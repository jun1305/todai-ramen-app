<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = ['name']; // 勝手に保存していい項目（これ重要！）

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class)->latestOfMany('eaten_at');
    }

    // ▼▼▼ 追加: この店が含まれているラリー ▼▼▼
    public function rallies()
    {
        return $this->belongsToMany(Rally::class, 'rally_shops');
    }
}
