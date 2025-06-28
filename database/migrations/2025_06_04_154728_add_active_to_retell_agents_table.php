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
        Schema::table('retell_agents', function (Blueprint $table) {
            // Füge active-Spalte hinzu, ohne auf voice_settings zu verweisen
            if (!Schema::hasColumn('retell_agents', 'active')) {
                $table->boolean('active')->default(true);
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
        
        Schema::table('retell_agents', function (Blueprint $table) {
            if (Schema::hasColumn('retell_agents', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
