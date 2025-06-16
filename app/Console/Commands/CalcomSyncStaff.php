<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;
use App\Models\Company;
use App\Models\Branch;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;

class CalcomSyncStaff extends Command
{
    protected $signature = 'calcom:sync-staff {--company=} {--dry-run}';
    protected $description = 'Synchronisiert Mitarbeiter von Cal.com (nutzt v1 API)';

    public function handle()
    {
        $this->info('🔄 Starte Cal.com Mitarbeiter-Synchronisation...');

        $calcomService = new CalcomV2Service();

        // Hole alle Benutzer von Cal.com (v1 API)
        $response = $calcomService->getUsers();

        if (!$response || !isset($response['users'])) {
            $this->error('Keine Benutzer von Cal.com gefunden!');
            return 1;
        }

        // WICHTIG: Die Users sind in $response['users']
        $users = $response['users'];
        
        $this->info("Gefundene Cal.com Benutzer: " . count($users));

        // Zeige Benutzer-Details
        $this->table(
            ['ID', 'Name', 'Email', 'Username'],
            collect($users)->map(function ($user) {
                return [
                    $user['id'] ?? 'N/A',
                    $user['name'] ?? 'N/A',
                    $user['email'] ?? 'N/A',
                    $user['username'] ?? 'N/A'
                ];
            })
        );

        // Frage ob fortfahren
        if (!$this->option('dry-run') && !$this->confirm('Möchten Sie diese Benutzer als Mitarbeiter importieren?')) {
            return 0;
        }

        // Hole die erste Company
        $company = Company::first();
        if (!$company) {
            $this->error('Keine Company gefunden! Bitte erst eine Company anlegen.');
            return 1;
        }

        $branch = $company->branches()->first();
        if (!$branch) {
            $this->error('Keine Branch gefunden! Bitte erst eine Branch anlegen.');
            return 1;
        }

        $this->info("Importiere für Company: {$company->name}, Branch: {$branch->name}");

        // Importiere Benutzer als Staff
        $imported = 0;
        foreach ($users as $user) {
            if ($this->option('dry-run')) {
                $this->info("[DRY-RUN] Würde importieren: {$user['name']} (ID: {$user['id']})");
                continue;
            }

            // Prüfe ob bereits Fabian Spitzer existiert
            if (isset($user['name']) && $user['name'] === 'Fabian Spitzer') {
                $existingStaff = Staff::where('name', 'Fabian Spitzer')->first();
                if ($existingStaff) {
                    // Update nur die calcom_user_id
                    $existingStaff->calcom_user_id = $user['id'];
                    $existingStaff->email = $user['email'] ?? $existingStaff->email;
                    $existingStaff->save();
                    $this->info("✅ Fabian Spitzer aktualisiert mit Cal.com ID: {$user['id']}");
                    $imported++;
                    continue;
                }
            }

            $staff = Staff::updateOrCreate(
                [
                    'calcom_user_id' => $user['id']
                ],
                [
                    'branch_id' => $branch->id,
                    'name' => $user['name'] ?? 'Unbekannt',
                    'email' => $user['email'] ?? null,
                    'first_name' => explode(' ', $user['name'] ?? '')[0] ?? '',
                    'last_name' => explode(' ', $user['name'] ?? '', 2)[1] ?? '',
                    'is_active' => true,
                    'calcom_calendar_link' => $user['username'] ?? null
                ]
            );

            $this->info("✅ Mitarbeiter importiert: {$staff->name} (Cal.com ID: {$user['id']})");
            $imported++;
        }

        $this->info("✨ Synchronisation abgeschlossen! {$imported} Mitarbeiter importiert/aktualisiert.");
        return 0;
    }
}
