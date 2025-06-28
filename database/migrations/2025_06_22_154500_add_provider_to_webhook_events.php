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
        if (!Schema::hasColumn('webhook_events', 'provider')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->string('provider', 50)->after('id')->default('unknown');
            });
        }
        
        $this->addIndexIfNotExists('webhook_events', 'provider');
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
        
        $this->dropIndexIfExists('webhook_events', 'webhook_events_provider_index');
        
        if (Schema::hasColumn('webhook_events', 'provider')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->dropColumn('provider');
            });
        }
    }
};