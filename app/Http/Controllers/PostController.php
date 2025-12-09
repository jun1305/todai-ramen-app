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
            $image->scale(width: 800);
    
            // 4. 画質を落としてエンコード（JPEG 75%）
            $encoded = $image->toJpeg(quality: 75);
    
            // 5. 保存（public/uploads フォルダへ直接保存）
            // ランダムなファイル名を生成 (例: uploads/abcdef12345.jpg)
            $fileName = 'uploads/' . Str::random(40) . '.jpg';
            
            // publicディレクトリ内の実パスを取得
            $storagePath = public_path($fileName);
    
            // 保存実行（Storageファサードは使わず、直接ファイルを書き込む）
            // public_path() はプロジェクトのルート/public/ を指す
            file_put_contents($storagePath, $encoded); 
    
            // パスをDB保存用にセット (storage/ をつけない)
            // 例: 'uploads/abcdef12345.jpg'
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

        // ★ 変更点: Postsテーブルにもポイントを保存する
        $post->earned_points = $points; 
        $post->save(); // ここで一緒に保存

        
        \App\Models\User::find(\Illuminate\Support\Facades\Auth::id())->increment('points', $points);
        // ★★★★★★★★★★★★★

        // 1. 自分以外の全ユーザーを取得（自分に通知しても仕方ないので）
        $users = User::where('id', '!=', Auth::id())->get();

        // 2. その人たちに通知を送る（WebPush設定していない人は自動でスキップされます）
        Notification::send($users, new NewPost($post));

        return redirect('/');
    }
    public function destroy(Post $post)
    {
        // 本人の投稿かどうか確認（セキュリティ対策）
        if (auth()->id() !== $post->user_id) {
            abort(403);
        }
    
        // 画像があれば削除する処理（オプション）
        // if ($post->image_path) {
        //     \Storage::disk('public')->delete($post->image_path);
        // }
    
        $post->delete();
    
        return back(); // マイページにリダイレクト
    }
}
