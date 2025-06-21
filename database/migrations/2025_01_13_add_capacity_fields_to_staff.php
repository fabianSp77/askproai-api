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
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                if (!Schema::hasColumn('staff', 'max_daily_appointments')) {
                    $table->integer('max_daily_appointments')->default(8);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('max_daily_appointments');
        });
    }
};