<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\RetellApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResyncCallDataCommand extends Command
{
    protected $signature = 'calls:resync
                            {--call_id= : Specific call ID to resync}
                            {--all : Resync all calls with missing data}
                            {--recent= : Resync calls from last X days}
                            {--dry-run : Show what would be synced without actually syncing}';

    protected $description = 'Re-sync call data from Retell API (transcripts, costs, latency, etc.)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if ($callId = $this->option('call_id')) {
            return $this->resyncSingleCall($callId, $dryRun);
        }

        if ($this->option('all')) {
            return $this->resyncAll($dryRun);
        }

        if ($recent = $this->option('recent')) {
            return $this->resyncRecent((int) $recent, $dryRun);
        }

        $this->error('Please specify --call_id, --all, or --recent flag');
        return 1;
    }

    private function resyncSingleCall(int $callId, bool $dryRun): int
    {
        $call = Call::find($callId);

        if (!$call) {
            $this->error("❌ Call {$callId} not found");
            return 1;
        }

        if (!$call->retell_call_id) {
            $this->error("❌ Call {$callId} has no retell_call_id");
            return 1;
        }

        $this->info("📞 Re-syncing Call ID: {$call->id}");
        $this->line("   Retell ID: {$call->retell_call_id}");
        $this->line("   Created: {$call->created_at}");
        $this->newLine();

        $this->showDataStatus('BEFORE', $call);

        if ($dryRun) {
            $this->warn('🔍 DRY RUN - Would fetch and sync data from Retell');
            return 0;
        }

        try {
            $client = new RetellApiClient();
            $callData = $client->getCallDetail($call->retell_call_id);

            if (!$callData) {
                $this->error("❌ Failed to fetch call data from Retell API");
                return 1;
            }

            $call = $client->syncCallToDatabase($callData);

            $this->newLine();
            $this->showDataStatus('AFTER', $call->fresh());

            $this->newLine();
            $this->info("✅ Call successfully re-synced");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to re-sync: {$e->getMessage()}");
            Log::error('Call resync failed', [
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    private function resyncAll(bool $dryRun): int
    {
        $this->info('🔍 Finding calls with missing data...');

        $calls = Call::whereNotNull('retell_call_id')
            ->where(function ($query) {
                $query->whereNull('transcript')
                    ->orWhereNull('cost')
                    ->orWhereNull('duration_sec')
                    ->orWhereNull('latency_metrics');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($calls->isEmpty()) {
            $this->info('✅ No calls need re-syncing - all have complete data');
            return 0;
        }

        $this->warn("Found {$calls->count()} call(s) needing re-sync");
        $this->newLine();

        if (!$dryRun && !$this->confirm("Re-sync {$calls->count()} calls?")) {
            $this->info('Cancelled');
            return 0;
        }

        return $this->processCalls($calls, $dryRun);
    }

    private function resyncRecent(int $days, bool $dryRun): int
    {
        $this->info("🔍 Finding calls from last {$days} days...");

        $calls = Call::whereNotNull('retell_call_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();

        if ($calls->isEmpty()) {
            $this->warn("No calls found in the last {$days} days");
            return 0;
        }

        $this->info("Found {$calls->count()} call(s)");
        $this->newLine();

        if (!$dryRun && !$this->confirm("Re-sync {$calls->count()} calls from last {$days} days?")) {
            $this->info('Cancelled');
            return 0;
        }

        return $this->processCalls($calls, $dryRun);
    }

    private function processCalls($calls, bool $dryRun): int
    {
        $successful = 0;
        $failed = 0;
        $skipped = 0;

        $client = new RetellApiClient();
        $progressBar = $this->output->createProgressBar($calls->count());
        $progressBar->start();

        foreach ($calls as $call) {
            try {
                if ($dryRun) {
                    $successful++;
                } else {
                    $callData = $client->getCallDetail($call->retell_call_id);

                    if (!$callData) {
                        $skipped++;
                        $this->newLine();
                        $this->warn("⚠️ Skipped Call {$call->id} - No data from Retell");
                        continue;
                    }

                    $client->syncCallToDatabase($callData);
                    $successful++;
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("❌ Failed Call {$call->id}: {$e->getMessage()}");
                $progressBar->advance();
            }

            // Rate limiting - wait 100ms between calls
            usleep(100000);
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("Summary:");
        $this->line("  Successful: {$successful}");
        $this->line("  Failed: {$failed}");
        $this->line("  Skipped: {$skipped}");

        return $failed > 0 ? 1 : 0;
    }

    private function showDataStatus(string $label, Call $call): void
    {
        $this->line("{$label} Sync Status:");
        $this->line("  Duration: " . ($call->duration_sec ? "{$call->duration_sec}s ✅" : "❌ Missing"));
        $this->line("  Cost: " . ($call->cost ? "\${$call->cost} ✅" : "❌ Missing"));
        $this->line("  Cost Breakdown: " . ($call->cost_breakdown ? "✅ Present" : "❌ Missing"));
        $this->line("  Transcript: " . ($call->transcript ? "✅ Present" : "❌ Missing"));
        $this->line("  Recording: " . ($call->recording_url ? "✅ Present" : "❌ Missing"));
        $this->line("  Summary: " . ($call->summary ? "✅ Present" : "❌ Missing"));
        $this->line("  Latency Metrics: " . ($call->latency_metrics ? "✅ Present" : "❌ Missing"));
        $this->line("  E2E Latency: " . ($call->end_to_end_latency ? "{$call->end_to_end_latency}ms ✅" : "❌ Missing"));
    }
}
