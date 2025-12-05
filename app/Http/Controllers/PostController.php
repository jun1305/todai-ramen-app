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
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'image' => 'nullable|image|max:10240', // 10MBまでの画像
        ]);

        // 1. 店が存在するかチェック（無ければ作る！）
        $shop = \App\Models\Shop::firstOrCreate(
            ['name' => $validated['shop_name']]
        );

        // 2. 投稿を保存
        $post = new \App\Models\Post();
        $post->shop_id = $shop->id;
        $post->user_id = \Illuminate\Support\Facades\Auth::id(); 
        $post->score = $validated['score'];
        $post->comment = $validated['comment'];
        $post->eaten_at = now();

        // ★★★ 画像保存処理（圧縮・リサイズ版） ★★★
        if ($request->hasFile('image')) {
            // 1. 画像処理マネージャーを起動 (GDドライバー使用)
            $manager = new ImageManager(new Driver());
            
            // 2. アップロードされた画像を読み込む
            $image = $manager->read($request->file('image'));

            // 3. リサイズ（横幅800px、縦は比率維持）
            // スマホ写真は横幅3000pxとかあるので、これで劇的に軽くなります
            $image->scale(width: 800);

            // 4. 画質を落としてエンコード（JPEG 75%）
            // 見た目はほぼ変わらず、ファイルサイズは1/10くらいになります
            $encoded = $image->toJpeg(quality: 75);

            // 5. 保存（storage/app/public/posts フォルダへ）
            // ランダムなファイル名を生成 (例: posts/abcdef12345.jpg)
            $fileName = 'posts/' . Str::random(40) . '.jpg';
            
            // 保存実行
            Storage::disk('public')->put($fileName, $encoded);

            // パスをDB保存用にセット
            $post->image_path = $fileName;
        }
        // ★★★★★★★★★★★★★★★★★★★


        $post->save();

        // ★★★ ポイント計算 ★★★
        $points = 1; // 基本は1ポイント

        // 投稿した店でキャンペーンやってるか確認
        $hasCampaign = Campaign::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->exists();

        if ($hasCampaign) {
            $points = 2; // キャンペーン中なら2ポイント
        }

        // ID:1のユーザーに加算（仮）
        \App\Models\User::find(\Illuminate\Support\Facades\Auth::id())->increment('points', $points);
        // ★★★★★★★★★★★★★

        return redirect('/');
    }
}
