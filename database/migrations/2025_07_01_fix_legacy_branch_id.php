<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generiere eine neue UUID für den Legacy-Branch
        $newUuid = (string) Str::uuid();
        
        // Deaktiviere Foreign Key Checks temporär
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // Update aller Referenzen in anderen Tabellen ZUERST
            $tablesToUpdate = [
                'appointments' => 'branch_id',
                'calls' => 'branch_id',
                'staff' => 'branch_id',
                'working_hours' => 'branch_id',
                'phone_numbers' => 'branch_id',
                'master_service_branches' => 'branch_id',
                'staff_event_types' => 'branch_id',
                'calcom_event_types' => 'branch_id',
                'retell_agents' => 'branch_id',
            ];
            
            foreach ($tablesToUpdate as $table => $column) {
                // Prüfe ob die Tabelle und Spalte existieren
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::statement("UPDATE {$table} SET {$column} = ? WHERE {$column} = '1'", [$newUuid]);
                }
            }
            
            // Update der Branch-Tabelle ZULETZT
            DB::statement("UPDATE branches SET id = ? WHERE id = '1'", [$newUuid]);
            
            // Log die Änderung
            \Log::info("Legacy branch ID '1' wurde zu UUID '{$newUuid}' migriert");
            
        } finally {
            // Aktiviere Foreign Key Checks wieder
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Diese Migration kann nicht rückgängig gemacht werden
        // da wir die ursprüngliche ID '1' nicht wiederherstellen können
    }
};