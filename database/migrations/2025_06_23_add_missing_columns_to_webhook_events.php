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
        Schema::table('webhook_events', function (Blueprint $table) {
            // Add correlation_id for tracking related operations
            if (!Schema::hasColumn('webhook_events', 'correlation_id')) {
                $table->string('correlation_id')->nullable()->after('company_id')->index();
            }
            
            // Add notes for storing processing notes
            if (!Schema::hasColumn('webhook_events', 'notes')) {
                $table->text('notes')->nullable()->after('error_message');
            }
            
            // retry_count already exists, skip it
            
            // status already exists, skip it
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
        
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn(['correlation_id', 'notes', 'retry_count', 'status']);
        });
    }
};