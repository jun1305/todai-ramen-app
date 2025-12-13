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
        // クエリビルダ開始（ショップ数もカウントするように追加）
        $query = Rally::with(['creator', 'shops'])
            ->withCount(['challengers', 'shops']);

        // 1. 検索（キーワード）
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

        // 2. 絞り込み（フィルター）
        if (Auth::check() && $request->filled('filter')) {
            $filter = $request->input('filter');
            $userId = Auth::id();

            switch ($filter) {
                case 'not_joined': // 未参加のみ
                    $query->whereDoesntHave('challengers', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                    break;
                case 'active': // 挑戦中のみ
                    $query->whereHas('challengers', function($q) use ($userId) {
                        $q->where('user_id', $userId)->where('is_completed', false);
                    });
                    break;
                case 'completed': // 制覇済みのみ
                    $query->whereHas('challengers', function($q) use ($userId) {
                        $q->where('user_id', $userId)->where('is_completed', true);
                    });
                    break;
            }
        }

        // 3. 並び替え（ソート）
        $sort = $request->input('sort', 'newest'); // デフォルトは新着順
        switch ($sort) {
            case 'popular': // 参加人数順
                $query->orderBy('challengers_count', 'desc');
                break;
            case 'shops_desc': // 店が多い順（高難易度）
                $query->orderBy('shops_count', 'desc');
                break;
            case 'shops_asc': // 店が少ない順（手軽）
                $query->orderBy('shops_count', 'asc');
                break;
            default: // 新着順
                $query->latest();
                break;
        }

        $rallies = $query->paginate(10)->appends($request->query());

        // ユーザーの進行状況取得
        $myJoinedRallyIds = [];
        $myConqueredShopIds = [];

        if (Auth::check()) {
            $user = Auth::user();
            $myJoinedRallyIds = $user->joinedRallies()->pluck('rallies.id')->toArray();
            $myConqueredShopIds = $user->posts()->pluck('shop_id')->unique()->toArray();
        }

        return view('rallies.index', compact('rallies', 'myJoinedRallyIds', 'myConqueredShopIds'));
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
        $rally = Rally::with(['shops.latestPost', 'challengers'])->findOrFail($id);
        
        $isJoined = false;
        $conqueredShopIds = [];
        $myShopImages = []; // これを追加

        if (Auth::check()) {
            $user = Auth::user();
            $isJoined = $rally->challengers->contains($user->id);

            if ($isJoined) {
                // 自分の投稿を取得
                $myPosts = $user->posts()
                    ->whereIn('shop_id', $rally->shops->pluck('id'))
                    ->latest() // 新しい順
                    ->get();

                // ① 制覇した店IDリスト
                $conqueredShopIds = $myPosts->pluck('shop_id')->unique()->toArray();

                // ② 店ID => 写真パス の連想配列を作成（最新1枚だけ保持）
                foreach ($myPosts as $post) {
                    if (!isset($myShopImages[$post->shop_id]) && $post->image_path) {
                        $myShopImages[$post->shop_id] = $post->image_path;
                    }
                }

                // ③ コンプリート判定（既存のロジック）
                $totalShops = $rally->shops->count();
                $conqueredCount = count($conqueredShopIds);
                if ($totalShops > 0 && $conqueredCount >= $totalShops) {
                    $pivot = $user->joinedRallies()->where('rally_id', $rally->id)->first()->pivot;
                    if (!$pivot->is_completed) {
                        $user->joinedRallies()->updateExistingPivot($rally->id, [
                            'is_completed' => true,
                            'completed_at' => now(),
                        ]);
                    }
                }
            }
        }

        // ビューに渡す
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
}