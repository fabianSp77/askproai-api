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
        if (!Schema::hasColumn('appointments', 'source')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('source')->default('phone')->after('status')->index();
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
            $this->dropIndexIfExists('appointments', 'appointments_source_index');
            
            Schema::table('appointments', function (Blueprint $table) {
                if (Schema::hasColumn('appointments', 'source')) {
                    $table->dropColumn('source');
                }
            });
        }
    }
};
