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
            $table->boolean('active')->default(true);
            $table->integer('default_duration_minutes')->default(30);
            $table->boolean('is_online_bookable')->default(true);
            $table->integer('min_staff_required')->default(1);
            $table->integer('buffer_time_minutes')->default(0);
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
