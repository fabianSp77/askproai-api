<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;
use App\Models\CalcomEventType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualStaffEventLinking extends Command
{
    protected $signature = 'staff:link-events {--auto : Automatisch alle verknüpfen}';
    protected $description = 'Manuelle Verknüpfung von Mitarbeitern mit Cal.com Event-Types';

    public function handle()
    {
        $this->info('🔗 Starte manuelle Mitarbeiter-Event-Type Verknüpfung...');
        
        $staff = Staff::all();
        $eventTypes = CalcomEventType::all();
        
        $this->info("Gefunden: {$staff->count()} Mitarbeiter, {$eventTypes->count()} Event-Types");
        
        if ($this->option('auto')) {
            $this->info('🤖 Führe automatische Verknüpfung durch...');
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($staff as $member) {
                foreach ($eventTypes as $eventType) {
                    try {
                        // Neue Tabelle verwenden
                        DB::table('staff_event_type_assignments')->updateOrInsert(
                            [
                                'staff_id' => $member->id,
                                'calcom_event_type_id' => $eventType->id
                            ],
                            [
                                'id' => Str::uuid(),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                        
                        $successCount++;
                        $this->line("✅ Verknüpft: {$member->name} ↔ {$eventType->name}");
                        
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->error("❌ Fehler bei {$member->name} ↔ {$eventType->name}");
                    }
                }
            }
            
            $this->info("✨ Abgeschlossen! Erfolg: $successCount, Fehler: $errorCount");
            return;
        }
    }
}
