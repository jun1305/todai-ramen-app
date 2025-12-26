<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Shop;
use App\Models\Campaign;
use App\Models\Genre;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessPostSubmission;
use App\Jobs\ProcessPostUpdate;
use App\Jobs\ProcessPostDelete;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
    // ① 投稿フォームを表示
    public function create()
    {
        // ジャンル一覧を取得
        $genres = Genre::all();
    
        return view('posts.create', compact('genres'));
    }

    // ② 投稿を保存
    public function store(Request $request)
    {
        $validated = $request->validate([
            // ... バリデーションルール ...
            'shop_name' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string|max:1000',
            'image' => 'required|image|max:10240',
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
            'genres' => 'nullable|array', // ★追加
            'genres.*' => 'exists:genres,id', // ★追加
        ]);

        // トランザクション
        $post = DB::transaction(function () use ($request, $validated) {
            $user = Auth::user();

            // ★★★ 修正：ショップ特定ロジック（強化版） ★★★
            $shop = $this->findOrCreateShop($validated['shop_name'], $request->google_place_id, $request->address);

            // ▼▼▼ 追加: ジャンルの保存処理 ▼▼▼
            if (!empty($request->genres)) {
                // syncWithoutDetaching: 既に登録されているジャンルは維持しつつ、新しいものを追加
                // (他のユーザーがつけたタグを消さないため、syncではなくsyncWithoutDetachingを使います)
                $shop->genres()->syncWithoutDetaching($request->genres);
            }
            // ▲▲▲ 追加ここまで ▲▲▲

            // 画像処理
            $imagePath = null;
            if ($request->hasFile('image')) {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($request->file('image'));
                $image->scale(width: 800);
                $encoded = $image->toWebp(quality: 75);
                $fileName = 'uploads/posts/' . Str::random(40) . '.webp';
                if (!file_exists(public_path('uploads/posts'))) mkdir(public_path('uploads/posts'), 0755, true);
                file_put_contents(public_path($fileName), $encoded);
                $imagePath = $fileName;
            }

            // 保存
            $post = new Post();
            $post->shop_id = $shop->id;
            $post->shop_name = $shop->name;
            $post->user_id = $user->id;
            $post->score = $validated['score'];
            $post->comment = $validated['comment'];
            $post->eaten_at = now();
            $post->image_path = $imagePath;

            // ★ポイント計算＆保存
            $points = $post->calculatePoints($shop);
            $post->earned_points = $points;
            $post->save();

            // ★ユーザーにポイント加算 (1 or 2)
            $user->increment('total_score', $points);
            $user->increment('posts_count');

            return $post;
        });

        // Job実行（ラリー判定など）
        ProcessPostSubmission::dispatchAfterResponse($post);

        return redirect()->route('profile.index')->with('success', '投稿しました！');
    }

    // ③ 編集
    public function edit(Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);
        
        // ▼▼▼ 追加: ジャンル一覧を取得 ▼▼▼
        $genres = Genre::all();
        // ▲▲▲ 追加ここまで ▲▲▲

        return view('posts.edit', compact('post', 'genres')); // genresを渡す
    }

    public function update(Request $request, Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);

        $validated = $request->validate([
            // ... バリデーション ...
            'shop_name' => 'required',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string',
            'image' => 'nullable|image|max:10240',
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
            'genres' => 'nullable|array', // ★追加
            'genres.*' => 'exists:genres,id', // ★追加
        ]);

        // 更新前の情報を保持
        $oldShopId = $post->shop_id;
        $oldPoints = $post->earned_points; // 元々持っていたポイント

        DB::transaction(function () use ($request, $post, $validated, $oldPoints) {
            $shop = $this->findOrCreateShop($validated['shop_name'], $request->google_place_id, $request->address);

            // ▼▼▼ 追加: ジャンルの保存処理 (syncWithoutDetachingを使用) ▼▼▼
            if (!empty($request->genres)) {
                // お店のジャンルを更新
                // 編集時は syncWithoutDetaching を使うことで、他の人のタグを残しつつ追加できる
                $shop->genres()->syncWithoutDetaching($request->genres);
            }
            // ▲▲▲ 追加ここまで ▲▲▲

            // 画像処理
            if ($request->hasFile('image')) {
                if ($post->image_path && file_exists(public_path($post->image_path))) {
                    @unlink(public_path($post->image_path));
                }
                $manager = new ImageManager(new Driver());
                $image = $manager->read($request->file('image'));
                $image->scale(width: 800);
                $encoded = $image->toWebp(quality: 75);
                $fileName = 'uploads/posts/' . Str::random(40) . '.webp';
                if (!file_exists(public_path('uploads/posts'))) mkdir(public_path('uploads/posts'), 0755, true);
                file_put_contents(public_path($fileName), $encoded);
                $post->image_path = $fileName;
            }

            $post->shop_id = $shop->id;
            $post->shop_name = $shop->name;
            $post->score = $validated['score'];
            $post->comment = $validated['comment'];

            // ★ポイント再計算
            // 店が変わればポイントが変わる可能性がある（キャンペーン店かどうか）
            $newPoints = $post->calculatePoints($shop);
            $post->earned_points = $newPoints;
            $post->save();

            // ★差分をユーザーに反映
            // 例: 通常店(1pt) -> キャンペーン店(2pt) に変更 = +1pt
            // 例: キャンペーン店(2pt) -> 通常店(1pt) に変更 = -1pt
            // 例: 変更なし = 0
            $diff = $newPoints - $oldPoints;
            if ($diff != 0) {
                $user = Auth::user();
                if ($diff > 0) {
                    $user->increment('total_score', $diff);
                } else {
                    $user->decrement('total_score', abs($diff));
                }
            }
        });

        // Job実行（ラリー判定・ランキング更新）
        ProcessPostUpdate::dispatchAfterResponse($post, $oldShopId);

        return redirect()->route('posts.show', $post)->with('success', '投稿を更新しました！');
    }

    // 削除
    public function destroy(Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);

        $userId = $post->user_id;
        $shopId = $post->shop_id;
        $earnedPoints = $post->earned_points;

        DB::transaction(function () use ($post, $userId, $earnedPoints) {
            // 投稿削除
            $post->delete();

            // ★ユーザーからポイントと投稿数を回収 (1 or 2)
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->decrement('total_score', $earnedPoints);
                $user->decrement('posts_count');
            }
        });

        // Job実行（ラリー剥奪判定・ランキング更新）
        ProcessPostDelete::dispatchAfterResponse($userId, $shopId);

        // ★修正: 削除後はマイページ(profile.index)へリダイレクト
        return redirect()->route('profile.index')->with('success', '投稿を削除しました。');
    }

    // ==========================================
    // ★★★ 追加：ショップ特定・更新の共通ロジック ★★★
    // ==========================================
    private function findOrCreateShop($name, $googlePlaceId, $address)
    {
        $shop = null;

        // 1. Google Place ID で探す（一番確実）
        if ($googlePlaceId) {
            $shop = Shop::where('google_place_id', $googlePlaceId)->first();
        }

        // 2. なければ店名で探す
        if (!$shop) {
            $shop = Shop::where('name', $name)->first();
        }

        // 3. それでもなければ新規作成
        if (!$shop) {
            $shop = Shop::create([
                'name' => $name,
                'google_place_id' => $googlePlaceId,
                'address' => $address,
            ]);
        } else {
            // 4. 既存のお店がある場合、情報が足りなければ埋める（ここが重要！）
            $updateData = [];

            // DBにPlaceIDがない、かつ今回送られてきた場合は更新
            if (empty($shop->google_place_id) && $googlePlaceId) {
                $updateData['google_place_id'] = $googlePlaceId;
            }

            // DBに住所がない、かつ今回送られてきた場合は更新
            if (empty($shop->address) && $address) {
                $updateData['address'] = $address;
            }

            // 更新対象があればアップデート実行
            if (!empty($updateData)) {
                $shop->update($updateData);
            }
        }

        return $shop;
    }
    // 投稿詳細画面
    public function show(Post $post)
    {
        // コメント投稿者(user)情報も含めて取得
        $post->load(['comments.user', 'shop', 'user']);

        return view('posts.show', compact('post'));
    }
}