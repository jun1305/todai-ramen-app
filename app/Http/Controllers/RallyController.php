<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rally;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RallyController extends Controller
{
    // ① ラリー一覧画面
    public function index(Request $request)
    {
        $query = Rally::with(['creator', 'shops'])
            ->withCount(['challengers', 'shops', 'likes']);

        // (検索・絞り込み・ソートの処理はそのまま維持...)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $type = $request->input('type', 'title');
            if ($type === 'creator') {
                $query->whereHas('creator', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            } else {
                $query->where('title', 'like', "%{$search}%");
            }
        }

        if (Auth::check() && $request->filled('filter')) {
            $filter = $request->input('filter');
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
                    // rally_likes テーブルに自分のIDがあるものだけ抽出
                    $query->whereHas('likes', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                    break;
            }
        }

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'popular': $query->orderBy('challengers_count', 'desc'); break;
            case 'shops_desc': $query->orderBy('shops_count', 'desc'); break;
            case 'shops_asc': $query->orderBy('shops_count', 'asc'); break;
            default: $query->latest(); break;
        }

        $rallies = $query->paginate(10)->appends($request->query());

        $myJoinedRallies = collect();
        $myPosts = collect();
        $myLikedRallyIds = []; // 初期化

        if (Auth::check()) {
            $user = Auth::user();
            $myJoinedRallies = $user->joinedRallies()->get()->keyBy('id');
            $myPosts = $user->posts()->select('shop_id', 'eaten_at')->get();
            
            // ▼▼▼ 修正箇所: likes() ではなく likedRallies() を使う ▼▼▼
            // ここでエラーが出ていました。likedRallies() に変えれば直ります。
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
        $request->validate([
            'title' => 'required|max:50',
            'description' => 'nullable|max:200',
            'shops' => 'required|array|min:1|max:5', // 店名は配列で受け取る
            'shops.*' => 'required|string|distinct', // 重複不可
        ]);

        DB::transaction(function () use ($request) {
            // 1. ラリー本体を作成
            $rally = Rally::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
            ]);

            // 2. お店を登録（なければ作成）して紐付け
            foreach ($request->shops as $shopName) {
                // 名前で検索、なければ作成
                $shop = Shop::firstOrCreate(['name' => $shopName]);
                
                // 中間テーブルに登録
                $rally->shops()->attach($shop->id);
            }
        });

        return redirect()->route('rallies.index')->with('success', 'ラリーを作成しました！');
    }

    // ④ ラリー詳細画面（スタンプカード）
    public function show($id)
    {
        // 1. まずラリー本体とお店リストを取得（作成日と店IDが必要なため）
        $rally = Rally::with('shops')->findOrFail($id);
        
        $rallyCreatedAt = $rally->created_at;
        $targetShopIds = $rally->shops->pluck('id'); // このラリーの店IDリスト

        // 2. 関連データを条件付きでロード（ここで厳密に絞り込む！）
        $rally->load(['shops.latestPost', 'challengers' => function($q) use ($rallyCreatedAt, $targetShopIds) {
            $q->orderByDesc('pivot_is_completed')
              ->orderBy('pivot_completed_at')
              ->orderByDesc('pivot_created_at')
              // 挑戦者の投稿を取得する際、「対象店」かつ「ラリー作成日以降」に限定する
              ->with(['posts' => function($postQ) use ($rallyCreatedAt, $targetShopIds) {
                  $postQ->select('id', 'user_id', 'shop_id', 'eaten_at') // 必要な列だけ
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
                // ▼▼▼ 自分用のデータ取得（ここも同じ条件で厳密に） ▼▼▼
                $myPosts = $user->posts()
                    ->whereIn('shop_id', $targetShopIds)
                    ->where('eaten_at', '>=', $rallyCreatedAt)
                    ->latest()
                    ->get();

                $conqueredShopIds = $myPosts->pluck('shop_id')->unique()->toArray();

                // 写真パスの収集
                foreach ($myPosts as $post) {
                    if (!isset($myShopImages[$post->shop_id]) && $post->image_path) {
                        $myShopImages[$post->shop_id] = $post->image_path;
                    }
                }

                // ▼▼▼ 達成状態の同期（前回と同じロジック） ▼▼▼
                $totalShops = $rally->shops->count();
                $conqueredCount = count($conqueredShopIds);
                
                $pivot = $user->joinedRallies()->where('rally_id', $rally->id)->first()->pivot;
                $isActuallyCompleted = ($totalShops > 0 && $conqueredCount >= $totalShops);

                if ($pivot->is_completed !== $isActuallyCompleted) {
                    $user->joinedRallies()->updateExistingPivot($rally->id, [
                        'is_completed' => $isActuallyCompleted,
                        'completed_at' => $isActuallyCompleted ? ($pivot->completed_at ?? now()) : null, 
                    ]);
                    $rally = $rally->fresh(['shops', 'challengers']); // リロード
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
}