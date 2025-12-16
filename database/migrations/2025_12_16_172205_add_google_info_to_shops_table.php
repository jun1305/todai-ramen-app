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
        // nameの後ろに住所とGoogle IDを追加
        $table->string('address')->nullable()->after('name');
        $table->string('google_place_id')->nullable()->after('address');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('shops', function (Blueprint $table) {
        $table->dropColumn(['address', 'google_place_id']);
    });
}
};
