<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // 👈 追加！

class ProfileController extends Controller
{
    public function index() {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        
        // ★自分のページ（index）はキャッシュせず、常に最新を表示します（ストレス防止）
        
        $user->loadCount(['posts', 'joinedRallies as completed_rallies_count' => function ($query) {
            $query->where('is_completed', true);
        }]);
        
        $user->loadSum('posts', 'earned_points');

        $postPoints = $user->posts_sum_earned_points ?? 0;
        $rallyPoints = ($user->completed_rallies_count ?? 0) * 5;
        
        $totalPoints = $postPoints + $rallyPoints;

        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        return view('profile.index', compact('user', 'posts', 'totalPoints'));
    }

    public function show($id)
    {
        // ==========================================
        // ★ここを高速化（キャッシュ対応）
        // ==========================================
        // ユーザー情報と集計結果（重い処理）を5分間キャッシュします
        $userCacheKey = "profile_user_{$id}";

        $user = Cache::remember($userCacheKey, 60 * 5, function () use ($id) {
            return User::withCount(['posts', 'joinedRallies as completed_rallies_count' => function ($query) {
                    $query->where('is_completed', true);
                }])
                ->withSum('posts', 'earned_points') 
                ->findOrFail($id);
        });
                
        // 計算はPHPで行うので一瞬です（キャッシュされた $user を使うのでDB負荷なし）
        $postPoints = $user->posts_sum_earned_points ?? 0;
        $rallyPoints = ($user->completed_rallies_count ?? 0) * 5;
        
        $totalPoints = $postPoints + $rallyPoints;

        // 投稿リストもページごとにキャッシュするとさらに高速ですが、
        // 「最新の投稿が見たい」需要が高いので、ここはあえてリアルタイム取得にします。
        // （インデックスが効いていれば十分速いです）
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

            // ▼▼▼ 追加: 更新したらキャッシュを削除する ▼▼▼
            // これを忘れると「画像変えたのに他人の画面では古いまま」になります
            Cache::forget("profile_user_{$user->id}");
            // ▲▲▲ 追加ここまで ▲▲▲

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

            // ▼▼▼ 追加: 更新したらキャッシュを削除 ▼▼▼
            Cache::forget("profile_user_{$user->id}");
            // ▲▲▲ 追加ここまで ▲▲▲

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}