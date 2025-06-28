<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip column drops in SQLite due to limitations
        if (!$this->isSQLite()) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                // Drop the old duration_ms column if it exists
                if (Schema::hasColumn('mcp_metrics', 'duration_ms')) {
                    $table->dropColumn('duration_ms');
                }
            });
        }
        
        // First ensure the columns exist
        Schema::table('mcp_metrics', function (Blueprint $table) {
            if (!Schema::hasColumn('mcp_metrics', 'status')) {
                $table->string('status', 20)->default('success');
            }
            if (!Schema::hasColumn('mcp_metrics', 'response_time')) {
                $table->decimal('response_time', 10, 3)->nullable();
            }
            if (!Schema::hasColumn('mcp_metrics', 'error_message')) {
                $table->text('error_message')->nullable();
            }
        });
        
        // Add indexes if they don't exist using compatible methods
        $this->addIndexIfNotExists('mcp_metrics', 'status', 'idx_mcp_metrics_status');
        $this->addIndexIfNotExists('mcp_metrics', ['service', 'status', 'created_at'], 'idx_mcp_metrics_service_status_created');
        $this->addIndexIfNotExists('mcp_metrics', ['service', 'operation', 'created_at'], 'idx_mcp_metrics_service_operation_created');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }

        // Drop indexes using compatible methods
        $this->dropIndexIfExists('mcp_metrics', 'idx_mcp_metrics_status');
        $this->dropIndexIfExists('mcp_metrics', 'idx_mcp_metrics_service_status_created');
        $this->dropIndexIfExists('mcp_metrics', 'idx_mcp_metrics_service_operation_created');
        
        // Skip column drops in SQLite due to limitations
        if (!$this->isSQLite()) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
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
    }
};
