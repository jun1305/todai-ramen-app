<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Genre;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
// use Illuminate\Support\Facades\Cache; //Cacheは使わないので削除OK

class RankingController extends Controller
{
    public function index(Request $request)
    {
        // ... (冒頭のパラメータ取得部分はそのまま) ...
        // 0. URLパラメータの正規化
        $defaults = [
            'tab'       => 'users',
            'period'    => 'total',
            'user_sort' => 'point',
            'shop_sort' => 'count',
        ];

        if (array_diff_key($defaults, $request->query())) {
            return redirect()->route('ranking.index', array_merge($defaults, $request->query()));
        }

        $tab      = $request->input('tab');
        $period   = $request->input('period');
        $userSort = $request->input('user_sort');
        $shopSort = $request->input('shop_sort');
        $genreId  = $request->input('genre_id'); 

        $queryParams = $request->query();
        $emptyPaginator = new LengthAwarePaginator([], 0, 10);
        $users  = $emptyPaginator;
        $shops  = $emptyPaginator;

        $startDate = match ($period) {
            'weekly'  => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'yearly'  => Carbon::now()->startOfYear(),
            default   => null,
        };

        $allGenres = Genre::all();

        // 2. 集計実行
        switch ($tab) {
            case 'users':
                // ★メソッド呼び出しだけにする
                $users = $this->getUsersRanking($period, $startDate, $userSort);
                $users->appends($queryParams);
                break;

            case 'shops':
                // ★メソッド呼び出しだけにする
                $shops = $this->getShopsRanking($period, $startDate, $shopSort, $genreId);
                $shops->appends($queryParams);
                break;
        }

        return view('ranking.index', compact('users', 'shops', 'period', 'tab', 'userSort', 'shopSort', 'allGenres', 'genreId'));
    }

    // ★ getRankingVersion メソッドは削除してOK

    // A. 部員ランキング (キャッシュなし版)
    private function getUsersRanking($period, $startDate, $sort)
    {
        // ★ Cache::remember を削除して、いきなりクエリを書く！
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
    }

    // B. 人気店ランキング (キャッシュなし版)
    private function getShopsRanking($period, $startDate, $sort, $genreId = null)
    {
        // ★ ここも Cache::remember を削除！
        $query = Shop::query();

        if ($genreId) {
            $query->whereHas('genres', function($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        if ($period === 'total') {
            $query->with('latestPost');
            if ($sort === 'score') {
                $query->orderBy('posts_avg_score', 'desc')->orderBy('posts_count', 'desc');
            } else {
                $query->orderBy('posts_count', 'desc')->orderBy('posts_avg_score', 'desc');
            }
        } else {
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
    }
}