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
        Schema::table('branches', function (Blueprint $table) {
            // Only add settings column as the other two already exist
            $this->addJsonColumn($table, 'settings', true);
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
        
        Schema::table('branches', function (Blueprint $table) {
            // Only drop settings column as it's the only one we're adding
            $table->dropColumn('settings');
        });
    }
};
