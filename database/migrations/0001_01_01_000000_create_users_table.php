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
        // ① ユーザーテーブル（名前とパスワードのみ）
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 名前（ID代わり）
            $table->string('password');
            $table->integer('points')->default(0); // ポイント
            $table->rememberToken();
            $table->timestamps();
        });


        // ③ セッションテーブル
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
