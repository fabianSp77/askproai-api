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
        if (Schema::hasTable('branch_service')) {
            Schema::table('branch_service', function (Blueprint $table) {
                // Prüfen ob die Spalten schon existieren und nur hinzufügen wenn nicht
                if (!Schema::hasColumn('branch_service', 'price')) {
                    $table->decimal('price', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('branch_service', 'duration')) {
                    $table->integer('duration')->nullable();
                }
                if (!Schema::hasColumn('branch_service', 'active')) {
                    $table->boolean('active')->default(true);
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
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('branch_service', function (Blueprint $table) {
            $table->dropColumn(['price', 'duration', 'active']);
        });
    }
};
