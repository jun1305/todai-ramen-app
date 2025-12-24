<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $fillable = ['name', 'slug'];

    // このジャンルに属するお店
    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'genre_shop');
    }
}