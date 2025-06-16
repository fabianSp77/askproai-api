<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip this migration if running tests with SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Check if the table exists
        if (!Schema::hasTable('unified_event_types')) {
            return;
        }

        // Zuerst prüfen ob es Datensätze gibt
        $hasRecords = DB::table('unified_event_types')->count() > 0;
        
        if ($hasRecords) {
            // Wenn Datensätze vorhanden sind, nehmen wir die erste verfügbare Branch ID
            $defaultBranchId = DB::table('branches')->first()->id ?? null;
            
            if (!$defaultBranchId) {
                throw new \Exception('Keine Branches vorhanden. Bitte erst eine Branch anlegen.');
            }
            
            // Alle NULL oder ungültigen branch_ids mit default Branch füllen
            DB::table('unified_event_types')
                ->whereNull('branch_id')
                ->orWhere('branch_id', '')
                ->update(['branch_id' => $defaultBranchId]);
        }
        
        // Foreign Key Constraint entfernen
        Schema::table('unified_event_types', function (Blueprint $table) {
            $foreignKeys = Schema::getForeignKeys('unified_event_types');
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('branch_id', $foreignKey['columns'])) {
                    $table->dropForeign($foreignKey['name']);
                }
            }
        });
        
        // Spalte auf nullable setzen
        DB::statement('ALTER TABLE unified_event_types MODIFY branch_id CHAR(36) NULL');
        
        // Foreign Key Constraint wieder hinzufügen mit ON DELETE SET NULL
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->onDelete('set null');
        });
        
        // Jetzt können wir die Default-Zuordnungen wieder auf NULL setzen für unassigned
        DB::table('unified_event_types')
            ->where('assignment_status', 'unassigned')
            ->update(['branch_id' => null]);
    }

    public function down(): void
    {
        // Skip this migration if running tests with SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Check if the table exists
        if (!Schema::hasTable('unified_event_types')) {
            return;
        }

        // Foreign Key Constraint entfernen
        Schema::table('unified_event_types', function (Blueprint $table) {
            $foreignKeys = Schema::getForeignKeys('unified_event_types');
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('branch_id', $foreignKey['columns'])) {
                    $table->dropForeign($foreignKey['name']);
                }
            }
        });
        
        // Spalte wieder auf NOT NULL setzen
        DB::statement('ALTER TABLE unified_event_types MODIFY branch_id CHAR(36) NOT NULL');
        
        // Foreign Key Constraint wieder hinzufügen
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches');
        });
    }
};
