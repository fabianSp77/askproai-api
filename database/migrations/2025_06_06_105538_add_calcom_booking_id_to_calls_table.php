<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('calls', 'calcom_booking_id')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->string('calcom_booking_id')->nullable()->after('raw_data');
            });
        }
    }

    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('calcom_booking_id');
        });
    }
};
