<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix for PolicyAnalyticsWidget 500 errors
     *
     * Root Cause: PolicyAnalyticsWidget queries appointment_modification_stats.metadata column
     * which doesn't exist in the database schema.
     *
     * This adds the missing metadata JSON column for storing additional policy violation data.
     */
    public function up(): void
    {
        // Skip if table doesn't exist (idempotent migration)
        if (!Schema::hasTable('appointment_modification_stats')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('appointment_modification_stats', 'metadata')) {
            return;
        }

        // Check if 'count' column exists for proper positioning
        if (Schema::hasColumn('appointment_modification_stats', 'count')) {
            Schema::table('appointment_modification_stats', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('count');
            });
        } else {
            Schema::table('appointment_modification_stats', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_modification_stats', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
