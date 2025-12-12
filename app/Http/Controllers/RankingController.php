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
        // 1. パラメータ取得（デフォルト値を設定）
        $period = $request->input('period', 'total');
        $userSort = $request->input('user_sort', 'point');
        $shopSort = $request->input('shop_sort', 'count');
        
        // ★現在のすべてのクエリパラメータを取得（期間やソート状態を維持するため）
        $queryParams = $request->query();

        // 2. 期間スコープの定義
        $queryDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        // --- 共通の集計用クロージャ ---
        // 同じロジックを何度も書かないように変数化します
        $dateFilter = function ($q) use ($queryDate) {
            if ($queryDate) $q->where('eaten_at', '>=', $queryDate);
        };

        // ==========================================
        // 3. 部員ランキング
        // ==========================================
        $userQuery = User::withCount(['posts' => $dateFilter])
            ->withSum(['posts' => $dateFilter], 'earned_points');

        if ($userSort === 'count') {
            $userQuery->orderBy('posts_count', 'desc')->orderBy('posts_sum_earned_points', 'desc');
        } else {
            $userQuery->orderBy('posts_sum_earned_points', 'desc')->orderBy('posts_count', 'desc');
        }

        $users = $userQuery->paginate(10, ['*'], 'users_page');
        
        // ★ここが修正ポイント！
        // ユーザー一覧のページネーションリンクには、必ず 'tab' => 'users' を付ける
        $users->appends(array_merge($queryParams, ['tab' => 'users']));


        // ==========================================
        // 4. 人気店ランキング
        // ==========================================
        $shopQuery = Shop::withCount(['posts' => $dateFilter])
            ->withAvg(['posts' => $dateFilter], 'score')
            ->with(['latestPost']);

        if ($shopSort === 'score') {
            $shopQuery->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
        } else {
            $shopQuery->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
        }

        $shops = $shopQuery->paginate(10, ['*'], 'shops_page');

        // ★ここが修正ポイント！
        // お店一覧のページネーションリンクには、必ず 'tab' => 'shops' を付ける
        $shops->appends(array_merge($queryParams, ['tab' => 'shops']));


        return view('ranking.index', compact('users', 'shops', 'period', 'userSort', 'shopSort'));
    }
}