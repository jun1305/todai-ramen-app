<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPostDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $shopId;
    protected $earnedPoints;
    // Postモデルは既に削除されているので、必要なIDと値だけを受け取る

    public function __construct($userId, $shopId, $earnedPoints)
    {
        $this->userId = $userId;
        $this->shopId = $shopId;
        $this->earnedPoints = $earnedPoints;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $shop = Shop::find($this->shopId);

        if (!$user) return;

        DB::transaction(function () use ($user, $shop) {
            // 1. ポイントと投稿数の回収
            $user->decrement('total_score', $this->earnedPoints);
            $user->decrement('posts_count');

            // 2. 店舗スコアの更新
            if ($shop) {
                $shop->updateRankingData();
            }

            // 3. ラリー剥奪チェック（激重処理）
            // この店が含まれている「達成済み」ラリーを探す
            $potentiallyAffectedRallies = $user->joinedRallies()
                ->wherePivot('is_completed', true)
                ->whereHas('shops', function($q) {
                    $q->where('shops.id', $this->shopId);
                })
                ->with('shops')
                ->get();

            foreach ($potentiallyAffectedRallies as $rally) {
                // 有効な投稿数を数え直す
                $validPostsCount = $user->posts()
                    ->whereIn('shop_id', $rally->shops->pluck('id'))
                    ->where('eaten_at', '>=', $rally->created_at)
                    ->distinct('shop_id')
                    ->count();

                // 足りなくなってたら剥奪
                if ($validPostsCount < $rally->shops->count()) {
                    $user->joinedRallies()->updateExistingPivot($rally->id, [
                        'is_completed' => false,
                        'completed_at' => null,
                    ]);
                    $user->decrement('total_score', 5);
                    $user->decrement('completed_rallies_count');
                }
            }
        });
    }
}