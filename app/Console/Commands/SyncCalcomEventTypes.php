<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use \App\Models\CalcomEventType;

class SyncCalcomEventTypes extends Command
{
    protected $signature   = 'calcom:sync-eventtypes';
    protected $description = 'Lädt alle Event-Typen aus Cal.com & speichert sie lokal';

    public function handle(): int
    {
        // Use services.calcom config for consistency
        $apiKey = config('services.calcom.api_key', config('calcom.api_key'));
        $team   = config('calcom.team_slug');   // ← askproai
        $user   = config('calcom.user_slug');   // ← leer
        $base   = rtrim(config('services.calcom.base_url', config('calcom.base_url', 'https://api.cal.com/v2')), '/');

        if (! $apiKey) {
            $this->error('❌  CALCOM_API_KEY fehlt in .env');
            return self::FAILURE;
        }

        // Determine API version from base URL
        $useV2 = str_contains($base, '/v2');

        /* ----------- Build URL and request ----------------------------- */
        $queryParams = [];
        if ($team) {
            $queryParams['teamUsername'] = $team;
        } elseif ($user) {
            $queryParams['userUsername'] = $user;
        }

        /* ----------- API-Call ------------------------------------------- */
        try {
            if ($useV2) {
                // V2 API: Bearer authentication with headers
                $url = $base . '/event-types';
                if (!empty($queryParams)) {
                    $url .= '?' . http_build_query($queryParams);
                }
                
                $resp = Http::acceptJson()
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'cal-api-version' => '2024-08-13',
                        'Content-Type' => 'application/json'
                    ])
                    ->get($url);
                    
                $this->info("📡 Using Cal.com V2 API");
            } else {
                // V1 API: Query parameter authentication (fallback)
                $queryParams['apiKey'] = $apiKey;
                $url = $base . '/event-types?' . http_build_query($queryParams);
                
                $resp = Http::acceptJson()->get($url);
                
                $this->info("📡 Using Cal.com V1 API (fallback)");
            }

            if (! $resp->successful()) {
                $this->error("Cal.com API-Fehler: {$resp->status()} {$resp->body()}");
                Log::error('[SyncCalcomEventTypes] API error', [
                    'status' => $resp->status(),
                    'body' => $resp->body()
                ]);
                return self::FAILURE;
            }

            /* ----------- Datenarray holt Cal im Feld  event_types ----------- */
            $items = $resp->json()['event_types'] ?? [];

            // Get default company and branch for new entries
            $defaultCompanyId = \App\Models\Company::first()->id ?? 1;
            $defaultBranchId = \App\Models\Branch::first()->id ?? null;
            
            if (!$defaultBranchId) {
                $this->error('❌ Keine Branch gefunden. Bitte erst eine Branch anlegen.');
                return self::FAILURE;
            }
            
            $count = 0;
            foreach ($items as $et) {
                $existing = CalcomEventType::where('calcom_event_type_id', $et['id'])->first();
                
                try {
                    CalcomEventType::updateOrCreate(
                        ['calcom_event_type_id' => (string)$et['id']],
                        [
                            'name'  => $et['title']  ?? $et['name'] ?? '—',
                            'is_active' => !($et['hidden'] ?? false),
                            'staff_id' => $existing ? $existing->staff_id : null,
                            'company_id' => $existing ? $existing->company_id : $defaultCompanyId,
                            'branch_id' => $existing ? $existing->branch_id : $defaultBranchId,
                            'duration_minutes' => $et['length'] ?? 30,
                            'description' => $et['description'] ?? null,
                            'slug' => $et['slug'] ?? null,
                            'last_synced_at' => now(),
                        ]
                    );
                } catch (\Exception $e) {
                    // Bei Unique-Constraint-Fehler nur aktualisieren
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        $this->warn("⚠️  Event Type '{$et['title']}' existiert bereits - wird übersprungen");
                        continue;
                    }
                    throw $e;
                }
                $count++;
            }

            $this->info("✅ Synchronisiert: {$count} Event-Typen");
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            Log::error('[SyncCalcomEventTypes] Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }
}
