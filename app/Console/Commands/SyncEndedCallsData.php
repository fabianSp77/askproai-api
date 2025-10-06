<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\RetellApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncEndedCallsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retell:sync-ended-calls-data
                            {--dry-run : Show what would be synced without actually syncing}
                            {--hours=24 : Look back this many hours for ended calls}
                            {--limit=50 : Maximum number of calls to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync complete data for calls that ended but are missing webhook data (timing, cost, latency metrics)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Syncing Complete Data for Ended Calls');
        $this->info(str_repeat('=', 60));

        $dryRun = $this->option('dry-run');
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        $this->info("📅 Looking back {$hours} hours");
        $this->info("🔢 Processing up to {$limit} calls");
        $this->newLine();

        try {
            // Find calls that are ended but missing complete data
            $threshold = Carbon::now()->subHours($hours);

            // Criteria:
            // 1. Has end_timestamp (call actually ended)
            // 2. Missing key fields (agent_version, timing metrics, or cost data)
            // 3. Has retell_call_id (can be fetched from API)
            $callsNeedingSync = Call::query()
                ->whereNotNull('retell_call_id')
                ->whereNotNull('end_timestamp')
                ->where('created_at', '>=', $threshold)
                ->where(function($query) {
                    // Missing at least one of the new fields
                    $query->whereNull('agent_version')
                        ->orWhereNull('agent_talk_time_ms')
                        ->orWhereNull('cost_breakdown')
                        ->orWhereNull('latency_metrics');
                })
                ->orderBy('end_timestamp', 'desc')
                ->limit($limit)
                ->get();

            if ($callsNeedingSync->isEmpty()) {
                $this->info('✅ No calls found needing data sync');
                return Command::SUCCESS;
            }

            $this->info("📞 Found {$callsNeedingSync->count()} calls needing complete data sync");
            $this->newLine();

            // Show what will be synced
            $this->table(
                ['ID', 'Retell Call ID', 'End Time', 'Missing Fields'],
                $callsNeedingSync->map(function($call) {
                    $missing = [];
                    if (!$call->agent_version) $missing[] = 'agent_version';
                    if (!$call->agent_talk_time_ms) $missing[] = 'timing';
                    if (!$call->cost_breakdown) $missing[] = 'cost';
                    if (!$call->latency_metrics) $missing[] = 'latency';

                    return [
                        $call->id,
                        substr($call->retell_call_id, 0, 20) . '...',
                        $call->end_timestamp ? Carbon::parse($call->end_timestamp)->format('Y-m-d H:i') : 'N/A',
                        implode(', ', $missing)
                    ];
                })->toArray()
            );

            if ($dryRun) {
                $this->newLine();
                $this->info('🏁 DRY RUN COMPLETE - Run without --dry-run to actually sync data');
                return Command::SUCCESS;
            }

            // Proceed with actual sync
            $this->newLine();
            $this->info('🚀 Starting data sync from Retell API...');

            $client = new RetellApiClient();
            $stats = [
                'total' => $callsNeedingSync->count(),
                'synced' => 0,
                'failed' => 0,
                'api_errors' => 0,
            ];

            $progressBar = $this->output->createProgressBar($stats['total']);
            $progressBar->start();

            foreach ($callsNeedingSync as $call) {
                try {
                    // Fetch complete call data from Retell API
                    $callData = $client->getCallDetail($call->retell_call_id);

                    if (!$callData) {
                        $stats['api_errors']++;
                        Log::warning('Failed to fetch call detail from Retell API', [
                            'call_id' => $call->id,
                            'retell_call_id' => $call->retell_call_id
                        ]);
                        $progressBar->advance();
                        continue;
                    }

                    // Sync complete data using RetellApiClient
                    $syncedCall = $client->syncCallToDatabase($callData);

                    if ($syncedCall) {
                        $stats['synced']++;

                        // Log what was synced
                        $fieldsAdded = [];
                        if ($syncedCall->agent_version && !$call->agent_version) {
                            $fieldsAdded[] = 'agent_version';
                        }
                        if ($syncedCall->agent_talk_time_ms && !$call->agent_talk_time_ms) {
                            $fieldsAdded[] = 'timing_metrics';
                        }
                        if ($syncedCall->cost_breakdown && !$call->cost_breakdown) {
                            $fieldsAdded[] = 'cost_data';
                        }
                        if ($syncedCall->latency_metrics && !$call->latency_metrics) {
                            $fieldsAdded[] = 'latency_data';
                        }

                        Log::info('Call data synced successfully', [
                            'call_id' => $syncedCall->id,
                            'retell_call_id' => $syncedCall->retell_call_id,
                            'fields_added' => $fieldsAdded
                        ]);
                    } else {
                        $stats['failed']++;
                        Log::error('Failed to sync call data', [
                            'call_id' => $call->id,
                            'retell_call_id' => $call->retell_call_id
                        ]);
                    }

                    // Small delay to avoid API rate limiting
                    usleep(100000); // 100ms

                } catch (\Exception $e) {
                    $stats['failed']++;
                    Log::error('Exception during call sync', [
                        'call_id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->info('✅ Sync Complete!');
            $this->newLine();
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Calls Processed', $stats['total']],
                    ['Successfully Synced', $stats['synced']],
                    ['Failed to Sync', $stats['failed']],
                    ['API Errors', $stats['api_errors']],
                ]
            );

            if ($stats['failed'] > 0 || $stats['api_errors'] > 0) {
                $this->warn('⚠️  Some calls failed to sync. Check logs for details.');
            }

            // Verification
            $this->verifyDataCompleteness();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Command failed: ' . $e->getMessage());
            Log::error('SyncEndedCallsData command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Verify data completeness after sync
     */
    private function verifyDataCompleteness()
    {
        $this->newLine();
        $this->info('📊 Data Completeness Verification:');

        $totalCalls = Call::whereNotNull('end_timestamp')->count();
        $withAgentVersion = Call::whereNotNull('end_timestamp')->whereNotNull('agent_version')->count();
        $withTimingMetrics = Call::whereNotNull('end_timestamp')->whereNotNull('agent_talk_time_ms')->count();
        $withCostData = Call::whereNotNull('end_timestamp')->whereNotNull('cost_breakdown')->count();
        $withLatencyData = Call::whereNotNull('end_timestamp')->whereNotNull('latency_metrics')->count();

        $agentVersionPercent = $totalCalls > 0 ? round(($withAgentVersion / $totalCalls) * 100, 1) : 0;
        $timingPercent = $totalCalls > 0 ? round(($withTimingMetrics / $totalCalls) * 100, 1) : 0;
        $costPercent = $totalCalls > 0 ? round(($withCostData / $totalCalls) * 100, 1) : 0;
        $latencyPercent = $totalCalls > 0 ? round(($withLatencyData / $totalCalls) * 100, 1) : 0;

        $this->table(
            ['Field Group', 'Count', 'Coverage'],
            [
                ['Total Ended Calls', $totalCalls, '100%'],
                ['With Agent Version', $withAgentVersion, $agentVersionPercent . '%'],
                ['With Timing Metrics', $withTimingMetrics, $timingPercent . '%'],
                ['With Cost Data', $withCostData, $costPercent . '%'],
                ['With Latency Data', $withLatencyData, $latencyPercent . '%'],
            ]
        );

        // Warnings
        if ($agentVersionPercent < 80) {
            $this->warn("⚠️  Agent version coverage is low ({$agentVersionPercent}%). Some calls may not have this field in Retell.");
        }

        if ($timingPercent < 80) {
            $this->warn("⚠️  Timing metrics coverage is low ({$timingPercent}%). Retell may not provide this for all calls.");
        }

        if ($costPercent < 80) {
            $this->warn("⚠️  Cost data coverage is low ({$costPercent}%). Check if Retell cost calculation is enabled.");
        }
    }
}
