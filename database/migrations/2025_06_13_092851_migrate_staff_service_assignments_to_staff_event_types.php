<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migriere vorhandene Daten von staff_service_assignments nach staff_event_types
        $assignments = DB::table('staff_service_assignments')->get();
        
        foreach ($assignments as $assignment) {
            // Prüfe ob der Eintrag schon existiert
            $exists = DB::table('staff_event_types')
                ->where('staff_id', $assignment->staff_id)
                ->where('event_type_id', $assignment->calcom_event_type_id)
                ->exists();
                
            if (!$exists) {
                DB::table('staff_event_types')->insert([
                    'staff_id' => $assignment->staff_id,
                    'event_type_id' => $assignment->calcom_event_type_id,
                    'is_primary' => false,
                    'created_at' => $assignment->created_at ?? now(),
                    'updated_at' => $assignment->updated_at ?? now(),
                ]);
            }
        }
        
        // Optional: Alte Tabelle umbenennen statt löschen für Backup
        Schema::rename('staff_service_assignments', 'staff_service_assignments_backup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Stelle die alte Tabelle wieder her
        if (Schema::hasTable('staff_service_assignments_backup')) {
            Schema::rename('staff_service_assignments_backup', 'staff_service_assignments');
        }
        
        // Optional: Lösche die migrierten Daten aus staff_event_types
        // Dies ist riskant, da möglicherweise neue Daten hinzugefügt wurden
        // DB::table('staff_event_types')->truncate();
    }
};