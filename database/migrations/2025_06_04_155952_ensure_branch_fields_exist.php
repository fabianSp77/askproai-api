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
            // Prüfe und füge fehlende Felder hinzu
            if (!Schema::hasColumn('branches', 'website')) {
                $table->string('website')->nullable();
            }
            
            if (!Schema::hasColumn('branches', 'country')) {
                $table->string('country')->default('Deutschland');
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
        
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'website')) {
                $table->dropColumn('website');
            }
            
            if (Schema::hasColumn('branches', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
