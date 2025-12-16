<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log; 

class ProfileController extends Controller
{
    public function index() {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        // 1. データ取得（ここはそのまま）
        $user->loadCount(['posts', 'joinedRallies as completed_rallies_count' => function ($query) {
            $query->where('is_completed', true);
        }]);
        
        $user->loadSum('posts', 'earned_points');

        // ▼▼▼ 追加: ここでポイントを合算する ▼▼▼
        $postPoints = $user->posts_sum_earned_points ?? 0; // 投稿のポイント
        $rallyPoints = ($user->completed_rallies_count ?? 0) * 5; // ラリー制覇ボーナス（1つ5pt）
        
        $totalPoints = $postPoints + $rallyPoints; // 合計ポイント
        // ▲▲▲ 追加ここまで ▲▲▲

        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        // compactに 'totalPoints' を追加してビューに渡す
        return view('profile.index', compact('user', 'posts', 'totalPoints'));
    }

    public function show($id)
    {
        // 1. ユーザー情報の取得（制覇ラリー数と投稿ポイント合計も一緒に取る）
        $user = User::withCount(['posts', 'joinedRallies as completed_rallies_count' => function ($query) {
                    $query->where('is_completed', true);
                }])
                ->withSum('posts', 'earned_points') 
                ->findOrFail($id);
                
        // ▼▼▼ 追加: ポイント計算ロジック（indexメソッドと同じもの） ▼▼▼
        $postPoints = $user->posts_sum_earned_points ?? 0;
        $rallyPoints = ($user->completed_rallies_count ?? 0) * 5;
        
        $totalPoints = $postPoints + $rallyPoints;
        // ▲▲▲ 追加ここまで ▲▲▲

        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        // compact に 'totalPoints' を追加
        return view('profile.index', compact('user', 'posts', 'totalPoints'));
    }

    public function updateIcon(Request $request)
    {
        // 1. バリデーション
        $request->validate([
            'icon' => 'required|image|max:2048', 
        ]);

        // エラー捕捉開始
        try {
            $user = Auth::user();
            $file = $request->file('icon');
            
            // 2. 保存先ディレクトリ
            $dir = 'profile_icons';
            $path = public_path($dir);

            // ディレクトリ作成
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            // 3. 古いアイコン削除
            if ($user->icon_path && File::exists(public_path($user->icon_path))) {
                File::delete(public_path($user->icon_path));
            }

            // 4. ファイル名生成
            $fileName = time() . '_' . $user->id . '.jpg';

            // 5. 画像保存（修正箇所：moveメソッドを使用するのが確実です）
            $file->move($path, $fileName);

            // 6. データベース更新
            // ★Userモデルの $fillable に 'icon_path' が必要です
            $user->icon_path = $dir . '/' . $fileName;
            $user->save(); // update()よりsave()の方が確実な場合があります

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            // エラーが起きたらログに残し、ブラウザにエラー内容を返す
            Log::error($e);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $user = Auth::user();
            
            // Userモデルの $fillable に 'name' があるか確認してください
            $user->name = $request->name;
            $user->save();

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}