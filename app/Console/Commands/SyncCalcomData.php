<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CalcomEventSyncService;

class SyncCalcomData extends Command
{
    protected $signature = 'calcom:sync {--reset : Zurücksetzen vor Sync}';
    protected $description = 'Synchronisiert Event-Types und Mitarbeiter von Cal.com';

    public function handle()
    {
        $this->info('🚀 Starte Cal.com Synchronisation...');

        if ($this->option('reset')) {
            $this->warn('⚠️  Resetiere bestehende Daten...');
            
            // Bestehende Verknüpfungen löschen
            \DB::table('staff_service_assignments')->truncate();
            \DB::table('calcom_event_types')->update(['sync_status' => 'pending']);
        }

        $syncService = new CalcomEventSyncService();

        // 1. Event-Types synchronisieren
        $this->info('📅 Synchronisiere Event-Types...');
        if ($syncService->syncAllEventTypes()) {
            $this->info('✅ Event-Types erfolgreich synchronisiert');
        } else {
            $this->error('❌ Fehler bei Event-Type Synchronisation');
            return 1;
        }

        // 2. Mitarbeiter-Verknüpfungen erstellen
        $this->info('👥 Verknüpfe Mitarbeiter mit Event-Types...');
        $syncService->linkStaffToEventTypes();
        $this->info('✅ Mitarbeiter-Verknüpfungen erstellt');

        // 3. Ergebnis anzeigen
        $eventTypeCount = \App\Models\CalcomEventType::where('sync_status', 'synced')->count();
        $staffCount = \App\Models\Staff::where('active', true)->count();
        $linkCount = \DB::table('staff_service_assignments')->count();

        $this->info("📊 Synchronisation abgeschlossen:");
        $this->info("   - Event-Types: {$eventTypeCount}");
        $this->info("   - Mitarbeiter: {$staffCount}");
        $this->info("   - Verknüpfungen: {$linkCount}");

        return 0;
    }
}
