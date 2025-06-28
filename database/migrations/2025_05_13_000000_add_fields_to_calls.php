<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (! Schema::hasColumn('calls', 'duration_sec')) {
                $table->unsignedInteger('duration_sec')->nullable();
            }

            if (! Schema::hasColumn('calls', 'details')) {
                $this->addJsonColumn($table, 'details', true);
            }

            // weitere Spalten hier in gleicher Weise …
        });
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'duration_sec',
                'details',
                // weitere Spalten hier …
            ]);
        });
    }
};
