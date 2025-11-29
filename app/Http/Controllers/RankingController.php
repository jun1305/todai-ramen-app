<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Post;

class RankingController extends Controller
{
    public function index()
    {
        // ① 部員ランキング（★ここを変更：ポイントが多い順）
        $users = User::withCount('posts') // 投稿数も一応数えておく
            ->orderBy('points', 'desc')   // ポイント順に並び替え！
            ->limit(10)
            ->get();

        // ② 人気店ランキング（店はポイントを持たないので、変わらず投稿数順）
        $shops = Shop::withCount('posts')
            ->with(['latestPost'])
            ->orderBy('posts_count', 'desc')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('users', 'shops'));
    }
}
