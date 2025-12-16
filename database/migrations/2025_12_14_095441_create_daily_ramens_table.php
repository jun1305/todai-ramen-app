<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_ramens', function (Blueprint $table) {
            $table->id();
            // Google Maps連携用
            $table->string('shop_name');       // 店名（Googleから取得）
            $table->string('google_place_id')->nullable(); // Googleの場所ID
            
            // 投稿内容
            $table->string('menu_name')->nullable(); // 食べたもの
            $table->text('comment')->nullable();     // 会長の一言
            $table->string('image_path');            // 写真
            $table->date('eaten_at');                // 食べた日
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_ramens');
    }
};
