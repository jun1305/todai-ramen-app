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
            // この投稿のお店が含まれているラリーだけをチェックすればOK
            if ($shop) {
                $relatedRallies = $user->joinedRallies()
                    ->whereHas('shops', function($q) use ($shop) {
                        $q->where('shops.id', $shop->id);
                    })
                    ->get();

                foreach ($relatedRallies as $rally) {
                    // ★修正: checkAndComplete ではなく syncUserStatus を使う
                    // これにより「本当に条件を満たした瞬間」だけ +5pt されるようになります
                    $rally->syncUserStatus($user);
                }
            }
        });

        // 3. 通知（変更なし）
        $users = User::where('id', '!=', $user->id)->get();
        if ($users->isNotEmpty()) {
            Notification::send($users, new NewPost($this->post));
        }
    }
}