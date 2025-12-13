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
        // ① ラリー本体（クエスト）
        Schema::create('rallies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 作成者
            $table->string('title'); // ラリー名
            $table->text('description')->nullable(); // 説明文
            $table->timestamps();
        });

        // ② ラリーの中身（どの店が含まれるか）
        Schema::create('rally_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rally_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // 念のため、同じラリーに同じ店を重複登録できないようにする
            $table->unique(['rally_id', 'shop_id']); 
        });

        // ③ ユーザーの参加状況（誰がどのラリーに挑戦中か）
        Schema::create('user_rallies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 挑戦者
            $table->foreignId('rally_id')->constrained()->onDelete('cascade'); // 挑戦するラリー
            $table->boolean('is_completed')->default(false); // 制覇したか？
            $table->timestamp('completed_at')->nullable(); // 制覇した日
            $table->timestamps();

            // 1人が同じラリーに二重参加できないようにする
            $table->unique(['user_id', 'rally_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_rallies');
        Schema::dropIfExists('rally_shops');
        Schema::dropIfExists('rallies');
    }
};