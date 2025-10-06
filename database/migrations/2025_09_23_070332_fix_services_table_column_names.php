<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Rename columns to match what the code expects
            if (Schema::hasColumn('services', 'active') && !Schema::hasColumn('services', 'is_active')) {
                $table->renameColumn('active', 'is_active');
            }
            if (Schema::hasColumn('services', 'default_duration_minutes') && !Schema::hasColumn('services', 'duration_minutes')) {
                $table->renameColumn('default_duration_minutes', 'duration_minutes');
            }
            if (Schema::hasColumn('services', 'is_online_bookable') && !Schema::hasColumn('services', 'is_online')) {
                $table->renameColumn('is_online_bookable', 'is_online');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Revert column names
            $table->renameColumn('is_active', 'active');
            $table->renameColumn('duration_minutes', 'default_duration_minutes');
            $table->renameColumn('is_online', 'is_online_bookable');
        });
    }
};