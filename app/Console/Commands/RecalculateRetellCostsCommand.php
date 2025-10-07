<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\HistoricalCostRecalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecalculateRetellCostsCommand extends Command
{
    protected $signature = 'retell:recalculate-costs
                            {--dry-run : Simulate without DB changes}
                            {--confirm : Execute production run}
                            {--batch-size=100 : Calls per batch}
                            {--call-id= : Process specific call ID only}
                            {--show-details : Show detailed output for each call}';

    protected $description = 'Recalculate historical Retell costs from cost_breakdown JSON';

    private HistoricalCostRecalculationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new HistoricalCostRecalculationService();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $showDetails = $this->option('show-details');

        // Require confirmation for production run
        if (!$dryRun && !$this->option('confirm')) {
            $this->error('❌ Production run requires --confirm flag');
            $this->info('💡 Use --dry-run to simulate first, then --confirm to execute');
            return 1;
        }

        // Display mode
        $mode = $dryRun ? '🧪 DRY-RUN MODE' : '🔥 PRODUCTION MODE';
        $this->info($mode);
        $this->newLine();

        // Pre-flight checks
        if (!$dryRun) {
            $this->info('🔍 Running pre-flight checks...');
            if (!$this->preflightChecks()) {
                return 1;
            }
            $this->info('✅ Pre-flight checks passed');
            $this->newLine();
        }

        // Build query
        $query = Call::whereNotNull('cost_breakdown')
            ->whereNull('deleted_at');

        if ($callId = $this->option('call-id')) {
            $query->where('id', $callId);
            $this->info("🎯 Processing single call ID: {$callId}");
        }

        $totalCalls = $query->count();
        $this->info("📊 Found {$totalCalls} calls to process");

        if ($totalCalls === 0) {
            $this->warn('No calls found to process');
            return 0;
        }

        $this->newLine();

        // Results tracking
        $results = [
            'success' => 0,
            'skipped' => 0,
            'flagged' => 0,
            'error' => 0,
        ];

        $totalDeltaUsd = 0;
        $totalDeltaEurCents = 0;
        $flaggedCalls = [];
        $errorCalls = [];

        // Progress bar
        $progressBar = $this->output->createProgressBar($totalCalls);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Initializing...');
        $progressBar->start();

        // Batch processing
        $query->orderBy('id')->chunk($batchSize, function ($calls) use (
            &$results,
            &$totalDeltaUsd,
            &$totalDeltaEurCents,
            &$flaggedCalls,
            &$errorCalls,
            $progressBar,
            $dryRun,
            $showDetails
        ) {
            foreach ($calls as $call) {
                $result = $this->service->recalculateCallCost($call, $dryRun);

                $results[$result['status']]++;
                $progressBar->setMessage("Processing Call #{$call->id}...");
                $progressBar->advance();

                // Track deltas
                if (isset($result['delta'])) {
                    $totalDeltaUsd += $result['delta']['usd'];
                    $totalDeltaEurCents += $result['delta']['eur_cents'];
                }

                // Collect flagged calls
                if ($result['status'] === 'flagged') {
                    $flaggedCalls[] = [
                        'call_id' => $call->id,
                        'reason' => $result['reason'],
                        'suggested_cost' => $result['suggested_cost_usd'] ?? null,
                    ];
                }

                // Collect errors
                if ($result['status'] === 'error') {
                    $errorCalls[] = [
                        'call_id' => $call->id,
                        'reason' => $result['reason'],
                        'error' => $result['error'] ?? 'Unknown error',
                    ];
                }

                // Show details if requested
                if ($showDetails && in_array($result['status'], ['success', 'flagged', 'error'])) {
                    $this->newLine();
                    $this->displayCallDetails($call, $result);
                }
            }
        });

        $progressBar->setMessage('Complete!');
        $progressBar->finish();
        $this->newLine(2);

        // Results summary
        $this->displaySummary($results, $totalCalls, $totalDeltaUsd, $totalDeltaEurCents);

        // Flagged calls
        if (!empty($flaggedCalls)) {
            $this->newLine();
            $this->warn('⚠️  Flagged Calls (Manual Review Required):');
            $this->table(
                ['Call ID', 'Reason', 'Suggested Cost (USD)'],
                array_map(fn($item) => [
                    $item['call_id'],
                    $item['reason'],
                    '$' . number_format($item['suggested_cost'] ?? 0, 2)
                ], $flaggedCalls)
            );
        }

        // Error calls
        if (!empty($errorCalls)) {
            $this->newLine();
            $this->error('❌ Failed Calls:');
            $this->table(
                ['Call ID', 'Reason', 'Error'],
                array_map(fn($item) => [
                    $item['call_id'],
                    $item['reason'],
                    substr($item['error'], 0, 50) . '...'
                ], $errorCalls)
            );
        }

        // Final message
        $this->newLine();
        if ($dryRun) {
            $this->info('✅ Dry-run complete. No changes were made to the database.');
            $this->info('💡 Use --confirm to execute the migration.');
        } else {
            $this->info('✅ Migration complete!');
            $this->info("📦 Batch ID: {$this->service->getBatchId()}");
            $this->info('💾 All changes logged to call_cost_migration_log table');
            $this->newLine();
            $this->info('🔄 To rollback: php artisan retell:rollback-costs --batch-id=' . $this->service->getBatchId() . ' --confirm');
        }

        return 0;
    }

    private function preflightChecks(): bool
    {
        // Check audit table exists
        if (!Schema::hasTable('call_cost_migration_log')) {
            $this->error('❌ Audit table "call_cost_migration_log" not found');
            $this->info('💡 Run: php artisan migrate');
            return false;
        }

        // Check if migration already ran recently
        $recentMigration = DB::table('call_cost_migration_log')
            ->where('migrated_at', '>', now()->subHours(1))
            ->exists();

        if ($recentMigration) {
            $this->warn('⚠️  Warning: Migration ran within last hour');
            if (!$this->confirm('Continue anyway?', false)) {
                return false;
            }
        }

        return true;
    }

    private function displaySummary(array $results, int $total, float $deltaUsd, int $deltaEurCents): void
    {
        $this->info('📊 Migration Summary:');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            collect($results)->map(fn($count, $status) => [
                ucfirst($status),
                $count,
                $total > 0 ? round(($count / $total) * 100, 2) . '%' : '0%'
            ])->toArray()
        );

        $this->newLine();
        $this->info('💰 Financial Impact:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Calls Processed', $total],
                ['Successfully Updated', $results['success']],
                ['Delta USD', '$' . number_format($deltaUsd, 2)],
                ['Delta EUR', '€' . number_format($deltaEurCents / 100, 2) . ' (' . $deltaEurCents . ' cents)'],
            ]
        );
    }

    private function displayCallDetails(Call $call, array $result): void
    {
        $statusIcon = match($result['status']) {
            'success' => '✅',
            'flagged' => '⚠️',
            'error' => '❌',
            default => 'ℹ️'
        };

        $this->line("{$statusIcon} Call #{$call->id}: {$result['status']} ({$result['reason']})");

        if (isset($result['old_values'], $result['new_values'])) {
            $this->line("  Old: \${$result['old_values']['retell_cost_usd']} = {$result['old_values']['retell_cost_eur_cents']} cents");
            $this->line("  New: \${$result['new_values']['retell_cost_usd']} = {$result['new_values']['retell_cost_eur_cents']} cents");
            $this->line("  Δ: {$result['delta']['eur_cents']} cents");
        }
    }
}
