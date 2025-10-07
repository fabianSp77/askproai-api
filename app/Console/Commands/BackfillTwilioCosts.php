<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\PlatformCostService;
use App\Services\CostCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillTwilioCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'costs:backfill-twilio
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--dry-run : Preview without saving}
                            {--limit=1000 : Maximum number of calls to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill Twilio costs for existing calls that are missing cost data';

    private PlatformCostService $platformCostService;
    private CostCalculator $costCalculator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->platformCostService = new PlatformCostService();
        $this->costCalculator = new CostCalculator();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Starting Twilio cost backfill process...');
        $this->newLine();

        // Build query for calls missing Twilio costs
        $query = Call::whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->where(function ($q) {
                $q->whereNull('twilio_cost_usd')
                  ->orWhere('twilio_cost_usd', 0)
                  ->orWhereNull('twilio_cost_eur_cents')
                  ->orWhere('twilio_cost_eur_cents', 0);
            });

        // Apply date filters if provided
        if ($this->option('from')) {
            $query->where('created_at', '>=', $this->option('from'));
            $this->info("📅 Start date filter: {$this->option('from')}");
        }

        if ($this->option('to')) {
            $query->where('created_at', '<=', $this->option('to'));
            $this->info("📅 End date filter: {$this->option('to')}");
        }

        // Apply limit
        $limit = $this->option('limit');
        $query->limit($limit);

        // Get calls to process
        $calls = $query->orderBy('created_at', 'asc')->get();

        if ($calls->isEmpty()) {
            $this->info('✅ No calls found that need Twilio cost backfilling');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$calls->count()} calls without Twilio costs");
        $this->newLine();

        // Show sample of what will be processed
        $this->table(
            ['ID', 'Created', 'Duration (s)', 'Current Twilio Cost'],
            $calls->take(5)->map(function ($call) {
                return [
                    $call->id,
                    $call->created_at->format('Y-m-d H:i'),
                    $call->duration_sec,
                    $call->twilio_cost_eur_cents ?? 'NULL'
                ];
            })->toArray()
        );

        if ($calls->count() > 5) {
            $this->info("... and " . ($calls->count() - 5) . " more calls");
        }

        $this->newLine();

        // Confirm execution unless dry-run
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be saved');
        } else {
            if (!$this->confirm('Do you want to proceed with backfilling Twilio costs?', true)) {
                $this->info('❌ Backfill cancelled by user');
                return Command::FAILURE;
            }
        }

        $this->newLine();

        // Process calls with progress bar
        $bar = $this->output->createProgressBar($calls->count());
        $bar->setFormat('very_verbose');

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($calls as $call) {
            try {
                $result = $this->processCall($call, $this->option('dry-run'));

                if ($result['success']) {
                    $updated++;
                } else {
                    $skipped++;
                }

                $bar->advance();

            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to backfill Twilio cost for call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('═══════════════════════════════════════════');
        $this->info('📊 Backfill Summary');
        $this->info('═══════════════════════════════════════════');
        $this->info("Total processed: {$calls->count()}");
        $this->info("✅ Updated: {$updated}");
        $this->info("⏭️  Skipped: {$skipped}");
        $this->info("❌ Errors: {$errors}");

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 DRY RUN - No actual changes were made');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✅ Backfill completed successfully!');
        }

        return Command::SUCCESS;
    }

    /**
     * Process a single call for Twilio cost backfilling
     *
     * @param Call $call
     * @param bool $dryRun
     * @return array
     */
    private function processCall(Call $call, bool $dryRun): array
    {
        // Get Twilio pricing configuration
        $costPerMinuteUsd = config('platform-costs.twilio.pricing.inbound_per_minute_usd', 0.0085);

        // Calculate duration in minutes
        $durationMinutes = $call->duration_sec / 60;

        // Calculate estimated Twilio cost
        $estimatedCostUsd = $durationMinutes * $costPerMinuteUsd;

        // Skip if cost would be zero or negative
        if ($estimatedCostUsd <= 0) {
            return [
                'success' => false,
                'reason' => 'zero_cost'
            ];
        }

        if (!$dryRun) {
            DB::transaction(function () use ($call, $estimatedCostUsd) {
                // Track Twilio cost using PlatformCostService
                $this->platformCostService->trackTwilioCost($call, $estimatedCostUsd);

                // Recalculate total external costs
                $this->platformCostService->calculateCallTotalCosts($call);

                // Recalculate profit margins
                $this->costCalculator->updateCallCosts($call);
            });
        }

        return [
            'success' => true,
            'estimated_cost_usd' => $estimatedCostUsd
        ];
    }
}
