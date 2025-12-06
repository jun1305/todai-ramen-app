<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
    public function index() {
        // ログイン中のユーザーを取得
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // もしログインしてなかったらログイン画面へ（念の為）
        if (!$user) {
            return redirect()->route('login');
        }
        
        $user->loadCount('posts'); // 投稿数をカウント
    
        // 自分の投稿を取得
        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
    
        return view('profile.index', compact('user', 'posts'));
    }

    public function show($id)
    {
        // ユーザーを探す（いなかったらエラー）
        $user = User::withCount('posts')->findOrFail($id);

        // その人の投稿を取得
        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);

        // 同じ 'profile.index' ビューを使い回す！
        return view('profile.index', compact('user', 'posts'));
    }

    public function updateIcon(Request $request)
    {
        // 1. バリデーション
        $request->validate([
            'icon' => 'required|image|max:2048', // 2MBまで
        ]);
    
        $user = auth()->user();
    
        // 2. アップロードされたファイルデータを取得
        $file = $request->file('icon');
        
        // 3. 保存先ディレクトリの定義（public/profile_icons）
        $dir = 'profile_icons';
        $path = public_path($dir);
    
        // ディレクトリがなければ作成（権限設定含む）
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    
        // 4. 古いアイコンがあれば削除（ゴミを残さない）
        if ($user->icon_path && File::exists(public_path($user->icon_path))) {
            File::delete(public_path($user->icon_path));
        }
    
        // 5. ファイル名を生成（被らないように時間+ID）
        $fileName = time() . '_' . $user->id . '.jpg';
    
        // 6. 画像を直接保存
        // move()ではなく、File::put()を使って「データの中身をそこに書き込む」処理にします
        File::put($path . '/' . $fileName, $file->get());
    
        // 7. データベースの更新
        // ユーザーテーブルのicon_pathカラムを更新
        $user->update(['icon_path' => $dir . '/' . $fileName]);
    
        return response()->json(['status' => 'success']);
    }

    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        $user = auth()->user();
        $user->update(['name' => $request->name]);
    
        return response()->json(['status' => 'success']);
    }

    
}