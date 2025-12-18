<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('shops', function (Blueprint $table) {
        // 投稿数（整数）
        $table->integer('posts_count')->default(0)->index();
        // 平均点（小数：全体3桁、小数部2桁 例: 9.99 / 99.9）
        // ※0.00〜5.00 とかなら 3,2 でもいいですが、余裕を見て double か decimal で
        $table->double('posts_avg_score', 8, 2)->default(0)->index();
    });
}

public function down()
{
    Schema::table('shops', function (Blueprint $table) {
        $table->dropColumn(['posts_count', 'posts_avg_score']);
    });
}
};
