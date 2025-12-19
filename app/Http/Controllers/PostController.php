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
use Illuminate\Support\Facades\Storage;
use App\Notifications\NewPost; 
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\Rally;
use Illuminate\Support\Facades\DB;
// Jobクラスの読み込み
use App\Jobs\ProcessPostSubmission;
use App\Jobs\ProcessPostUpdate;
use App\Jobs\ProcessPostDelete;

class PostController extends Controller
{
    // ① 投稿フォームを表示する
    public function create()
    {
        return view('posts.create');
    }

    // ② 投稿を保存する
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

        // トランザクションは「保存の成功」だけを担保
        $post = DB::transaction(function () use ($request, $validated) {
            $user = Auth::user();

            // 1. 店の特定・作成（ここは同期でやる必要あり）
            $shop = null;
            if ($request->google_place_id) $shop = Shop::where('google_place_id', $request->google_place_id)->first();
            if (!$shop) $shop = Shop::where('name', $validated['shop_name'])->first();
            if (!$shop) {
                $shop = Shop::create([
                    'name' => $validated['shop_name'],
                    'google_place_id' => $request->google_place_id,
                    'address' => $request->address,
                ]);
            } else {
                if (empty($shop->google_place_id) && $request->google_place_id) {
                    $shop->update([
                        'google_place_id' => $request->google_place_id,
                        'address' => $request->address ?? $shop->address,
                    ]);
                }
            }

            // 2. 画像処理
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

            // 3. 保存
            $post = new Post();
            $post->shop_id = $shop->id;
            $post->shop_name = $shop->name;
            $post->user_id = $user->id;
            $post->score = $validated['score'];
            $post->comment = $validated['comment'];
            $post->eaten_at = now();
            $post->image_path = $imagePath;

            // ポイント計算
            $points = 1;
            $hasCampaign = Campaign::where('shop_id', $shop->id)->where('is_active', true)->exists();
            if ($hasCampaign) $points = 2;
            $post->earned_points = $points;
            
            $post->save();

            // ユーザーの表示用スコアだけ先に更新
            $user->increment('total_score', $points);
            $user->increment('posts_count');

            return $post;
        });

        // ★★★ ここを変更！「レスポンスを返した後にやっておいて！」 ★★★
        // dispatch($post) ではなく dispatchAfterResponse($post) にします
        ProcessPostSubmission::dispatchAfterResponse($post);

        return redirect()->route('profile.index')->with('success', '投稿しました！');
    }

    public function destroy(Post $post)
    {
        if (Auth::id() !== $post->user_id) abort(403);

        // 消す前に必要なデータをメモる
        $userId = $post->user_id;
        $shopId = $post->shop_id;
        $earnedPoints = $post->earned_points;

        // さっさと消す
        $post->delete();

        // ★★★ ここを変更！「あとは裏でよろしく！」 ★★★
        ProcessPostDelete::dispatchAfterResponse($userId, $shopId, $earnedPoints);

        return back()->with('success', '投稿を削除しました。');
    }

    // ③ 編集画面を表示する
    public function edit(Post $post)
    {
        if (Auth::id() !== $post->user_id) {
            abort(403);
        }

        return view('posts.edit', compact('post'));
    }

    // ④ 投稿を更新する
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
            // 店ロジック
            $shop = null;
            if ($request->google_place_id) $shop = Shop::where('google_place_id', $request->google_place_id)->first();
            if (!$shop) $shop = Shop::where('name', $validated['shop_name'])->first();
            if (!$shop) {
                $shop = Shop::create(['name' => $validated['shop_name'], 'address' => $request->address, 'google_place_id' => $request->google_place_id]);
            } else {
                if (empty($shop->google_place_id) && $request->google_place_id) {
                    $shop->update(['google_place_id' => $request->google_place_id, 'address' => $request->address ?? $shop->address]);
                }
            }

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

        // ★★★ ここを変更！「店スコア更新は裏で！」 ★★★
        if ($post->shop_id) {
            ProcessPostUpdate::dispatchAfterResponse($post->shop_id);
        }

        return redirect()->route('profile.index')->with('success', '投稿を更新しました！');
    }
}