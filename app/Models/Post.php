<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'shop_id',
        'shop_name', // 👈 ★追加！これがないと保存されません
        'score',
        'comment',
        'image_path',
        'eaten_at'
    ];

    protected $casts = [
        'eaten_at' => 'datetime',
        'score' => 'float', // 👈 追加: 小数として扱う（4.50 ではなく 4.5 になる）
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->likes->where('user_id', $user->id)->isNotEmpty();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest(); // 新しい順
    }

    public function calculatePoints(?Shop $shop = null): int
    {
        // shopが渡されなければリレーションから取得
        $shop = $shop ?? $this->shop;

        if (!$shop) return 1;

        // アクティブなキャンペーンがあるか確認
        // (Campaignモデルの実装に合わせて調整してください)
        $hasCampaign = Campaign::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->exists();

        return $hasCampaign ? 2 : 1;
    }
}