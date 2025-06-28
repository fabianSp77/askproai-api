<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Only add metadata column if it doesn't exist
                if (!Schema::hasColumn('appointments', 'metadata')) {
                    $this->addJsonColumn($table, 'metadata', true);
                }
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
            // The column will remain but won't cause issues
            return;
        }
        
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};