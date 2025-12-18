<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function index() {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');
        
        // 変数 $totalPoints の作成を削除！
    
        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        // compact から 'totalPoints' を削除
        return view('profile.index', compact('user', 'posts'));
    }

    public function show($id)
    {
        // ==========================================
        // ★ここも爆速化
        // ==========================================
        // withCount も不要です！
        // findOrFail するだけで、保存済みのカウント情報も全部取れます。
        
        $user = User::findOrFail($id);
                
        

        $posts = $user->posts()->with('shop')->latest('eaten_at')->paginate(10);
        
        return view('profile.index', compact('user', 'posts'));
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

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}