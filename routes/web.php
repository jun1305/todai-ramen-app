<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\AdminCampaignController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController; 
use App\Http\Controllers\NotificationController; // ←通知用も追加
use App\Models\Post;
use App\Models\Shop;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| ゲスト用ルート（ログインしてない人だけが通れる）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| 会員専用ルート（ログイン必須エリア）
|--------------------------------------------------------------------------
| ここに書かれたURLにログインなしでアクセスすると、自動でログイン画面に飛ばされます。
*/
Route::middleware('auth')->group(function () {

    // ホーム画面
    Route::get('/', function () {
        // ログインしている人のIDを取得（後で「自分へのいいね」判定などで使うため）
        // ※Blade側で Auth::user() を使うので、コントローラー側で特別なことはしなくてOK
        
        $posts = App\Models\Post::with(['shop', 'user', 'likes'])
            ->latest('eaten_at')
            ->paginate(10);
            
        $campaign = App\Models\Campaign::latest()->first();
        
        return view('welcome', compact('posts', 'campaign'));
    });

    // 投稿機能
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    
    // いいね機能
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('posts.like');

    // ランキング
    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

    // お店図鑑
    Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
    Route::get('/shops/{id}', [ShopController::class, 'show'])->name('shops.show');

    // マイページ
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::get('/users/{id}', [ProfileController::class, 'show'])->name('users.show');

    // 通知（既読にする機能）
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

    // 管理画面（※今はログインしていれば誰でも見れます。URLを知っている人のみ）
    Route::get('/admin/secret', [AdminCampaignController::class, 'index']);
    Route::post('/admin/campaigns', [AdminCampaignController::class, 'store'])->name('admin.campaigns.store');
    Route::delete('/admin/campaigns/{id}', [AdminCampaignController::class, 'destroy'])->name('admin.campaigns.destroy');

    // ログアウト
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);

    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/profile/icon', [App\Http\Controllers\ProfileController::class, 'updateIcon'])->name('profile.update_icon');
    Route::post('/profile/name', [App\Http\Controllers\ProfileController::class, 'updateName'])->name('profile.update_name');
});


