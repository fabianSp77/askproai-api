<?php

namespace App\Console\Commands;

use App\Services\Calcom\CalcomV2Client;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CalcomCacheWarmup extends Command
{
    protected $signature = 'calcom:cache-warmup {--company=}';
    protected $description = 'Warm up Cal.com API caches';

    public function handle()
    {
        $this->info('Starting Cal.com cache warmup...');

        $companyId = $this->option('company');
        
        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::whereNotNull('calcom_api_key')->get();
        }

        if ($companies->isEmpty()) {
            $this->warn('No companies found with Cal.com API keys.');
            return 0;
        }

        $this->info("Found {$companies->count()} companies to process.");

        foreach ($companies as $company) {
            $this->processCompany($company);
        }

        $this->info('Cache warmup completed!');
        return 0;
    }

    private function processCompany(Company $company)
    {
        $this->line("Processing company: {$company->name} (ID: {$company->id})");

        try {
            $client = new CalcomV2Client($company->calcom_api_key);

            // Warm up event types cache
            $this->info('  - Fetching event types...');
            $eventTypes = $client->getEventTypes();
            $this->info("    Found {$eventTypes['count']} event types");

            // Warm up schedules cache
            $this->info('  - Fetching schedules...');
            $schedules = $client->getSchedules();
            $this->info("    Found " . count($schedules) . " schedules");

            // Warm up slots for next 7 days for each event type
            if (!empty($eventTypes['data'])) {
                $this->info('  - Fetching available slots...');
                
                $startTime = now()->startOfDay();
                $endTime = now()->addDays(7)->endOfDay();
                
                foreach ($eventTypes['data'] as $eventType) {
                    try {
                        $slots = $client->getAvailableSlots([
                            'startTime' => $startTime->toIso8601String(),
                            'endTime' => $endTime->toIso8601String(),
                            'eventTypeId' => $eventType['id'],
                        ]);
                        
                        $this->line("    Event '{$eventType['title']}': " . count($slots) . " slots available");
                    } catch (\Exception $e) {
                        $this->error("    Failed to fetch slots for event '{$eventType['title']}': " . $e->getMessage());
                    }
                }
            }

            $this->info("  ✓ Company processed successfully\n");

        } catch (\Exception $e) {
            $this->error("  ✗ Failed to process company: " . $e->getMessage() . "\n");
        }
    }
}