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
        // Add status column to mcp_metrics table
        if (Schema::hasTable('mcp_metrics') && !Schema::hasColumn('mcp_metrics', 'status')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->string('status', 50)->nullable()->after('response_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        if (Schema::hasTable('mcp_metrics')) {
            Schema::table('mcp_metrics', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};