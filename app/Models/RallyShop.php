<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RallyShop extends Model
{
    use HasFactory;

    protected $fillable = ['rally_id', 'shop_id'];
}