<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rally extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'description'];

    // 作成者（User）
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ラリーに含まれるお店（Shop）
    // 多対多のリレーション: ralliesテーブルとshopsテーブルをrally_shops経由で繋ぐ
    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'rally_shops')
                    ->withTimestamps();
    }

    // このラリーに挑戦しているユーザー（User）
    // 多対多のリレーション: ralliesテーブルとusersテーブルをuser_rallies経由で繋ぐ
    public function challengers()
    {
        return $this->belongsToMany(User::class, 'user_rallies')
                    ->withPivot('is_completed', 'completed_at') // 中間テーブルのカラムも取得
                    ->withTimestamps();
    }

    public function syncUserStatus(User $user)
    {
        // 1. ラリー参加状況を確認
        $pivot = $user->joinedRallies()->where('rally_id', $this->id)->first()?->pivot;
        if (!$pivot) {
            return false; // 参加していない
        }

        // 2. このラリーに必要な店舗IDを全取得
        $requiredShopIds = $this->shops()->pluck('shops.id');
        
        // ★重要: 店舗が1つも登録されていないラリーは、絶対に達成させない！
        // これがないと「0件中0件行った＝達成」になってしまうバグを防ぐ
        if ($requiredShopIds->isEmpty()) {
            return false;
        }

        // 3. ユーザーが訪問済みの店舗IDを取得
        $visitedShopIds = $user->posts()
            ->whereIn('shop_id', $requiredShopIds) // このラリー対象店に絞る
            ->where('eaten_at', '>=', $this->created_at) // 日付制限が必要ならここに追加
            ->pluck('shop_id')
            ->unique(); // 同じ店に何度行っても「1店舗」とカウント

        // 4. 判定：「必要な店のID」から「行った店のID」を引く
        // 残りが空っぽなら、全部行ったということ
        $remainingShopIds = $requiredShopIds->diff($visitedShopIds);
        $isCompletedNow = $remainingShopIds->isEmpty();

        // 5. DBの現在のステータスと比較
        $wasCompleted = (bool)$pivot->is_completed;

        // 6. 状態が変わった場合のみ更新処理
        if ($isCompletedNow && !$wasCompleted) {
            // 【未達成 → 達成】
            $user->joinedRallies()->updateExistingPivot($this->id, [
                'is_completed' => true,
                'completed_at' => now(),
            ]);
            
            // ポイント加算 (+5)
            $user->increment('total_score', 5);
            $user->increment('completed_rallies_count');

            return true; // 達成した

        } elseif (!$isCompletedNow && $wasCompleted) {
            // 【達成 → 未達成】（編集でお店が変わったり、ラリー条件が変わった場合）
            $user->joinedRallies()->updateExistingPivot($this->id, [
                'is_completed' => false,
                'completed_at' => null,
            ]);

            // ポイント没収 (-5)
            $user->decrement('total_score', 5);
            $user->decrement('completed_rallies_count');
            
            return false; // 未達成に戻った
        }

        return $isCompletedNow; // 変化なし
    }
    
    // 古いメソッドは互換性のため残すか、削除して呼び出し元を変える
    public function checkAndComplete(User $user) {
        return $this->syncUserStatus($user);
    }

    public function likes()
    {
        // ここも 'rally_likes' を明示的に指定します
        return $this->belongsToMany(User::class, 'rally_likes', 'rally_id', 'user_id');
    }

    public function isLikedBy($user)
    {
        if (!$user) return false;
        // 修正: ここでの likes() 呼び出しは上記のメソッドを使うのでOK
        return $this->likes()->where('rally_likes.user_id', $user->id)->exists();
    }

}