<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class ProcessPostSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle(): void
    {
        // 念のためデータが存在するか確認
        if (!$this->post || !$this->post->user) return;

        $user = $this->post->user;
        $shop = $this->post->shop;

        DB::transaction(function () use ($user, $shop) {
            // 1. 店舗スコア更新
            if ($shop) {
                $shop->updateRankingData();
            }

            // 2. ラリー制覇判定
            $relatedRallies = $user->joinedRallies()
                ->whereHas('shops', function($q) use ($shop) {
                    $q->where('shops.id', $shop->id);
                })
                ->get();

            foreach ($relatedRallies as $rally) {
                $rally->checkAndComplete($user);
            }
        });

        // 3. 通知（トランザクションの外でOK）
        // 全員に送ると重いので、必要に応じて絞るか、ここもさらに別のJobに分けるのがベスト
        $users = User::where('id', '!=', $user->id)->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new NewPost($this->post));
        }
    }
}