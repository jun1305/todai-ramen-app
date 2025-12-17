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
        // 0. URLパラメータの正規化（リダイレクト処理）
        // ==========================================
        // パラメータが1つでも欠けていたら、デフォルト値を埋めてリダイレクトさせます。
        // これにより「期間を変えたらタブが勝手に戻った」といった事故を根本から防ぎます。
        $defaults = [
            'tab'       => 'users',  // 初期タブ
            'period'    => 'total',  // 初期期間
            'user_sort' => 'point',  // 部員のソート初期値
            'shop_sort' => 'count',  // 店のソート初期値
        ];

        // 「デフォルトのキー」が「現在のクエリ」に含まれていない場合
        if (array_diff_key($defaults, $request->query())) {
            // 足りないパラメータをデフォルト値で埋めて、リダイレクト（再読み込み）
            // array_merge は後ろの引数が優先されるので、現在のパラメータは維持されます
            return redirect()->route('ranking.index', array_merge($defaults, $request->query()));
        }


        // ==========================================
        // 1. パラメータ取得 & 期間設定
        // ==========================================
        // リダイレクト後なので、ここには必ず値が入っています
        $period   = $request->input('period');
        $userSort = $request->input('user_sort');
        $shopSort = $request->input('shop_sort');
        
        // ページネーションリンク用（現在の全パラメータを引き継ぐ）
        $queryParams = $request->query();

        $queryDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        // 集計用フィルタ（クロージャ）
        $postDateFilter = function ($q) use ($queryDate) {
            if ($queryDate) $q->where('eaten_at', '>=', $queryDate);
        };

        // ラリー用フィルタ（テーブル名を指定しないのがコツ）
        $rallyDateFilter = function ($q) use ($queryDate) {
            $q->where('is_completed', true);
            if ($queryDate) $q->where('completed_at', '>=', $queryDate);
        };


        // ==========================================
        // 2. 部員ランキング集計
        // ==========================================
        $userQuery = User::withCount([
                'posts' => $postDateFilter, 
                'joinedRallies as completed_rallies_count' => $rallyDateFilter 
            ])
            ->withSum(['posts' => $postDateFilter], 'earned_points');

        // ソートロジック（合計ポイント = 投稿Pt + ラリー数×5）
        if ($userSort === 'count') {
            $userQuery->orderBy('posts_count', 'desc')
                      ->orderByRaw('(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5)) DESC');
        } else {
            $userQuery->orderByRaw('(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5)) DESC')
                      ->orderBy('posts_count', 'desc');
        }

        $users = $userQuery->paginate(10, ['*'], 'users_page');
        $users->appends($queryParams); // 全パラメータを維持


        // ==========================================
        // 3. 人気店ランキング集計
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
        $shops->appends($queryParams); // 全パラメータを維持


        return view('ranking.index', compact('users', 'shops', 'period', 'userSort', 'shopSort'));
    }
}