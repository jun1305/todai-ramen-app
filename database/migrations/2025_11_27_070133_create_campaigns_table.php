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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
        $table->foreignId('shop_id')->constrained(); // 店ID
        $table->string('title'); // 「用心棒」など
        $table->text('content'); // 「ポイント2倍！」など
        $table->boolean('is_active')->default(true); // 有効スイッチ
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
