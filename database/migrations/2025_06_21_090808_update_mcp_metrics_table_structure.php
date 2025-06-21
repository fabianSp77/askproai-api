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
        Schema::table('mcp_metrics', function (Blueprint $table) {
            // Drop the old duration_ms column if it exists
            if (Schema::hasColumn('mcp_metrics', 'duration_ms')) {
                $table->dropColumn('duration_ms');
            }
            
            // Add indexes if they don't exist
            try {
                $table->index('status', 'idx_mcp_metrics_status');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index(['service', 'status', 'created_at'], 'idx_mcp_metrics_service_status_created');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            try {
                $table->index(['service', 'operation', 'created_at'], 'idx_mcp_metrics_service_operation_created');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mcp_metrics', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_mcp_metrics_status');
            $table->dropIndex('idx_mcp_metrics_service_status_created');
            $table->dropIndex('idx_mcp_metrics_service_operation_created');
            
            // Drop columns
            if (Schema::hasColumn('mcp_metrics', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('mcp_metrics', 'response_time')) {
                $table->dropColumn('response_time');
            }
            
            if (Schema::hasColumn('mcp_metrics', 'error_message')) {
                $table->dropColumn('error_message');
            }
        });
    }
};
