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

    public function checkAndComplete(User $user)
    {
        // 1. そもそも参加しているか確認
        // (pivotデータを取り出す)
        $pivot = $this->challengers()->where('user_id', $user->id)->first()?->pivot;

        if (!$pivot) {
            return false; // 参加してない
        }

        // 2. 既に制覇済みなら何もしない（負荷軽減）
        if ($pivot->is_completed) {
            return true;
        }

        // 3. 進捗を計算
        $targetShopIds = $this->shops->pluck('id');
        
        // ユーザーの有効な投稿数をカウント（店舗IDで重複除外）
        $conqueredCount = $user->posts()
            ->whereIn('shop_id', $targetShopIds)
            ->where('eaten_at', '>=', $this->created_at) // ラリー作成日以降
            ->distinct('shop_id')
            ->count('shop_id');

        $totalShops = $this->shops->count();

        // 4. 判定＆更新
        if ($totalShops > 0 && $conqueredCount >= $totalShops) {
            // 制覇！更新する
            $this->challengers()->updateExistingPivot($user->id, [
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            $user->increment('total_score', 5);
            $user->increment('completed_rallies_count');

            return true; // 新たに制覇した
        }

        return false; // まだ
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