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
        Schema::table('event_type_import_logs', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('event_type_import_logs', 'total_errors')) {
                $table->integer('total_errors')->default(0)->after('failed_count');
            }
            
            if (!Schema::hasColumn('event_type_import_logs', 'error_details')) {
                $this->addJsonColumn($table, 'error_details', true)->after('error_message');
            }
        });
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
        
        Schema::table('event_type_import_logs', function (Blueprint $table) {
            $table->dropColumn(['total_errors', 'error_details']);
        });
    }
};