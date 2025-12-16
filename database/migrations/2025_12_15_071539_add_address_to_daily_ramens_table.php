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
            // shop_nameの後ろに address カラムを追加（空でもOKなように nullable にしておく）
            $table->string('address')->nullable()->after('shop_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('daily_ramens', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
