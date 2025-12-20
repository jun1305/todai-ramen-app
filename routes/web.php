<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\AdminCampaignController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController; 
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushController; // ← 追加: 下でフルパスで書かれていたため
use App\Models\Post;
use App\Models\Campaign; // ← 追加: ロジック内で使用しているため
use App\Http\Controllers\RallyController;
use App\Http\Controllers\DailyRamenController;
use App\Models\User;
use App\Http\Controllers\CommentController;

/*
|--------------------------------------------------------------------------
| ゲスト用ルート（ログインしてない人だけが通れる）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // 登録・ログイン
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // パスワードリセット
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'verifySecretAnswer'])->name('password.verify');
    Route::get('/reset-password', [AuthController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| 会員専用ルート（ログイン必須エリア）
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // ▼ ホーム画面
    // （本来はここも Controller に移動させるのがベストです）
    Route::get('/', function () {
        $posts = Post::with(['shop', 'user', 'likes'])
            ->latest('eaten_at')
            ->paginate(20);
            
        $campaign = Campaign::latest()->first();
        
        return view('welcome', compact('posts', 'campaign'));
    })->name('home'); // 名前をつけておくと便利

    // ▼ 投稿機能（作成・保存・編集・更新・削除・いいね）
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('posts.like');

    // 投稿詳細
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

    // コメント投稿・削除
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // ▼ お店・ランキング
    Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
    Route::get('/shops/{id}', [ShopController::class, 'show'])->name('shops.show');
    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

    // ▼ ブックマーク機能
    Route::get('/bookmarks', [App\Http\Controllers\BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/shops/{shop}/bookmark', [App\Http\Controllers\BookmarkController::class, 'toggle'])->name('shops.bookmark');

    // ▼ ラーメンラリー（ここに追加）
    Route::get('/rallies', [RallyController::class, 'index'])->name('rallies.index');
    Route::get('/rallies/create', [RallyController::class, 'create'])->name('rallies.create');
    Route::post('/rallies', [RallyController::class, 'store'])->name('rallies.store');
    Route::get('/rallies/{id}', [RallyController::class, 'show'])->name('rallies.show');
    Route::post('/rallies/{id}/join', [RallyController::class, 'join'])->name('rallies.join');
    Route::post('/rallies/{rally}/like', [App\Http\Controllers\RallyController::class, 'toggleLike'])->name('rallies.like')->middleware('auth');
    Route::delete('/rallies/{id}', [RallyController::class, 'destroy'])->name('rallies.destroy');

    // ▼ マイページ・ユーザー設定
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/icon', [ProfileController::class, 'updateIcon'])->name('profile.update_icon');
    Route::post('/profile/name', [ProfileController::class, 'updateName'])->name('profile.update_name');
    Route::get('/users/{id}', [ProfileController::class, 'show'])->name('users.show');

    // ▼ 通知・プッシュ通知
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);
    Route::post('/push/subscribe', [PushController::class, 'subscribe']);

    // ▼ 管理画面
    Route::get('/admin/secret', [AdminCampaignController::class, 'index']);
    Route::post('/admin/campaigns', [AdminCampaignController::class, 'store'])->name('admin.campaigns.store');
    Route::delete('/admin/campaigns/{id}', [AdminCampaignController::class, 'destroy'])->name('admin.campaigns.destroy');

    // ▼ 今日の一杯（会長ログ）
    Route::get('/daily', [App\Http\Controllers\DailyRamenController::class, 'index'])->name('daily.index');
    Route::get('/daily/create', [App\Http\Controllers\DailyRamenController::class, 'create'])->name('daily.create');
    Route::post('/daily', [App\Http\Controllers\DailyRamenController::class, 'store'])->name('daily.store');
    Route::get('/daily/calendar-fetch', [App\Http\Controllers\DailyRamenController::class, 'fetchCalendar'])->name('daily.fetch_calendar');
    Route::get('/daily/{id}/edit', [DailyRamenController::class, 'edit'])->name('daily.edit');
    Route::put('/daily/{id}', [DailyRamenController::class, 'update'])->name('daily.update');

    // ▼ 丼顔フラッシュクイズ
    Route::get('/quiz', [App\Http\Controllers\QuizController::class, 'index'])->name('quiz.index');

    // ▼ ログアウト
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/recalculate-scores', function () {
    $users = App\Models\User::with(['posts', 'joinedRallies'])->get(); // posts も必要
    
    foreach ($users as $user) {
        // 1. 各種カウント
        $postCount = $user->posts()->count();
        $rallyCount = $user->joinedRallies()->wherePivot('is_completed', true)->count();
        
        // 2. ポイント計算
        $postPoints = $user->posts()->sum('earned_points');
        $rallyPoints = $rallyCount * 5;
        
        // 3. まとめて保存
        $user->posts_count = $postCount;
        $user->completed_rallies_count = $rallyCount;
        $user->total_score = $postPoints + $rallyPoints;
        
        $user->save();
    }
    
    return '件数とスコアの再計算が完了しました！';
});

Route::get('/recalculate-shop-scores', function () {
    $shops = App\Models\Shop::with('posts')->get();
    
    foreach ($shops as $shop) {
        // 1. 投稿数
        $count = $shop->posts()->count();
        
        // 2. 平均点（投稿がない場合は0）
        $avg = $count > 0 ? $shop->posts()->avg('score') : 0;
        
        // 3. 保存
        $shop->posts_count = $count;
        $shop->posts_avg_score = $avg;
        $shop->save();
    }
    
    return '店舗のスコア再計算が完了しました！';
});