<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class VerifyTeamEventIds extends Command
{
    protected $signature = 'calcom:verify-team-events {--team-id= : Optional specific team ID to check}';
    protected $description = 'Verify Event-IDs assigned to each Cal.com Team (CRITICAL for multi-tenant isolation)';

    public function handle()
    {
        $this->info("════════════════════════════════════════════════════════════════");
        $this->info("🔍 Cal.com Team Event-ID Verification");
        $this->info("════════════════════════════════════════════════════════════════");
        $this->newLine();

        $apiKey = config('services.calcom.api_key');
        if (!$apiKey) {
            $this->error("❌ CALCOM_API_KEY not configured in .env");
            return;
        }

        $client = new Client();

        // Get companies with Cal.com team IDs
        $companies = \App\Models\Company::whereNotNull('calcom_team_id');

        if ($this->option('team-id')) {
            $companies = $companies->where('calcom_team_id', $this->option('team-id'));
        }

        $companies = $companies->get();

        foreach ($companies as $company) {
            $this->verifyTeamEvents($client, $apiKey, $company);
        }

        $this->info("════════════════════════════════════════════════════════════════");
        $this->info("✅ Verification complete");
    }

    private function verifyTeamEvents(Client $client, string $apiKey, $company)
    {
        $this->info("🏢 {$company->name} (Team ID: {$company->calcom_team_id}, Company ID: {$company->id})");
        $this->line("───────────────────────────────────────────────────────────────");

        try {
            // CORRECT ENDPOINT: /v1/teams/{teamId}/event-types
            // This returns ONLY events assigned to this specific team
            $url = "https://api.cal.com/v1/teams/{$company->calcom_team_id}/event-types";

            $response = $client->request('GET', $url, [
                'query' => [
                    'apiKey' => $apiKey,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['event_types'])) {
                $this->error("  ❌ No event_types in response");
                return;
            }

            $eventCount = count($data['event_types']);
            $this->info("  ✅ Team-Scoped Event-IDs: {$eventCount}");
            $this->newLine();

            $dbEventIds = \Illuminate\Support\Facades\DB::table('calcom_event_mappings')
                ->where('company_id', $company->id)
                ->pluck('calcom_event_type_id')
                ->toArray();

            $calcomEventIds = array_map(fn($e) => (string)$e['id'], $data['event_types']);

            // Check each event
            foreach ($data['event_types'] as $event) {
                $eventId = (string)$event['id'];
                $title = $event['title'] ?? $event['name'] ?? 'N/A';
                $inDb = in_array($eventId, $dbEventIds);

                $status = $inDb ? '✅' : '⚠️';
                $this->line("    {$status} {$eventId} | {$title}");

                if (!$inDb) {
                    $this->line("         (Missing from calcom_event_mappings - add with: DB insert)");
                }
            }

            // Check for orphaned mappings (in DB but not in Cal.com team)
            $orphanedIds = array_diff($dbEventIds, $calcomEventIds);
            if (!empty($orphanedIds)) {
                $this->newLine();
                $this->warn("  ⚠️ Orphaned Mappings (in DB but not in Cal.com Team):");
                foreach ($orphanedIds as $orphanedId) {
                    $this->line("     ❌ {$orphanedId}");
                }
            }

        } catch (\Exception $e) {
            $this->error("  ❌ Error: " . $e->getMessage());
        }

        $this->newLine();
    }
}
