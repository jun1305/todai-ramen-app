<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
}