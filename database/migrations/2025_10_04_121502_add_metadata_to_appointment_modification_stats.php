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
        Schema::table('appointment_modification_stats', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('count');
        });
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
