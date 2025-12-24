<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Genre; // ★忘れず追加
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // 0. URLパラメータの正規化
        $defaults = [
            'tab'       => 'users',
            'period'    => 'total',
            'user_sort' => 'point',
            'shop_sort' => 'count',
            // genre_sort は不要になりました
        ];

        if (array_diff_key($defaults, $request->query())) {
            return redirect()->route('ranking.index', array_merge($defaults, $request->query()));
        }

        // 1. パラメータ取得
        $tab      = $request->input('tab');
        $period   = $request->input('period');
        $userSort = $request->input('user_sort');
        $shopSort = $request->input('shop_sort');
        
        // ★追加: 絞り込み用のジャンルID
        $genreId  = $request->input('genre_id'); 

        $queryParams = $request->query();

        // 空のページネーター
        $emptyPaginator = new LengthAwarePaginator([], 0, 10);
        $users  = $emptyPaginator;
        $shops  = $emptyPaginator;

        // 共通の日付フィルタ
        $startDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        // ★追加: 絞り込み選択肢用の全ジャンル取得
        $allGenres = Genre::all();

        // 2. 集計実行
        switch ($tab) {
            case 'users':
                $users = $this->getUsersRanking($period, $startDate, $userSort);
                $users->appends($queryParams);
                break;

            case 'shops':
                // ★引数に $genreId を追加
                $shops = $this->getShopsRanking($period, $startDate, $shopSort, $genreId);
                $shops->appends($queryParams);
                break;
        }

        // viewに $allGenres を渡す
        return view('ranking.index', compact('users', 'shops', 'period', 'tab', 'userSort', 'shopSort', 'allGenres', 'genreId'));
    }

    private function getRankingVersion()
    {
        return Cache::get('ranking_version', 1);
    }

    // A. 部員ランキング (変更なし)
    private function getUsersRanking($period, $startDate, $sort)
    {
        $page = request()->input('users_page', 1);
        $version = $this->getRankingVersion();
        $cacheKey = "ranking_users_v{$version}_{$period}_{$sort}_page{$page}";

        return Cache::remember($cacheKey, 600, function () use ($period, $startDate, $sort) {
            $query = User::query();

            if ($period === 'total') {
                $query->withSum('posts', 'earned_points')
                      ->withCount(['joinedRallies as completed_rallies_count' => function ($q) {
                          $q->where('is_completed', true);
                      }]);

                if ($sort === 'count') {
                    $query->orderBy('posts_count', 'desc')->orderBy('total_score', 'desc');
                } else {
                    $query->orderBy('total_score', 'desc')->orderBy('posts_count', 'desc');
                }
            } else {
                $postFilter = fn($q) => $startDate ? $q->where('eaten_at', '>=', $startDate) : null;
                $rallyFilter = function($q) use ($startDate) {
                    $q->where('is_completed', true);
                    if ($startDate) $q->where('user_rallies.completed_at', '>=', $startDate);
                };

                $query->withCount([
                    'posts' => $postFilter,
                    'joinedRallies as completed_rallies_count' => $rallyFilter
                ])->withSum(['posts' => $postFilter], 'earned_points');

                $scoreExpression = '(COALESCE(posts_sum_earned_points, 0) + (completed_rallies_count * 5))';
                
                if ($sort === 'count') {
                    $query->orderBy('posts_count', 'desc')->orderByRaw("$scoreExpression DESC");
                } else {
                    $query->orderByRaw("$scoreExpression DESC")->orderBy('posts_count', 'desc');
                }
            }

            return $query->paginate(10, ['*'], 'users_page');
        });
    }

    // ------------------------------------------------
    // B. 人気店ランキング (★ジャンル絞り込み対応)
    // ------------------------------------------------
    private function getShopsRanking($period, $startDate, $sort, $genreId = null)
    {
        $page = request()->input('shops_page', 1);
        $version = $this->getRankingVersion();
        
        // ★キャッシュキーにジャンルIDを含める（重要！）
        // genre_id が指定されていなければ 'all' という文字にする
        $genreKey = $genreId ? $genreId : 'all';
        $cacheKey = "ranking_shops_v{$version}_{$period}_{$sort}_g{$genreKey}_page{$page}";

        return Cache::remember($cacheKey, 600, function () use ($period, $startDate, $sort, $genreId) {
            $query = Shop::query();

            // ★追加: ジャンル絞り込み
            if ($genreId) {
                $query->whereHas('genres', function($q) use ($genreId) {
                    $q->where('genres.id', $genreId);
                });
            }

            if ($period === 'total') {
                // 累計
                $query->with('latestPost');
                if ($sort === 'score') {
                    $query->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
                } else {
                    $query->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
                }
            } else {
                // 期間指定
                $postFilter = fn($q) => $startDate ? $q->where('eaten_at', '>=', $startDate) : null;

                $query->withCount(['posts' => $postFilter])
                      ->withAvg(['posts' => $postFilter], 'score')
                      ->with('latestPost');

                if ($sort === 'score') {
                    $query->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
                } else {
                    $query->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
                }
            }

            return $query->paginate(10, ['*'], 'shops_page');
        });
    }
}