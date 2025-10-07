<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollbackRetellCostsCommand extends Command
{
    protected $signature = 'retell:rollback-costs
                            {--batch-id= : Specific migration batch to rollback}
                            {--confirm : Execute rollback}
                            {--show-details : Show detailed output for each call}';

    protected $description = 'Rollback Retell cost migration to previous values';

    public function handle(): int
    {
        if (!$this->option('confirm')) {
            $this->error('❌ Rollback requires --confirm flag for safety');
            $this->info('💡 This will restore all costs to their pre-migration values');
            return 1;
        }

        $batchId = $this->option('batch-id');

        if (!$batchId) {
            // Show available batches
            $this->showAvailableBatches();
            return 1;
        }

        $this->warn('🔄 ROLLBACK MODE');
        $this->info("📦 Batch ID: {$batchId}");
        $this->newLine();

        // Get all migrations for this batch
        $migrations = DB::table('call_cost_migration_log')
            ->where('migration_batch', $batchId)
            ->get();

        if ($migrations->isEmpty()) {
            $this->error("❌ No migrations found for batch: {$batchId}");
            return 1;
        }

        $this->info("📊 Found {$migrations->count()} calls to rollback");

        // Confirm before proceeding
        if (!$this->confirm('⚠️  This will restore all values. Continue?', false)) {
            $this->info('Rollback cancelled');
            return 0;
        }

        $this->newLine();

        $progressBar = $this->output->createProgressBar($migrations->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Rolling back...');
        $progressBar->start();

        $rolledBack = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($migrations as $migration) {
            try {
                DB::transaction(function () use ($migration) {
                    DB::table('calls')
                        ->where('id', $migration->call_id)
                        ->update([
                            'retell_cost_usd' => $migration->old_retell_cost_usd,
                            'retell_cost_eur_cents' => $migration->old_retell_cost_eur_cents,
                            'base_cost' => $migration->old_base_cost,
                            'exchange_rate_used' => $migration->old_exchange_rate_used,
                        ]);

                    // Mark migration as rolled back
                    DB::table('call_cost_migration_log')
                        ->where('id', $migration->id)
                        ->update([
                            'status' => 'rolled_back',
                        ]);
                });

                $rolledBack++;
                $progressBar->setMessage("Rolled back Call #{$migration->call_id}");

            } catch (\Exception $e) {
                $errors++;
                $errorDetails[] = [
                    'call_id' => $migration->call_id,
                    'error' => $e->getMessage()
                ];

                \Log::error("Rollback failed for call {$migration->call_id}", [
                    'error' => $e->getMessage(),
                    'batch_id' => $batchId
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('Complete!');
        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Rollback Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Calls', $migrations->count()],
                ['Successfully Rolled Back', $rolledBack],
                ['Errors', $errors],
            ]
        );

        if (!empty($errorDetails)) {
            $this->newLine();
            $this->error('❌ Failed Rollbacks:');
            $this->table(
                ['Call ID', 'Error'],
                array_map(fn($item) => [
                    $item['call_id'],
                    substr($item['error'], 0, 60) . '...'
                ], $errorDetails)
            );
        }

        $this->newLine();
        if ($errors === 0) {
            $this->info("✅ Rollback complete! All {$rolledBack} calls restored to original values.");
        } else {
            $this->warn("⚠️  Rollback completed with {$errors} errors. Check logs for details.");
        }

        return $errors === 0 ? 0 : 1;
    }

    private function showAvailableBatches(): void
    {
        $batches = DB::table('call_cost_migration_log')
            ->select('migration_batch', DB::raw('COUNT(*) as call_count'), DB::raw('MAX(migrated_at) as latest_migration'))
            ->groupBy('migration_batch')
            ->orderBy('latest_migration', 'desc')
            ->get();

        if ($batches->isEmpty()) {
            $this->error('❌ No migration batches found');
            return;
        }

        $this->info('Available migration batches:');
        $this->newLine();
        $this->table(
            ['Batch ID', 'Calls', 'Latest Migration'],
            $batches->map(fn($batch) => [
                $batch->migration_batch,
                $batch->call_count,
                $batch->latest_migration
            ])->toArray()
        );

        $this->newLine();
        $this->info('💡 Use: php artisan retell:rollback-costs --batch-id=BATCH_ID --confirm');
    }
}
