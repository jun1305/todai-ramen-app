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
        // ==========================================
        // 0. URLパラメータの正規化
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
        $queryParams = $request->query();

        // ==========================================
        // 2. 部員ランキング集計（爆速モード）
        // ==========================================
        
        $userQuery = User::query();

        // ★分岐：期間が「累計(total)」の場合
       // ★分岐：期間が「累計(total)」の場合
        // ★分岐：期間が「累計(total)」の場合
        if ($period === 'total') {
            // ▼ A. 累計：カラムの数字をただ並べるだけ（最速）
            
            // ★追加：内訳表示のために、投稿ポイント合計と制覇ラリー数を取得する
            $userQuery->withSum('posts', 'earned_points');
            $userQuery->withCount(['joinedRallies as completed_rallies_count' => function ($q) {
                $q->where('is_completed', true);
            }]);

            if ($userSort === 'count') {
                // 杯数順（同じならスコア順）
                $userQuery->orderBy('posts_count', 'desc')
                          ->orderBy('total_score', 'desc');
            } else {
                // ポイント順（同じなら杯数順）
                $userQuery->orderBy('total_score', 'desc')
                          ->orderBy('posts_count', 'desc');
            }

        }else {
            // ▼ B. 期間別：これまで通りリアルタイム集計（期間指定が必要なため計算必須）
            $queryDate = match ($period) {
                'weekly'  => Carbon::now()->startOfWeek(),
                'monthly' => Carbon::now()->startOfMonth(),
                'yearly'  => Carbon::now()->startOfYear(),
                default   => null,
            };

            // 集計用フィルタ
            $postDateFilter = function ($q) use ($queryDate) {
                if ($queryDate) $q->where('eaten_at', '>=', $queryDate);
            };

            $rallyDateFilter = function ($q) use ($queryDate) {
                $q->where('is_completed', true);
                if ($queryDate) $q->where('user_rallies.completed_at', '>=', $queryDate);
            };

            // 集計実行
            $userQuery->withCount([
                    'posts' => $postDateFilter, 
                    'joinedRallies as completed_rallies_count' => $rallyDateFilter 
                ])
                ->withSum(['posts' => $postDateFilter], 'earned_points');

            // 計算結果でソート
            if ($userSort === 'count') {
                $userQuery->orderBy('posts_count', 'desc')
                          ->orderByRaw('(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5)) DESC');
            } else {
                $userQuery->orderByRaw('(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5)) DESC')
                          ->orderBy('posts_count', 'desc');
            }
        }

        $users = $userQuery->paginate(10, ['*'], 'users_page');
        $users->appends($queryParams);


        // ==========================================
        // 3. 人気店ランキング集計
        // ==========================================
        
        $shopQueryDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        $shopDateFilter = function ($q) use ($shopQueryDate) {
            if ($shopQueryDate) $q->where('eaten_at', '>=', $shopQueryDate);
        };

        $shopQuery = Shop::withCount(['posts' => $shopDateFilter])
            ->withAvg(['posts' => $shopDateFilter], 'score')
            ->with(['latestPost']);

        if ($shopSort === 'score') {
            $shopQuery->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
        } else {
            $shopQuery->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
        }

        $shops = $shopQuery->paginate(10, ['*'], 'shops_page');
        $shops->appends($queryParams);


        return view('ranking.index', compact('users', 'shops', 'period', 'userSort', 'shopSort'));
    }
}