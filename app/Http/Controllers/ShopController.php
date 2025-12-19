<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        // 1. 検索機能
        $query = Shop::query();
        
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
        }

        // 2. リスト表示用
        $shops = $query->with('latestPost')
            ->orderBy('posts_count', 'desc')
            ->paginate(10);

        // 3. ピックアップ用（★修正: ランダムに5件取得）
        // 検索時は表示しないので、検索がない時だけ取得
        $pickupShops = collect();
        if (!$search) {
            $pickupShops = Shop::whereHas('posts') // 投稿がある店のみ
                ->with('latestPost')
                ->inRandomOrder() // ★ここをランダムに変更
                ->limit(5)
                ->get();
        }

        return view('shops.index', compact('shops', 'pickupShops', 'search'));
    }

    // showメソッドは変更なしなので省略...
    public function show($id)
    {
        $shop = Shop::withCount('posts')->with(['latestPost'])->findOrFail($id);
        $posts = $shop->posts()->with('user')->latest('eaten_at')->paginate(10);

        // ★修正: 5件制限を撤廃。1件でもあれば計算、なければ null
        $avgScore = $shop->posts()->avg('score');
        if ($avgScore) {
            $avgScore = round($avgScore, 1);
        }

        return view('shops.show', compact('shop', 'posts', 'avgScore'));
    }
}