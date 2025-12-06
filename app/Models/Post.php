<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Post extends Model
{
    use HasFactory;
    /** @use HasFactory<\Database\Factories\PostFactory> */
    protected $fillable = [
        'user_id',
        'shop_id',
        'score',
        'comment',
        'image_path',
        'eaten_at'
    ];

    protected $casts = [
        'eaten_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    // いいねリレーション
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // 特定のユーザーがいいね済みかチェックする便利機能
    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->likes->where('user_id', $user->id)->isNotEmpty();
    }
}
