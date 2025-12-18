<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
// Cacheファサードは不要になったので削除してOK

class ProfileController extends Controller
{
    public function index() {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        // 1. カウント情報の取得
        // 投稿数(posts_count)と、制覇ラリー数(completed_rallies_count)を取得
        $user->loadCount([
            'posts', 
            'joinedRallies as completed_rallies_count' => function ($query) {
                $query->where('is_completed', true);
            }
        ]);
        
        // 2. 合計ポイントの取得
        // ★計算不要！カラムから直接取得（爆速）
        $totalPoints = $user->total_score;

        // 3. 投稿リスト取得
        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        return view('profile.index', compact('user', 'posts', 'totalPoints'));
    }

    public function show($id)
    {
        // 他人のプロフィール表示
        // キャッシュを使わずとも、インデックスが効いていれば十分高速です
        
        $user = User::withCount([
                'posts', 
                'joinedRallies as completed_rallies_count' => function ($query) {
                    $query->where('is_completed', true);
                }
            ])
            ->findOrFail($id);
                
        // ★ここもカラムから直接取得
        $totalPoints = $user->total_score;

        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        return view('profile.index', compact('user', 'posts', 'totalPoints'));
    }

    public function updateIcon(Request $request)
    {
        $request->validate([
            'icon' => 'required|image|max:2048', 
        ]);

        try {
            $user = Auth::user();
            $file = $request->file('icon');
            
            $dir = 'profile_icons';
            $path = public_path($dir);

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            if ($user->icon_path && File::exists(public_path($user->icon_path))) {
                File::delete(public_path($user->icon_path));
            }

            $fileName = time() . '_' . $user->id . '.jpg';
            $file->move($path, $fileName);

            $user->icon_path = $dir . '/' . $fileName;
            $user->save();

            // ★キャッシュを使っていないので、削除処理(forget)も不要！
            
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
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
            
            $user->name = $request->name;
            $user->save();

            // ★キャッシュを使っていないので、削除処理(forget)も不要！

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}