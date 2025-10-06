<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\DataIntegrity\CallCustomerLinkerService;
use App\Services\DataIntegrity\SessionOutcomeTrackerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🔧 EMERGENCY FIX #5: Process historical unlinked calls
 *
 * This command retroactively processes calls that have names
 * but were never linked to customers.
 *
 * Usage:
 *   php artisan calls:process-unlinked            # Last 30 days
 *   php artisan calls:process-unlinked --days=90  # Last 90 days
 *   php artisan calls:process-unlinked --all      # All time
 */
class ProcessUnlinkedCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:process-unlinked
                            {--days=30 : Process calls from last N days}
                            {--all : Process all calls regardless of date}
                            {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🔧 Process historical unlinked calls with customer linking and outcome detection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $processAll = $this->option('all');
        $days = $this->option('days');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔍 Finding unlinked calls...');

        // Build query
        $query = Call::where(function ($q) {
            $q->where('customer_link_status', 'name_only')
              ->orWhere(function ($subQ) {
                  $subQ->whereNull('customer_link_status')
                       ->whereNotNull('customer_name');
              });
        })
        ->where('customer_id', null)
        ->where(function ($q) {
            $q->whereNotNull('extracted_name')
              ->orWhereNotNull('customer_name')
              ->orWhereNotNull('name');
        });

        // Add date filter unless --all
        if (!$processAll) {
            $query->where('created_at', '>=', now()->subDays($days));
            $this->info("  Filtering to last {$days} days");
        } else {
            $this->info("  Processing ALL historical calls");
        }

        $calls = $query->get();

        $this->info("  Found {$calls->count()} calls to process\n");

        if ($calls->count() === 0) {
            $this->info('✅ No calls to process!');
            return self::SUCCESS;
        }

        // Show sample calls
        $this->table(
            ['ID', 'Created', 'Name (extracted)', 'Name (customer)', 'Status', 'Customer ID'],
            $calls->take(5)->map(fn($call) => [
                $call->id,
                $call->created_at->format('Y-m-d H:i'),
                $call->extracted_name ?? '-',
                $call->customer_name ?? '-',
                $call->customer_link_status ?? 'NULL',
                $call->customer_id ?? 'NULL'
            ])->toArray()
        );

        if ($calls->count() > 5) {
            $this->info("  ... and " . ($calls->count() - 5) . " more\n");
        }

        if ($dryRun) {
            $this->info('✅ Dry run complete - would process ' . $calls->count() . ' calls');
            return self::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->confirm('Process these calls?', true)) {
            $this->warn('❌ Cancelled by user');
            return self::FAILURE;
        }

        // Initialize services
        $linker = new CallCustomerLinkerService();
        $outcomeTracker = new SessionOutcomeTrackerService();

        $stats = [
            'total' => $calls->count(),
            'linked' => 0,
            'outcome_updated' => 0,
            'success_updated' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $progressBar = $this->output->createProgressBar($calls->count());
        $progressBar->start();

        foreach ($calls as $call) {
            try {
                // 1. Try to link customer
                $match = $linker->findBestCustomerMatch($call);

                if ($match && $match['confidence'] >= 70) {
                    $linker->linkCustomer($call, $match['customer'], $match['method'], $match['confidence']);
                    $stats['linked']++;

                    Log::info('🔗 Historical call linked', [
                        'call_id' => $call->id,
                        'customer_id' => $match['customer']->id,
                        'confidence' => $match['confidence']
                    ]);
                }

                // 2. Detect outcome
                $outcomeTracker->autoDetectAndSet($call);
                $stats['outcome_updated']++;

                // 3. Determine success (if not set)
                if ($call->call_successful === null) {
                    $this->determineCallSuccess($call);
                    $stats['success_updated']++;
                }

            } catch (\Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ];

                Log::error('❌ Failed processing historical call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('✅ Processing complete!');
        $this->newLine();

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Processed', $stats['total'], '100%'],
                ['Successfully Linked', $stats['linked'], round(($stats['linked'] / max($stats['total'], 1)) * 100, 1) . '%'],
                ['Outcome Updated', $stats['outcome_updated'], round(($stats['outcome_updated'] / max($stats['total'], 1)) * 100, 1) . '%'],
                ['Success Status Updated', $stats['success_updated'], round(($stats['success_updated'] / max($stats['total'], 1)) * 100, 1) . '%'],
                ['Failed', $stats['failed'], round(($stats['failed'] / max($stats['total'], 1)) * 100, 1) . '%'],
            ]
        );

        // Show errors if any
        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Errors encountered:');
            $this->table(
                ['Call ID', 'Error'],
                array_map(fn($err) => [$err['call_id'], substr($err['error'], 0, 80)], $stats['errors'])
            );
        }

        // Log final stats
        Log::info('✅ Historical call processing complete', $stats);

        return self::SUCCESS;
    }

    /**
     * Determine if call was successful based on multiple criteria
     *
     * (Duplicated from RetellWebhookController for standalone command usage)
     */
    private function determineCallSuccess(Call $call): void
    {
        if ($call->call_successful !== null) {
            return;
        }

        $successful = false;
        $reason = 'unknown';

        // Success criteria (in priority order)
        if ($call->appointment_made || $call->appointments()->exists()) {
            $successful = true;
            $reason = 'appointment_made';
        } elseif ($call->session_outcome === 'appointment_booked') {
            $successful = true;
            $reason = 'appointment_booked';
        } elseif ($call->session_outcome === 'information_only' && $call->duration_sec >= 30) {
            $successful = true;
            $reason = 'information_provided';
        } elseif ($call->customer_id && $call->duration_sec >= 20) {
            $successful = true;
            $reason = 'customer_interaction';
        } elseif ($call->duration_sec < 10) {
            $successful = false;
            $reason = 'too_short';
        } elseif (!$call->transcript || strlen($call->transcript) < 50) {
            $successful = false;
            $reason = 'no_meaningful_interaction';
        } else {
            // Default: if we got a transcript and >20s, consider it successful
            $successful = ($call->duration_sec >= 20 && $call->transcript);
            $reason = $successful ? 'completed_interaction' : 'unclear';
        }

        $call->call_successful = $successful;
        $call->save();
    }
}
