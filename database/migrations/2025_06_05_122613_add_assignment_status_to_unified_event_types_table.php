<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->enum('assignment_status', ['assigned', 'unassigned'])->default('unassigned');
            $table->index('assignment_status');
        });

        // Alle existierenden Event Types als unassigned markieren
        DB::table('unified_event_types')->update(['assignment_status' => 'unassigned']);
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->dropColumn('assignment_status');
        });
    }
};
