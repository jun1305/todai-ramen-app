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
    Schema::table('daily_ramens', function (Blueprint $table) {
        // shop_id を追加（Shopsテーブルと紐付け）
        // nullableにしておき、あとで既存データにIDを入れる猶予を作ります
        $table->foreignId('shop_id')->nullable()->constrained('shops')->after('id');
        
        // 不要になるカラムを削除
        $table->dropColumn(['address', 'google_place_id']);
    });
}

public function down()
{
    Schema::table('daily_ramens', function (Blueprint $table) {
        // 元に戻す処理
        $table->dropForeign(['shop_id']);
        $table->dropColumn('shop_id');
        $table->string('address')->nullable();
        $table->string('google_place_id')->nullable();
    });
}
};
