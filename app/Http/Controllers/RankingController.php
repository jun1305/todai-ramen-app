<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache; // 👈 追加！

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // ==========================================
        // 0. URLパラメータの正規化（リダイレクト処理）
        // ==========================================
        $defaults = [
            'tab'       => 'users',
            'period'    => 'total',
            'user_sort' => 'point',
            'shop_sort' => 'count',
        ];

        if (array_diff_key($defaults, $request->query())) {
            return redirect()->route('ranking.index', array_merge($defaults, $request->query()));
        }

        // 1. パラメータ取得
        $period   = $request->input('period');
        $userSort = $request->input('user_sort');
        $shopSort = $request->input('shop_sort');
        $userPage = $request->input('users_page', 1); // ページ番号もキーにする
        $shopPage = $request->input('shops_page', 1);
        
        $queryParams = $request->query();

        // 共通ロジック：期間計算
        $queryDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        // ==========================================
        // 2. 部員ランキング集計（キャッシュ対応）
        // ==========================================
        // キャッシュキー：条件ごとに一意になる名前をつける
        $usersCacheKey = "ranking_users_{$period}_{$userSort}_page_{$userPage}";

        // 60秒 * 5 = 5分間キャッシュする
        $users = Cache::remember($usersCacheKey, 60 * 5, function () use ($queryDate, $userSort) {
            
            // --- ここに重い処理を閉じ込める ---
            $postDateFilter = function ($q) use ($queryDate) {
                if ($queryDate) $q->where('eaten_at', '>=', $queryDate);
            };

            $rallyDateFilter = function ($q) use ($queryDate) {
                $q->where('is_completed', true);
                if ($queryDate) $q->where('user_rallies.completed_at', '>=', $queryDate);
            };

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

            return $userQuery->paginate(10, ['*'], 'users_page');
        });

        // キャッシュから取り出した後にパラメータを付与
        $users->appends($queryParams);


        // ==========================================
        // 3. 人気店ランキング集計（キャッシュ対応）
        // ==========================================
        $shopsCacheKey = "ranking_shops_{$period}_{$shopSort}_page_{$shopPage}";

        $shops = Cache::remember($shopsCacheKey, 60 * 5, function () use ($queryDate, $shopSort) {
            
            // --- ここに重い処理を閉じ込める ---
            $postDateFilter = function ($q) use ($queryDate) {
                if ($queryDate) $q->where('eaten_at', '>=', $queryDate);
            };
            
            $shopQuery = Shop::withCount(['posts' => $postDateFilter])
                ->withAvg(['posts' => $postDateFilter], 'score')
                ->with(['latestPost']);

            if ($shopSort === 'score') {
                $shopQuery->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
            } else {
                $shopQuery->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
            }

            return $shopQuery->paginate(10, ['*'], 'shops_page');
        });

        $shops->appends($queryParams);


        return view('ranking.index', compact('users', 'shops', 'period', 'userSort', 'shopSort'));
    }
}