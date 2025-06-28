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
        if (!Schema::hasColumn('companies', 'retell_default_settings')) {
            Schema::table('companies', function (Blueprint $table) {
                $this->addJsonColumn($table, 'retell_default_settings', true)->after('retell_enabled');
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

        // Skip in SQLite due to limitations
        if (!$this->isSQLite()) {
            Schema::table('companies', function (Blueprint $table) {
                if (Schema::hasColumn('companies', 'retell_default_settings')) {
                    $table->dropColumn('retell_default_settings');
                }
            });
        }
    }
};
