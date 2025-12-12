<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;

class ShopController extends Controller
{
    // ① お店一覧（変更なし）
    public function index(Request $request)
    {
        $query = Shop::withCount('posts')->with(['latestPost']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $shops = $query->orderBy('posts_count', 'desc')->paginate(20);

        return view('shops.index', compact('shops', 'search'));
    }

    // ② お店詳細（修正）
    public function show($id)
    {
        $shop = Shop::withCount('posts')->with(['latestPost'])->findOrFail($id);
        $posts = $shop->posts()->with('user')->latest('eaten_at')->paginate(10);

        // ▼▼▼ 追加: 5件以上なら平均点を計算 ▼▼▼
        $avgScore = null; // 初期値はnull
        
        if ($shop->posts_count >= 5) {
            // 平均を算出し、小数点第1位で四捨五入（例: 84.375 -> 84.4）
            $avgScore = round($shop->posts()->avg('score'), 1);
        }
        // ▲▲▲ ここまで ▲▲▲

        // avgScore を View に渡す
        return view('shops.show', compact('shop', 'posts', 'avgScore'));
    }
}