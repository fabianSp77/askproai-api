<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            // Nur die absolut notwendigen Spalten hinzufügen
            $table->boolean('active')->default(true)->after('company_id');
            $table->integer('default_duration_minutes')->default(30)->after('active');
            $table->boolean('is_online_bookable')->default(true)->after('default_duration_minutes');
            $table->integer('min_staff_required')->default(1)->after('is_online_bookable');
            $table->integer('buffer_time_minutes')->default(0)->after('min_staff_required');
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'active',
                'default_duration_minutes',
                'is_online_bookable',
                'min_staff_required',
                'buffer_time_minutes'
            ]);
        });
    }
};
