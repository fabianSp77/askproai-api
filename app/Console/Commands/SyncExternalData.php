<?php

namespace App\Console\Commands;

use App\Services\DataSecurity\ExternalDataSync;
use Illuminate\Console\Command;

class SyncExternalData extends Command
{
    protected $signature = 'askproai:sync-external
                            {--source=all : Source to sync: all, calcom, retell}
                            {--verify : Verify data integrity after sync}';

    protected $description = 'Sync data from external sources (Cal.com, Retell.ai)';

    private $syncService;

    public function __construct(ExternalDataSync $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $source = $this->option('source');
        
        $this->info("Starting external data sync ({$source})...");
        
        $report = $this->syncService->syncAllExternalData();
        
        // Display results
        $this->info("\n📊 Sync Results:");
        $this->table(
            ['Source', 'Items Synced', 'Errors'],
            [
                ['Cal.com Appointments', $report['calcom']['appointments'], count($report['calcom']['errors'])],
                ['Cal.com Event Types', $report['calcom']['event_types'], '-'],
                ['Retell.ai Calls', $report['retell']['calls'], count($report['retell']['errors'])],
                ['Retell.ai Agents', $report['retell']['agents'], '-'],
            ]
        );
        
        // Show errors if any
        if (!empty($report['calcom']['errors']) || !empty($report['retell']['errors'])) {
            $this->error("\n❌ Errors encountered:");
            foreach ($report['calcom']['errors'] as $error) {
                $this->error("Cal.com: {$error}");
            }
            foreach ($report['retell']['errors'] as $error) {
                $this->error("Retell: {$error}");
            }
        }
        
        // Verify integrity if requested
        if ($this->option('verify')) {
            $this->info("\n🔍 Verifying data integrity...");
            $issues = $this->syncService->verifyDataIntegrity();
            
            if (empty($issues)) {
                $this->info("✅ Data integrity check passed!");
            } else {
                $this->warn("⚠️  Integrity issues found:");
                foreach ($issues as $issue) {
                    $this->warn("- {$issue}");
                }
            }
        }
        
        $duration = $report['finished_at']->diffInSeconds($report['started_at']);
        $this->info("\n✅ Sync completed in {$duration} seconds");
        
        return 0;
    }
}