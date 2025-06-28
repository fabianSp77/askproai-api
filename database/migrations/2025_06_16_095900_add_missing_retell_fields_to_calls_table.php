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
        Schema::table('calls', function (Blueprint $table) {
            // Retell webhook data storage
            if (!Schema::hasColumn('calls', 'webhook_data')) {
                $this->addJsonColumn($table, 'webhook_data', true);
            }
            
            // Agent version tracking
            if (!Schema::hasColumn('calls', 'agent_version')) {
                $table->integer('agent_version')->nullable();
            }
            
            // Retell cost tracking (in dollars)
            if (!Schema::hasColumn('calls', 'retell_cost')) {
                $table->decimal('retell_cost', 10, 4)->nullable();
            }
            
            // Custom SIP headers
            if (!Schema::hasColumn('calls', 'custom_sip_headers')) {
                $this->addJsonColumn($table, 'custom_sip_headers', true);
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
        
        Schema::table('calls', function (Blueprint $table) {
            $columns = ['webhook_data', 'agent_version', 'retell_cost', 'custom_sip_headers'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};