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
            'name' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:4|confirmed',
            'secret_answer' => 'required|string|max:255', // 追加
        ], [
            'name.required' => '名前を入力してください。',
            'name.unique' => 'その名前は既に使用されています。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは4文字以上で入力してください。',
            'password.confirmed' => '確認用パスワードと一致しません。',
            'secret_answer.required' => '秘密の質問の答えを入力してください。', // 追加
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            // 答えもハッシュ化して保存（セキュリティ対策）
            'secret_answer' => Hash::make($validated['secret_answer']),
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

        // ▼▼▼ 追加: チェックボックスの値を取得（チェックされていれば true） ▼▼▼
        $remember = $request->boolean('remember');

        // ▼▼▼ 修正: 第2引数に $remember を渡す ▼▼▼
        if (Auth::attempt($credentials, $remember)) {
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

    // ⑥ パスワード忘れた画面（ユーザー名と秘密の質問入力）
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // ⑦ 秘密の質問の確認処理
    public function verifySecretAnswer(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'secret_answer' => 'required',
        ]);

        // 名前でユーザーを探す
        $user = User::where('name', $request->name)->first();

        // ユーザーがいない、または答えが一致しない場合
        // Hash::check(入力された答え, データベースのハッシュ) で比較
        if (!$user || !Hash::check($request->secret_answer, $user->secret_answer)) {
            return back()->withErrors([
                'error' => '名前または秘密の質問の答えが間違っています。',
            ]);
        }

        // 正解したら、セッションに一時的にユーザーIDを保存してリセット画面へ
        session(['reset_user_id' => $user->id]);
        return redirect()->route('password.reset.form');
    }

    // ⑧ 新しいパスワード入力画面
    public function showResetForm()
    {
        // セッションにIDがない（正規の手順を踏んでいない）場合は弾く
        if (!session()->has('reset_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.reset-password');
    }

    // ⑨ パスワード更新処理
    public function resetPassword(Request $request)
    {
        if (!session()->has('reset_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ], [
            'password.required' => '新しいパスワードを入力してください。',
            'password.min' => 'パスワードは4文字以上で入力してください。',
            'password.confirmed' => '確認用パスワードと一致しません。',
        ]);

        $user = User::find(session('reset_user_id'));
        
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // セッション削除
        session()->forget('reset_user_id');

        return redirect()->route('login')->with('status', 'パスワードを変更しました。ログインしてください。');
    }
}