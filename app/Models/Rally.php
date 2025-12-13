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
}