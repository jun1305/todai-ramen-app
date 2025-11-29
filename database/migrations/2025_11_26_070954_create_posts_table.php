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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 投稿した人
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete(); // 食べた店
            $table->string('image_path')->nullable(); // ラーメンの写真（無しでもOK）
            $table->integer('score'); // 点数 (1~5)
            $table->text('comment')->nullable(); // 感想
            $table->dateTime('eaten_at'); // 食べた日時（ランキング集計用）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
