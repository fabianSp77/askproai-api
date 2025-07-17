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
            // Füge kunde_id als Fremdschlüssel hinzu, wenn noch nicht vorhanden
            if (!Schema::hasColumn('calls', 'kunde_id')) {
                $table->foreignId('kunde_id')->nullable()
                    ->constrained('customers')->nullOnDelete();
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
            if (Schema::hasColumn('calls', 'kunde_id')) {
                $table->dropForeign(['kunde_id']);
                $table->dropColumn('kunde_id');
            }
        });
    }
};
