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
        // 入力チェック（画像も追加）
        $validated = $request->validate([
            'shop_name' => 'required',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string',
            'image' => 'required|image|max:10240',
            // ▼▼▼ 追加: 住所などのバリデーション（nullableでOK） ▼▼▼
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
        ]);

        // ▼▼▼ 修正: お店を探す・作るロジック（DailyRamenと同じ賢いロジックに統一） ▼▼▼
        $shop = null;

        // ① Google Place ID があれば、それで探す
        if ($request->google_place_id) {
            $shop = Shop::where('google_place_id', $request->google_place_id)->first();
        }

        // ② なければ、店名で探してみる
        if (!$shop) {
            $shop = Shop::where('name', $validated['shop_name'])->first();
        }

        // ③ それでもなければ、新規作成する
        if (!$shop) {
            $shop = Shop::create([
                'name' => $validated['shop_name'],
                'address' => $request->address, // 住所をShopsテーブルに保存
                'google_place_id' => $request->google_place_id,
            ]);
        }
        // ▲▲▲ 修正ここまで ▲▲▲

        // 2. 投稿を保存
        $post = new \App\Models\Post();
        $post->shop_id = $shop->id;
        $post->shop_name = $validated['shop_name']; // ★追加: Postsテーブルにも店名を保存
        $post->user_id = \Illuminate\Support\Facades\Auth::id(); 
        $post->score = $validated['score'];
        $post->comment = $validated['comment'];
        $post->eaten_at = now(); // またはフォームから受け取るなら $request->eaten_at

        // ★★★ 画像保存処理（圧縮・リサイズ版） ★★★
        if ($request->hasFile('image')) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image'));
            $image->scale(width: 800);
            $encoded = $image->toJpeg(quality: 75);
    
            // ★修正: 保存先を 'uploads/posts/' に統一
            $fileName = 'uploads/posts/' . Str::random(40) . '.jpg';
            
            // ディレクトリがない場合は作成
            if (!file_exists(public_path('uploads/posts'))) {
                mkdir(public_path('uploads/posts'), 0777, true);
            }
            
            $storagePath = public_path($fileName);
            file_put_contents($storagePath, $encoded); 
            $post->image_path = $fileName;
        }

        $post->save();

        // ★★★ ポイント計算 ★★★
        $points = 1; 

        // 投稿した店でキャンペーンやってるか確認
        $hasCampaign = Campaign::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->exists();

        if ($hasCampaign) {
            $points = 2; 
        }

        $post->earned_points = $points; 
        $post->save(); 
        
        \App\Models\User::find(\Illuminate\Support\Facades\Auth::id())->increment('points', $points);

        // 通知処理
        $users = User::where('id', '!=', Auth::id())->get();
        Notification::send($users, new NewPost($post));

        return redirect('/');
    }

    public function destroy(Post $post)
    {
        if (auth()->id() !== $post->user_id) {
            abort(403);
        }
    
        // 画像があれば削除
        if ($post->image_path && file_exists(public_path($post->image_path))) {
            unlink(public_path($post->image_path));
        }
    
        $post->delete();
    
        return back(); 
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
        if (Auth::id() !== $post->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'shop_name' => 'required',
            'score' => 'required|numeric|min:0|max:100',
            'comment' => 'nullable|string',
            'image' => 'nullable|image|max:10240',
            // update時も住所情報があれば受け取る（必須ではない）
            'address' => 'nullable|string',
            'google_place_id' => 'nullable|string',
        ]);

        // 1. 店の更新ロジック（storeと同じく賢く検索・作成）
        // ※ 編集で店名を変えた場合、新しい店として扱うか、既存の店の名前を変えるかは仕様によりますが、
        // 今回は「新しい店名なら新しい店として紐付け直す」のが安全です。
        $shop = null;
        if ($request->google_place_id) {
            $shop = Shop::where('google_place_id', $request->google_place_id)->first();
        }
        if (!$shop) {
            $shop = Shop::where('name', $validated['shop_name'])->first();
        }
        if (!$shop) {
            $shop = Shop::create([
                'name' => $validated['shop_name'],
                'address' => $request->address,
                'google_place_id' => $request->google_place_id,
            ]);
        }

        // 2. データのセット
        $post->shop_id = $shop->id;
        $post->shop_name = $validated['shop_name']; // ★店名も更新
        $post->score = $validated['score'];
        $post->comment = $validated['comment'];

        // ★★★ 画像の差し替え処理 ★★★
        if ($request->hasFile('image')) {
            // A. 古い画像があれば削除
            if ($post->image_path && file_exists(public_path($post->image_path))) {
                unlink(public_path($post->image_path));
            }

            // B. 新しい画像を保存
            $manager = new ImageManager(new Driver());
            $image = $manager->read($request->file('image'));
            $image->scale(width: 800);
            $encoded = $image->toJpeg(quality: 75);

            // ★修正: 保存先を 'uploads/posts/' に統一
            $fileName = 'uploads/posts/' . Str::random(40) . '.jpg';
            
            if (!file_exists(public_path('uploads/posts'))) {
                mkdir(public_path('uploads/posts'), 0777, true);
            }
            
            $storagePath = public_path($fileName);
            file_put_contents($storagePath, $encoded);

            $post->image_path = $fileName;
        }

        $post->save();

        return redirect()->route('profile.index')->with('success', '投稿を更新しました！');
    }
}