<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
// ▼WebPush用の読み込みを追加
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewPost extends Notification
{
    use Queueable;

    protected $post;

    // 投稿データを受け取る
    public function __construct($post)
    {
        $this->post = $post;
    }

    // 送信手段にWebPushを指定
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    // 通知の具体的な中身（スマホに届く文字）
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('🍜 新着ラーメン投稿！')
            ->body($this->post->user->name . "さんが「" . $this->post->shop->name . "」に行きました！")
            ->icon('/images/icon-192.png') // アプリアイコン
            ->action('詳細を見る', '/'); // タップしたときの飛び先（詳細ページなど）
            // ->data(['id' => $notification->id]) // 必要ならデータを持たせる
    }
}