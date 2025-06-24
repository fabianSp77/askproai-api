<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration is just a placeholder to mark the deprecation
        // The actual column removal will be done in a future migration
        // after ensuring all code is updated to use the new relationship
        
        Schema::table('branches', function (Blueprint $table) {
            // Add comment to indicate deprecation
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE branches MODIFY COLUMN calcom_event_type_id VARCHAR(255) COMMENT 'DEPRECATED - Use branch_event_types table instead'");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove deprecation comment
        Schema::table('branches', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE branches MODIFY COLUMN calcom_event_type_id VARCHAR(255)");
            }
        });
    }
};