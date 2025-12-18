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
    Schema::table('users', function (Blueprint $table) {
        // total_score の後ろあたりに追加。検索用に index もつける
        $table->integer('posts_count')->default(0)->index()->after('total_score');
        $table->integer('completed_rallies_count')->default(0)->index()->after('posts_count');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['posts_count', 'completed_rallies_count']);
    });
}
};
