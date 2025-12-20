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

    public function __construct($userId, $shopId)
    {
        $this->userId = $userId;
        $this->shopId = $shopId;
        // ポイント回収はControllerで終わっているので、ここでは不要
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $shop = Shop::find($this->shopId);

        if (!$user) return;

        DB::transaction(function () use ($user, $shop) {
            // 1. 店舗ランキング更新
            if ($shop) {
                $shop->updateRankingData();
            }

            // 2. ラリー状態の同期（Sync）
            // この店が含まれていたラリーのみチェックすればOK
            $ralliesToCheck = $user->joinedRallies()
                ->whereHas('shops', fn($q) => $q->where('shops.id', $this->shopId))
                ->get();

            foreach ($ralliesToCheck as $rally) {
                $rally->syncUserStatus($user); // 投稿が消えたことで未達成になれば -5pt される
            }
        });
    }
}