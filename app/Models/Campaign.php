<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    // ↓↓↓ ★これを追加してください★ ↓↓↓
    protected $fillable = [
        'shop_id',
        'title',
        'content',
        'is_active',
        // 'multiplier', // もし今後使うなら
        // 'starts_at',  // もし今後使うなら
        // 'ends_at',    // もし今後使うなら
    ];
    // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}