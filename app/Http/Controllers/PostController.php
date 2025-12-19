<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Shop;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessPostSubmission;
use App\Jobs\ProcessPostUpdate;
use App\Jobs\ProcessPostDelete;

class PostController extends Controller
{
    // ① 投稿フォームを表示
    public function create()
    {
        return view('posts.create');
    }

    // ② 投稿を保存
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string|max:1000',
            'image' => 'required|image|max:10240',
            'address' => 'nullable|string|max:255',
            'google_place_id' => 'nullable|string|max:255',
        ]);

        // トランザクション
        $post = DB::transaction(function () use ($request, $validated) {
            $user = Auth::user();

            // ★★★ 修正：ショップ特定ロジック（強化版） ★★★
            $shop = $this->findOrCreateShop($validated['shop_name'], $request->google_place_id, $request->address);

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

            $points = 1;
            $hasCampaign = Campaign::where('shop_id', $shop->id)->where('is_active', true)->exists();
            if ($hasCampaign) $points = 2;
            $post->earned_points = $points;
            
            $post->save();

            $user->increment('total_score', $points);
            $user->increment('posts_count');

            return $post;
        });

        // ジョブ実行（レスポンス後）
        ProcessPostSubmission::dispatchAfterResponse($post);

        return redirect()->route('profile.index')->with('success', '投稿しました！');
    }

    // ③ 編集
    public function edit(Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);
        return view('posts.edit', compact('post'));
    }

    // ④ 更新
    public function update(Request $request, Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);
        
        $validated = $request->validate([
            'shop_name' => 'required',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string',
            'image' => 'nullable|image|max:10240',
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $post, $validated) {
            
            // ★★★ 修正：ショップ特定ロジック（強化版） ★★★
            // 名前が変わっていたら新しい店になる可能性があるので、ここでも同じロジックを通す
            $shop = $this->findOrCreateShop($validated['shop_name'], $request->google_place_id, $request->address);

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
            $post->save();
        });

        if ($post->shop_id) {
            ProcessPostUpdate::dispatchAfterResponse($post->shop_id);
        }

        return redirect()->route('profile.index')->with('success', '投稿を更新しました！');
    }

    // 削除（変更なし）
    public function destroy(Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);
        $userId = $post->user_id;
        $shopId = $post->shop_id;
        $earnedPoints = $post->earned_points;
        $post->delete();
        ProcessPostDelete::dispatchAfterResponse($userId, $shopId, $earnedPoints);
        return back()->with('success', '投稿を削除しました。');
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