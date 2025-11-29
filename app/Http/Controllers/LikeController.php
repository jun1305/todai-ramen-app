<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use App\Notifications\PostLiked;

class LikeController extends Controller
{
    public function toggle(Post $post)
    {
        $user = Auth::user();

        // 既にいいねしてたら削除（解除）
        if ($post->isLikedBy($user)) {
            $post->likes()->where('user_id', $user->id)->delete();
            $status = 'removed';
        } 
        // まだなら作成（いいね）
        else {
            $post->likes()->create(['user_id' => $user->id]);
            $status = 'added';
        }

        if ($post->user_id !== $user->id) {
            $post->user->notify(new PostLiked($user, $post));
        }

        // 最新のいいね数を返す（画面の数字を更新するため）
        return response()->json([
            'status' => $status,
            'count' => $post->likes()->count(),
        ]);
    }
}