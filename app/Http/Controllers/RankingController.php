<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Post;
use Carbon\Carbon;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // 1. 期間の取得（デフォルトは 'total'）
        $period = $request->input('period', 'total');

        // 2. 日付の範囲設定
        $queryDate = null;
        switch ($period) {
            case 'weekly':
                $queryDate = Carbon::now()->startOfWeek();
                break;
            case 'monthly':
                $queryDate = Carbon::now()->startOfMonth();
                break;
            case 'yearly':
                $queryDate = Carbon::now()->startOfYear();
                break;
            default: // total
                $queryDate = null;
        }

        // 3. ユーザーランキング（ポイント順・上位5名）
        // 期間内の投稿に紐づくポイントを集計します
        $users = User::withCount(['posts' => function ($query) use ($queryDate) {
                if ($queryDate) {
                    $query->where('eaten_at', '>=', $queryDate);
                }
            }])
            ->withSum(['posts' => function ($query) use ($queryDate) {
                if ($queryDate) {
                    $query->where('eaten_at', '>=', $queryDate);
                }
            }], 'earned_points') // 'earned_points'カラムを合計してポイントとする場合
            ->get()
            // ポイント（posts_sum_score）がない場合は0にする
            ->each(function($user) {
                $user->period_points = $user->posts_sum_earned_points ?? 0;
                $user->period_count = $user->posts_count ?? 0;
            })
            ->sortByDesc('period_points') // 集計したポイントでソート
            ->take(5)
            ->values(); // ★ここでトップ5に制限

        // 4. 人気店ランキング（投稿数順・上位5店）
        $shops = Shop::withCount(['posts' => function ($query) use ($queryDate) {
                if ($queryDate) {
                    $query->where('eaten_at', '>=', $queryDate);
                }
            }])
            ->with(['latestPost' => function ($query) {
                // サムネイル用に最新の投稿画像を取得
                $query->whereNotNull('image_path');
            }])
            ->orderBy('posts_count', 'desc')
            ->take(5) // ★ここでトップ5に制限
            ->get()
            ->values();

        return view('ranking.index', compact('users', 'shops', 'period'));
    }
}
