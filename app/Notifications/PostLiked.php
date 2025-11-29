<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\User; // 追加
use App\Models\Post; // 追加

class PostLiked extends Notification
{
    use Queueable;

    public $liker; // いいねした人
    public $post;  // いいねされた投稿

    public function __construct(User $liker, Post $post)
    {
        $this->liker = $liker;
        $this->post = $post;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // データベースに保存する設定
    }

    public function toArray(object $notifiable): array
    {
        return [
            'liker_id' => $this->liker->id,
            'liker_name' => $this->liker->name,
            'post_id' => $this->post->id,
            'post_comment' => $this->post->comment,
            'message' => $this->liker->name . 'さんがあなたの投稿にいいねしました！', 
        ];
    }
}