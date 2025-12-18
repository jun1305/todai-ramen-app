<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rally;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// Cache は削除
use App\Models\User;

class RallyController extends Controller
{
    // ① ラリー一覧画面
    public function index(Request $request)
    {
        // パラメータ取得
        $search = $request->input('search');
        $type = $request->input('type', 'title');
        $filter = $request->input('filter');
        $sort = $request->input('sort', 'newest');
        
        // ==========================================
        // ★キャッシュ廃止：リアルタイム検索
        // ==========================================
        
        $query = Rally::with(['creator', 'shops'])
            ->withCount(['challengers', 'shops', 'likes']);

        // 検索条件
        if ($request->filled('search')) {
            if ($type === 'creator') {
                $query->whereHas('creator', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            } else {
                $query->where('title', 'like', "%{$search}%");
            }
        }

        // 絞り込みフィルター
        if (Auth::check() && $request->filled('filter')) {
            $userId = Auth::id();
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

        // ソート
        switch ($sort) {
            case 'popular': $query->orderBy('challengers_count', 'desc'); break;
            case 'shops_desc': $query->orderBy('shops_count', 'desc'); break;
            case 'shops_asc': $query->orderBy('shops_count', 'asc'); break;
            default: $query->latest(); break;
        }

        $rallies = $query->paginate(10);
        $rallies->appends($request->query());

        // マイデータ取得
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
        // 空欄の店データを除外
        $rawShops = $request->input('shops', []);
        $validShops = array_filter($rawShops, function($shop) {
            return !empty($shop['name']);
        });
        $request->merge(['shops' => array_values($validShops)]);

        $request->validate([
            'title' => 'required|max:50',
            'description' => 'nullable|max:200',
            'shops' => 'required|array|min:1|max:5', 
            'shops.*.name' => 'required|string', 
        ]);

        DB::transaction(function () use ($request) {
            $rally = Rally::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
            ]);

            foreach ($request->shops as $shopData) {
                $name = $shopData['name'];
                $placeId = $shopData['google_place_id'] ?? null;
                $address = $shopData['address'] ?? null;

                $shop = null;
                if ($placeId) {
                    $shop = Shop::where('google_place_id', $placeId)->first();
                }
                if (!$shop) {
                    $shop = Shop::where('name', $name)->first();
                }
                if (!$shop) {
                    $shop = Shop::create([
                        'name' => $name,
                        'google_place_id' => $placeId,
                        'address' => $address,
                    ]);
                } else {
                    if (empty($shop->google_place_id) && $placeId) {
                        $shop->update([
                            'google_place_id' => $placeId,
                            'address' => $address ?? $shop->address,
                        ]);
                    }
                }
                
                $rally->shops()->attach($shop->id);
            }
        });

        return redirect()->route('rallies.index')->with('success', 'ラリーを作成しました！');
    }

    // ④ ラリー詳細画面
    public function show($id)
    {
        // ==========================================
        // ★キャッシュ廃止：リアルタイム取得
        // ==========================================
        
        $rally = Rally::with('shops')->findOrFail($id);
        
        $rallyCreatedAt = $rally->created_at;
        $targetShopIds = $rally->shops->pluck('id');

        // 関連データのロード（挑戦者リストなど）
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
        
        $isJoined = false;
        $conqueredShopIds = [];
        $myShopImages = [];

        if (Auth::check()) {
            $user = Auth::user();
            $isJoined = $rally->challengers->contains($user->id);

            if ($isJoined) {
                // 自分の投稿データ取得
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

                // 達成判定（念のためアクセス時に同期チェック）
                $totalShops = $rally->shops->count();
                $conqueredCount = count($conqueredShopIds);
                
                $pivot = $user->joinedRallies()->where('rally_id', $rally->id)->first()->pivot;
                $isActuallyCompleted = ($totalShops > 0 && $conqueredCount >= $totalShops);

                if ($pivot->is_completed !== $isActuallyCompleted) {
                    // ※ここで本来はUserモデルのcompleted_rallies_countやtotal_scoreの調整が必要になる可能性がありますが、
                    // 基本的にはPostControllerで処理されているはずなので、ここではフラグの同期のみ行います。
                    // もし「閲覧だけで達成扱いにする」場合は、前述のincrement処理が必要です。
                    // 今回は「PostController/Rallyモデルで処理済み」という前提で、フラグ同期のみにします。
                    
                    $user->joinedRallies()->updateExistingPivot($rally->id, [
                        'is_completed' => $isActuallyCompleted,
                        'completed_at' => $isActuallyCompleted ? ($pivot->completed_at ?? now()) : null, 
                    ]);
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
            
            // 1. まず参加登録
            $rally->challengers()->attach(Auth::id());

            // ▼▼▼ 追加: 参加した瞬間に「過去の投稿」で条件を満たしているかチェックする ▼▼▼
            // これがないと、あとから参加した場合に反映されません
            $rally->checkAndComplete(Auth::user());
            // ▲▲▲ 追加ここまで ▲▲▲
        }

        return back()->with('success', 'ラリーに参加しました！制覇を目指そう！');
    }

    public function toggleLike($id)
    {
        $rally = Rally::findOrFail($id);
        $user = Auth::user();
    
        // モデル側のメソッドを活用（RallyモデルにisLikedByがある前提）
        if ($rally->isLikedBy($user)) {
            $rally->likes()->detach($user->id);
            $status = 'removed';
        } else {
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
            // 1. 達成者IDリストを取得
            $completedUserIds = $rally->challengers()
                ->wherePivot('is_completed', true)
                ->pluck('users.id')
                ->toArray();

            // 2. スコア減算
            if (!empty($completedUserIds)) {
                // ▼▼▼ 修正: withTrashed() を削除してシンプルにする ▼▼▼
                User::whereIn('id', $completedUserIds)
                    ->decrement('total_score', 5);
                
                User::whereIn('id', $completedUserIds)
                    ->decrement('completed_rallies_count');
            }

            // 3. 関連データの削除
            $rally->shops()->detach();
            $rally->challengers()->detach();
            $rally->likes()->detach();

            // 4. 本体削除
            $rally->delete();
        });

        return redirect()->route('rallies.index')->with('success', 'ラリーを削除しました。');
    }
}