<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite index for stuck calls query performance.
     * Query: WHERE status IN ('ongoing', 'in_progress', 'active') AND created_at < threshold
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Composite index on (status, created_at) for efficient stuck call detection
            // Used by: Call::scopeStuck() and CleanupStuckCalls command
            $table->index(['status', 'created_at'], 'idx_calls_status_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_status_created_at');
        });
    }
};
