<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('appointments', 'start') &&
            ! Schema::hasColumn('appointments', 'start_time')) {

            Schema::table('appointments', function (Blueprint $table) {
                $table->renameColumn('start', 'start_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'start_time') &&
            ! Schema::hasColumn('appointments', 'start')) {

            Schema::table('appointments', function (Blueprint $table) {
                $table->renameColumn('start_time', 'start');
            });
        }
    }
};
