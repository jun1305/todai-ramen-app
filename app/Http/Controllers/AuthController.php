<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ① 会員登録画面
    public function showRegister()
    {
        return view('auth.register');
    }

    // ② 会員登録処理
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users', // 名前が被ってないかチェック
            'password' => 'required|string|min:4|confirmed', // パスワード（短めでもOKにしときました）
        ]);

        $user = User::create([
            'name' => $validated['name'],
            // 'email' => ... ←削除
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect('/');
    }

    // ③ ログイン画面
    public function showLogin()
    {
        return view('auth.login');
    }

    // ④ ログイン処理
    public function login(Request $request)
    {
        // 名前とパスワードだけチェック
        $credentials = $request->validate([
            'name' => ['required'],
            'password' => ['required'],
        ]);

        // Auth::attempt は自動的に 'name' カラムを探してくれます
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/');
        }

        return back()->withErrors([
            'name' => '名前またはパスワードが間違っています。',
        ]);
    }

    // ⑤ ログアウト処理
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}