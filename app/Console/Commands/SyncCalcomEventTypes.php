<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use \App\Models\CalcomEventType;

class SyncCalcomEventTypes extends Command
{
    protected $signature   = 'calcom:sync-eventtypes';
    protected $description = 'Lädt alle Event-Typen aus Cal.com & speichert sie lokal';

    public function handle(): int
    {
        $apiKey = config('calcom.api_key');
        $team   = config('calcom.team_slug');   // ← askproai
        $user   = config('calcom.user_slug');   // ← leer
        $base   = rtrim(config('calcom.base_url'), '/');

        if (! $apiKey) {
            $this->error('❌  CALCOM_API_KEY fehlt in .env');
            return self::FAILURE;
        }

        /* ----------- URL zusammenbauen ---------------------------------- */
        $query = 'apiKey=' . $apiKey;
        if ($team)      $query .= '&teamUsername=' . $team;
        elseif ($user)  $query .= '&userUsername=' . $user;

        $url  = "{$base}/v1/event-types?{$query}";

        /* ----------- API-Call ------------------------------------------- */
        $resp = Http::acceptJson()->get($url);

        if (! $resp->successful()) {
            $this->error("Cal.com API-Fehler: {$resp->status()} {$resp->body()}");
            return self::FAILURE;
        }

        /* ----------- Datenarray holt Cal im Feld  event_types ----------- */
        $items = $resp->json()['event_types'] ?? [];

        $count = 0;
        foreach ($items as $et) {
            CalcomEventType::updateOrCreate(
                ['calcom_id' => $et['id']],
                [
                    'title'  => $et['title']  ?? $et['name'] ?? '—',
                    'active' => !($et['hidden'] ?? false),
              'staff_id' =>  \App\Models\CalcomEventType::where('calcom_id', $et['id'])->value('staff_id') ?: null,
                ]
            );
            $count++;
        }

        $this->info("✓ Synchronisiert: {$count} Event-Typen");
        return self::SUCCESS;
    }
}
