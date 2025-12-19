<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'body' => 'required|max:140', // 140文字制限
        ]);

        $comment = new Comment();
        $comment->post_id = $post->id;
        $comment->user_id = Auth::id();
        $comment->body = $request->body;
        $comment->save();

        // 投稿主以外なら通知を送るなどの処理をここに書いてもOK

        return back()->with('success', 'コメントしました！');
    }

    public function destroy(Comment $comment)
    {
        // 自分のコメントでなければエラー
        if (Auth::id() !== $comment->user_id) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'コメントを削除しました。');
    }
}