<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;

class ShopController extends Controller
{
    // ① お店一覧（検索機能つき）
    public function index(Request $request)
    {
        // 検索ワードがあれば絞り込む
        $query = Shop::withCount('posts')->with(['latestPost']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // 投稿が多い順に並べて取得
        $shops = $query->orderBy('posts_count', 'desc')->paginate(20);

        return view('shops.index', compact('shops', 'search'));
    }

    // ② お店詳細（その店の投稿一覧）
    public function show($id)
    {
        // お店情報を取得（もし無ければ404エラー）
        $shop = Shop::withCount('posts')->with(['latestPost'])->findOrFail($id);

        // その店の投稿を新しい順に取得（ユーザー情報つき）
        $posts = $shop->posts()->with('user')->latest('eaten_at')->paginate(10);

        return view('shops.show', compact('shop', 'posts'));
    }
}