<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyRamen extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',      // 追加
        'shop_name',
        'menu_name',
        'comment',
        'image_path',
        'eaten_at',
    ];

    protected $casts = [
        'eaten_at' => 'datetime',
    ];

    // ★ショップとのリレーション（繋がり）を定義
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * 住所を取得するアクセサ（修正版）
     * 自身のテーブルにはもう住所がないので、紐付いている shop から取る
     */
    public function getAddressAttribute()
    {
        return $this->shop ? $this->shop->address : null;
    }

    /**
     * 短い住所のアクセサ（修正版）
     * $this->address を呼べば、上の getAddressAttribute 経由でShopの住所が取れる
     */
    public function getShortAddressAttribute()
    {
        $address = $this->address; // 上で作ったアクセサ経由で取得

        if (!$address) {
            return 'エリア不明';
        }

        if (preg_match('/(.+?[都道府県])(.+?[市区町村])/u', $address, $matches)) {
            return $matches[0];
        }

        return mb_strimwidth($address, 0, 20, '...');
    }

    /**
     * GoogleマップURLのアクセサ（修正版）
     * これもShopの情報を優先して使う
     */
    public function getMapUrlAttribute()
    {
        // 紐付いているショップがあれば、そのMapURLを使う
        if ($this->shop) {
            return $this->shop->map_url; // Shopモデルにある map_url アクセサを使う
        }
        
        // 万が一ショップがない場合は店名検索
        return "https://www.google.com/maps/search/?api=1&query=" . urlencode($this->shop_name . " ラーメン");
    }
}