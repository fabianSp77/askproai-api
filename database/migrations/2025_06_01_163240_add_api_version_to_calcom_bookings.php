<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiVersionToCalcomBookings extends Migration
{
    public function up()
    {
        Schema::table('calcom_bookings', function (Blueprint $table) {
            $table->enum('api_version', ['v1', 'v2'])->default('v1');
        });
    }

    public function down()
    {
        Schema::table('calcom_bookings', function (Blueprint $table) {
            $table->dropColumn('api_version');
        });
    }
}
