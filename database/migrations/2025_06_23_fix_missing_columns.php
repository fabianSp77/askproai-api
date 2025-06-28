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
        // Add working_hours column to staff table
        if (!Schema::hasColumn('staff', 'working_hours')) {
            Schema::table('staff', function (Blueprint $table) {
                $this->addJsonColumn($table, 'working_hours', true);
            });
        }
        
        // Add response_time column to mcp_metrics table
        if (Schema::hasTable('mcp_metrics') && !Schema::hasColumn('mcp_metrics', 'response_time')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->integer('response_time')->nullable()->after('service');
            });
        }
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
        
        if (Schema::hasColumn('staff', 'working_hours')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropColumn('working_hours');
            });
        }
        
        if (Schema::hasTable('mcp_metrics') && Schema::hasColumn('mcp_metrics', 'response_time')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->dropColumn('response_time');
            });
        }
    }
};