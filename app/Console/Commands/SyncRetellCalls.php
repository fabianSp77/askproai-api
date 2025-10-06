<?php

namespace App\Console\Commands;

use App\Services\RetellApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncRetellCalls extends Command
{
    protected $signature = 'retell:sync-calls 
                            {--limit=1000 : Maximum number of calls to fetch}
                            {--days=30 : Number of days to look back}
                            {--force : Force re-sync even if calls exist}';

    protected $description = 'Sync all calls from Retell API to local database';

    public function handle()
    {
        $this->info('🔄 Starting Retell Call Synchronization');
        $this->info(str_repeat('=', 50));
        
        try {
            $client = new RetellApiClient();
            
            // Prepare parameters
            $params = [
                'limit' => (int) $this->option('limit'),
                'sort_order' => 'descending'
            ];
            
            // Add date filter if specified
            $days = (int) $this->option('days');
            if ($days > 0) {
                $startDate = now()->subDays($days)->startOfDay()->timestamp * 1000;
                $params['filter_criteria'] = [
                    'start_timestamp_after' => $startDate
                ];
                $this->info("📅 Fetching calls from last {$days} days");
            }
            
            $this->info("📞 Fetching calls from Retell API...");
            $this->info("  Limit: {$params['limit']}");
            
            // Progress bar for better UX
            $bar = null;
            
            // Perform the sync
            $stats = $client->syncAllCalls($params);
            
            // Display results
            $this->newLine();
            $this->info('✅ Synchronization Complete!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Calls Found', $stats['total']],
                    ['Successfully Synced', $stats['synced']],
                    ['Failed', $stats['failed']],
                    ['Skipped', $stats['skipped']]
                ]
            );
            
            // Check for issues
            if ($stats['failed'] > 0) {
                $this->warn("⚠️  {$stats['failed']} calls failed to sync. Check logs for details.");
            }
            
            // Additional verification
            $this->verifyDatabaseSync();
            
        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('Retell sync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    private function verifyDatabaseSync()
    {
        $this->newLine();
        $this->info('📊 Database Verification:');
        
        $totalCalls = \App\Models\Call::count();
        $recentCalls = \App\Models\Call::where('created_at', '>=', now()->subDay())->count();
        $callsWithTranscripts = \App\Models\Call::whereNotNull('transcript')->count();
        $callsWithCustomers = \App\Models\Call::whereNotNull('customer_id')->count();
        
        $this->table(
            ['Database Metric', 'Count'],
            [
                ['Total Calls in DB', $totalCalls],
                ['Calls (Last 24h)', $recentCalls],
                ['Calls with Transcripts', $callsWithTranscripts],
                ['Calls with Customer Link', $callsWithCustomers]
            ]
        );
        
        // Check for potential issues
        if ($callsWithCustomers < ($totalCalls * 0.5)) {
            $this->warn('⚠️  Less than 50% of calls are linked to customers. Consider improving phone number matching.');
        }
        
        if ($callsWithTranscripts < ($totalCalls * 0.8)) {
            $this->warn('⚠️  Some calls are missing transcripts. You may need to fetch detailed call data.');
        }
    }
}
