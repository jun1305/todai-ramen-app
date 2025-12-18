<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rally;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RallyController extends Controller
{
    // ① ラリー一覧画面
    public function index(Request $request)
    {
        // パラメータ取得
        $page = $request->input('page', 1);
        $search = $request->input('search');
        $type = $request->input('type', 'title');
        $filter = $request->input('filter');
        $sort = $request->input('sort', 'newest');
        
        // ★キャッシュキーの作成（検索条件やページ番号を含める）
        // ログインユーザーによってフィルタ結果が変わる（参加中など）ので、
        // フィルタがある場合はキャッシュしない（またはユーザーIDをキーに含める）のが安全です。
        $userId = Auth::id();
        $cacheKey = "rallies_index_{$page}_{$sort}_{$search}_{$type}_{$filter}_user{$userId}";

        // 5分間キャッシュ
        $rallies = Cache::remember($cacheKey, 60 * 5, function () use ($request, $search, $type, $filter, $sort, $userId) {
            
            $query = Rally::with(['creator', 'shops'])
                ->withCount(['challengers', 'shops', 'likes']);

            // (検索・絞り込み・ソートの処理はそのまま...)
            if ($request->filled('search')) {
                if ($type === 'creator') {
                    $query->whereHas('creator', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                } else {
                    $query->where('title', 'like', "%{$search}%");
                }
            }

            if ($userId && $request->filled('filter')) {
                switch ($filter) {
                    case 'not_joined':
                        $query->whereDoesntHave('challengers', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                        break;
                    case 'active':
                        $query->whereHas('challengers', function($q) use ($userId) {
                            $q->where('user_id', $userId)->where('is_completed', false);
                        });
                        break;
                    case 'completed':
                        $query->whereHas('challengers', function($q) use ($userId) {
                            $q->where('user_id', $userId)->where('is_completed', true);
                        });
                        break;
                    case 'liked':
                        $query->whereHas('likes', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                        break;
                }
            }

            switch ($sort) {
                case 'popular': $query->orderBy('challengers_count', 'desc'); break;
                case 'shops_desc': $query->orderBy('shops_count', 'desc'); break;
                case 'shops_asc': $query->orderBy('shops_count', 'asc'); break;
                default: $query->latest(); break;
            }

            return $query->paginate(10);
        });

        // Appendsはキャッシュの外で
        $rallies->appends($request->query());

        // ★マイデータ取得（ここは軽いのでキャッシュ不要、リアルタイム性が大事）
        $myJoinedRallies = collect();
        $myPosts = collect();
        $myLikedRallyIds = [];

        if (Auth::check()) {
            $user = Auth::user();
            $myJoinedRallies = $user->joinedRallies()->get()->keyBy('id');
            $myPosts = $user->posts()->select('shop_id', 'eaten_at')->get();
            $myLikedRallyIds = $user->likedRallies()->pluck('rallies.id')->toArray();
        }

        return view('rallies.index', compact('rallies', 'myJoinedRallies', 'myPosts', 'myLikedRallyIds'));
    }

    // ② ラリー作成画面
    public function create()
    {
        return view('rallies.create');
    }

    // ③ ラリー保存処理
    public function store(Request $request)
    {
        // ▼▼▼ 追加: バリデーション前に、名前が空欄のデータを取り除く ▼▼▼
        $rawShops = $request->input('shops', []);
        
        // 名前が入っているものだけをフィルター（除外）する
        $validShops = array_filter($rawShops, function($shop) {
            return !empty($shop['name']);
        });

        // 配列のキー（番号）を 0, 1, 2... と綺麗に振り直して、リクエスト情報を書き換える
        $request->merge(['shops' => array_values($validShops)]);
        // ▲▲▲ 追加ここまで ▲▲▲

        $request->validate([
            'title' => 'required|max:50',
            'description' => 'nullable|max:200',
            // これで「中身があるものだけ」で個数チェックされるようになります
            'shops' => 'required|array|min:1|max:5', 
            'shops.*.name' => 'required|string', 
        ]);

        DB::transaction(function () use ($request) {
            // 1. ラリー本体を作成
            $rally = Rally::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
            ]);

            // 2. お店を登録（賢く検索・作成）して紐付け
            foreach ($request->shops as $shopData) {
                // 送られてきたデータを取り出し
                $name = $shopData['name'];
                $placeId = $shopData['google_place_id'] ?? null;
                $address = $shopData['address'] ?? null;

                $shop = null;

                // A. Google Place ID があれば、それで検索（一番確実）
                if ($placeId) {
                    $shop = Shop::where('google_place_id', $placeId)->first();
                }

                // B. なければ、店名で検索
                if (!$shop) {
                    $shop = Shop::where('name', $name)->first();
                }

                // C. それでもなければ、新規作成（ここで住所なども保存！）
                if (!$shop) {
                    $shop = Shop::create([
                        'name' => $name,
                        'google_place_id' => $placeId,
                        'address' => $address,
                    ]);
                } else {
                    // D. 既存の店なら、足りない情報を補完してあげる（親切設計）
                    if (empty($shop->google_place_id) && $placeId) {
                        $shop->update([
                            'google_place_id' => $placeId,
                            'address' => $address ?? $shop->address,
                        ]);
                    }
                }
                
                // 中間テーブルに登録
                $rally->shops()->attach($shop->id);
            }
        });

        return redirect()->route('rallies.index')->with('success', 'ラリーを作成しました！');
    }

    // ④ ラリー詳細画面
    public function show($id)
    {
        // ★基本情報だけキャッシュする（5分）
        // 参加者のリストなどが重いので、ここをキャッシュします。
        $rallyCacheKey = "rally_show_{$id}";
        
        $rally = Cache::remember($rallyCacheKey, 60 * 5, function () use ($id) {
            $rally = Rally::with('shops')->findOrFail($id);
            $rallyCreatedAt = $rally->created_at;
            $targetShopIds = $rally->shops->pluck('id');

            // 関連データのロード（重い処理）
            $rally->load(['shops.latestPost', 'challengers' => function($q) use ($rallyCreatedAt, $targetShopIds) {
                $q->orderByDesc('pivot_is_completed')
                  ->orderBy('pivot_completed_at')
                  ->orderByDesc('pivot_created_at')
                  ->with(['posts' => function($postQ) use ($rallyCreatedAt, $targetShopIds) {
                      $postQ->select('id', 'user_id', 'shop_id', 'eaten_at')
                            ->whereIn('shop_id', $targetShopIds)
                            ->where('eaten_at', '>=', $rallyCreatedAt);
                  }]);
            }]);
            
            return $rally;
        });
        
        // ★ここから下は「自分自身のデータ」なのでキャッシュしません（リアルタイム判定）
        $isJoined = false;
        $conqueredShopIds = [];
        $myShopImages = [];

        if (Auth::check()) {
            $user = Auth::user();
            // キャッシュされた $rally を使うのでDB負荷は低いです
            $isJoined = $rally->challengers->contains($user->id);

            if ($isJoined) {
                // 自分の投稿データ取得（これは毎回やる）
                $rallyCreatedAt = $rally->created_at;
                $targetShopIds = $rally->shops->pluck('id');

                $myPosts = $user->posts()
                    ->whereIn('shop_id', $targetShopIds)
                    ->where('eaten_at', '>=', $rallyCreatedAt)
                    ->latest()
                    ->get();

                $conqueredShopIds = $myPosts->pluck('shop_id')->unique()->toArray();

                foreach ($myPosts as $post) {
                    if (!isset($myShopImages[$post->shop_id]) && $post->image_path) {
                        $myShopImages[$post->shop_id] = $post->image_path;
                    }
                }

                // 達成判定（更新があればDB書き込み）
                $totalShops = $rally->shops->count();
                $conqueredCount = count($conqueredShopIds);
                
                // pivotデータ取得はキャッシュできないので直接
                $pivot = $user->joinedRallies()->where('rally_id', $rally->id)->first()->pivot;
                $isActuallyCompleted = ($totalShops > 0 && $conqueredCount >= $totalShops);

                if ($pivot->is_completed !== $isActuallyCompleted) {
                    $user->joinedRallies()->updateExistingPivot($rally->id, [
                        'is_completed' => $isActuallyCompleted,
                        'completed_at' => $isActuallyCompleted ? ($pivot->completed_at ?? now()) : null, 
                    ]);
                    // ここで $rally のキャッシュを消してもいいですが、
                    // 自分の画面上の表示は $isActuallyCompleted で制御すればOKです
                }
            }
        }

        return view('rallies.show', compact('rally', 'isJoined', 'conqueredShopIds', 'myShopImages'));
    }

    // ⑤ ラリー参加処理
    public function join($id)
    {
        $rally = Rally::findOrFail($id);

        // すでに参加していなければ参加登録
        if (!$rally->challengers->contains(Auth::id())) {
            $rally->challengers()->attach(Auth::id());
        }

        return back()->with('success', 'ラリーに参加しました！制覇を目指そう！');
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'rally_likes', 'rally_id', 'user_id');
    }
    
    // ユーザーがいいねしているか確認する便利メソッド
    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function toggleLike($id)
    {
        $rally = Rally::findOrFail($id);
        $user = Auth::user();
    
        if ($rally->isLikedBy($user)) {
            // 解除
            $rally->likes()->detach($user->id);
            $status = 'removed';
        } else {
            // 登録
            $rally->likes()->attach($user->id);
            $status = 'added';
        }
    
        return response()->json([
            'status' => $status,
            'count' => $rally->likes()->count()
        ]);
    }

    public function destroy($id)
    {
        $rally = Rally::findOrFail($id);

        if (Auth::id() !== $rally->user_id) {
            abort(403);
        }

        DB::transaction(function () use ($rally) {
            // ▼▼▼ 追加: 達成済みのユーザーからポイントを回収する ▼▼▼
            
            // 1. このラリーを達成しているユーザーのIDリストを取得
            $completedUserIds = $rally->challengers()
                ->wherePivot('is_completed', true)
                ->pluck('users.id');

            // 2. そのユーザーたちのスコアから 5pt 引く
            // （whereIn を使うと、対象者が100人いても1回のSQLで済むので高速です）
            if ($completedUserIds->isNotEmpty()) {
                User::whereIn('id', $completedUserIds)->decrement('total_score', 5);
            }
            // ▲▲▲ 追加ここまで ▲▲▲


            // 3. 関連データの削除（ここはそのまま）
            $rally->shops()->detach();
            $rally->challengers()->detach();
            $rally->likes()->detach();

            // 4. ラリー本体の削除
            $rally->delete();
        });

        return redirect()->route('rallies.index')->with('success', 'ラリーを削除しました。');
    }

    
}