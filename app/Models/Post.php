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
}