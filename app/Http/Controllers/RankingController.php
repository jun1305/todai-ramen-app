<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use Carbon\Carbon;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // 1. パラメータ取得
        $period = $request->input('period', 'total');
        $userSort = $request->input('user_sort', 'point');
        $shopSort = $request->input('shop_sort', 'count');
        
        $queryParams = $request->query();

        // 2. 期間スコープの定義
        $queryDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        // --- A. 投稿用の集計フィルタ (eaten_at) ---
        $postDateFilter = function ($q) use ($queryDate) {
            if ($queryDate) $q->where('eaten_at', '>=', $queryDate);
        };

        // --- B. ラリー用の集計フィルタ (completed_at) ---
        // ★修正済み: テーブル名を指定せずカラム名のみにする
        $rallyDateFilter = function ($q) use ($queryDate) {
            $q->where('is_completed', true);
            if ($queryDate) $q->where('completed_at', '>=', $queryDate);
        };

        // ==========================================
        // 3. 部員ランキング
        // ==========================================
        $userQuery = User::withCount([
                'posts' => $postDateFilter, 
                'joinedRallies as completed_rallies_count' => $rallyDateFilter 
            ])
            ->withSum(['posts' => $postDateFilter], 'earned_points');

        if ($userSort === 'count') {
            $userQuery->orderBy('posts_count', 'desc')
                      ->orderByRaw('(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5)) DESC');
        } else {
            $userQuery->orderByRaw('(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5)) DESC')
                      ->orderBy('posts_count', 'desc');
        }

        $users = $userQuery->paginate(10, ['*'], 'users_page');
        $users->appends(array_merge($queryParams, ['tab' => 'users']));


        // ==========================================
        // 4. 人気店ランキング
        // ==========================================
        $shopQuery = Shop::withCount(['posts' => $postDateFilter])
            ->withAvg(['posts' => $postDateFilter], 'score')
            ->with(['latestPost']);

        if ($shopSort === 'score') {
            $shopQuery->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
        } else {
            $shopQuery->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
        }

        $shops = $shopQuery->paginate(10, ['*'], 'shops_page');
        $shops->appends(array_merge($queryParams, ['tab' => 'shops']));


        return view('ranking.index', compact('users', 'shops', 'period', 'userSort', 'shopSort'));
    }
}