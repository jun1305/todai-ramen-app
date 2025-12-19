<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    // ▼▼▼ 修正: 住所とGoogle IDを追加 ▼▼▼
    protected $fillable = [
        'name',
        'address',           // 👈 これありますか？
        'google_place_id',   // 👈 これありますか？
        'posts_count',
        'posts_avg_score',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function latestPost()
    {
        return $this->hasOne(Post::class)->latestOfMany('eaten_at');
    }

    public function rallies()
    {
        return $this->belongsToMany(Rally::class, 'rally_shops');
    }

    // ▼▼▼ 追加: GoogleマップのURLを自動生成する機能 ▼▼▼
    // 使い方: $shop->map_url
    public function getMapUrlAttribute()
    {
        if ($this->google_place_id) {
            return "https://www.google.com/maps/search/?api=1&query=Google&query_place_id={$this->google_place_id}";
        }
        // IDがない場合は店名と住所で検索させるリンク
        return "https://www.google.com/maps/search/?api=1&query=" . urlencode($this->name . " " . $this->address);
    }
    
    public function getShortAddressAttribute()
    {
        $address = $this->address;

        if (!$address) {
            return ''; // 住所がない場合は空文字
        }

        // "東京都港区" のような「都道府県+市区町村」だけを抜き出す正規表現
        if (preg_match('/(.+?[都道府県])(.+?[市区町村])/u', $address, $matches)) {
            return $matches[0];
        }

        // マッチしない場合は、先頭から9文字くらいを適当に返す
        return mb_strimwidth($address, 0, 9, '...');
    }

    public function updateRankingData()
    {
        $this->posts_count = $this->posts()->count();
        // 投稿があれば平均を計算、なければ0
        $this->posts_avg_score = $this->posts()->exists() 
            ? $this->posts()->avg('score') 
            : 0;
        
        $this->save();
    }
}