<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPostUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;
    protected $oldShopId;

    public function __construct(Post $post, ?int $oldShopId = null)
    {
        $this->post = $post;
        $this->oldShopId = $oldShopId;
    }

    public function handle(): void
    {
        if (!$this->post || !$this->post->user) return;

        $user = $this->post->user;
        $newShop = $this->post->shop;

        DB::transaction(function () use ($user, $newShop) {
            
            // 1. 店舗ランキング更新（新・旧）
            if ($newShop) {
                $newShop->updateRankingData();
            }
            if ($this->oldShopId && (!$newShop || $this->oldShopId !== $newShop->id)) {
                $oldShop = Shop::find($this->oldShopId);
                if ($oldShop) $oldShop->updateRankingData();
            }

            // 2. ラリー状態の同期（Sync）
            // 影響を受ける可能性のあるラリーを全て取得
            $ralliesToCheck = collect();

            // 新しい店を含むラリー
            if ($newShop) {
                $ralliesToCheck = $ralliesToCheck->merge(
                    $user->joinedRallies()->whereHas('shops', fn($q) => $q->where('shops.id', $newShop->id))->get()
                );
            }
            // 古い店を含むラリー（剥奪の可能性）
            if ($this->oldShopId) {
                $ralliesToCheck = $ralliesToCheck->merge(
                    $user->joinedRallies()->whereHas('shops', fn($q) => $q->where('shops.id', $this->oldShopId))->get()
                );
            }

            // IDでユニークにしてから、それぞれの状態を同期
            $ralliesToCheck->unique('id')->each(function ($rally) use ($user) {
                $rally->syncUserStatus($user); // ここで+5/-5の判断が行われる
            });
        });
    }
}