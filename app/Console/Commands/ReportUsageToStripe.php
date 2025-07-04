<?php

namespace App\Console\Commands;

use App\Models\BillingPeriod;
use App\Models\Call;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ReportUsageToStripe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:report-usage 
                            {--company= : Process specific company ID}
                            {--period= : Process specific billing period ID}
                            {--dry-run : Run without actually reporting to Stripe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report usage minutes to Stripe for metered billing';

    private StripeClient $stripe;

    public function __construct()
    {
        parent::__construct();
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting usage reporting to Stripe...');
        
        $startTime = now();
        $reported = 0;
        $skipped = 0;
        $errors = 0;
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No actual reporting to Stripe');
        }

        // Get billing periods to process
        $query = BillingPeriod::query()
            ->where('status', 'pending')
            ->where('start_date', '<=', now())
            ->whereNotNull('subscription_id');

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        if ($periodId = $this->option('period')) {
            $query->where('id', $periodId);
        }

        $billingPeriods = $query->get();

        if ($billingPeriods->isEmpty()) {
            $this->warn('No pending billing periods found.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$billingPeriods->count()} billing periods...");

        foreach ($billingPeriods as $billingPeriod) {
            try {
                $result = $this->reportUsageForPeriod($billingPeriod, $isDryRun);
                
                if ($result === 'reported') {
                    $reported++;
                    $this->line("✓ Reported usage for period {$billingPeriod->id} (Company: {$billingPeriod->company_id})");
                } elseif ($result === 'skipped') {
                    $skipped++;
                    $this->info("→ Skipped period {$billingPeriod->id} (no usage or dry run)");
                } else {
                    $errors++;
                    $this->error("✗ Error reporting period {$billingPeriod->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error processing period {$billingPeriod->id}: {$e->getMessage()}");
                Log::error('ReportUsageToStripe Error', [
                    'billing_period_id' => $billingPeriod->id,
                    'company_id' => $billingPeriod->company_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $duration = now()->diffInSeconds($startTime);
        
        $this->info('');
        $this->info('Usage reporting completed:');
        $this->info("✓ Reported: {$reported}");
        $this->info("→ Skipped: {$skipped}");
        $this->error("✗ Errors: {$errors}");
        $this->info("⏱ Duration: {$duration}s");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Report usage for a specific billing period
     */
    private function reportUsageForPeriod(BillingPeriod $billingPeriod, bool $isDryRun): string
    {
        DB::beginTransaction();
        try {
            // Calculate usage for the period
            $usage = $this->calculateUsageForPeriod($billingPeriod);
            
            if ($usage['total_minutes'] == 0) {
                DB::commit();
                return 'skipped';
            }

            // Update billing period with usage data
            $billingPeriod->used_minutes = $usage['total_minutes'];
            $billingPeriod->total_minutes = $usage['total_minutes'];
            $billingPeriod->calculateOverage();
            $billingPeriod->save();

            $this->info("  Period {$billingPeriod->id}: {$usage['total_minutes']} minutes used");
            $this->info("  Overage: {$billingPeriod->overage_minutes} minutes (Cost: {$billingPeriod->overage_cost} {$billingPeriod->currency})");

            // Report to Stripe if not dry run and has overage
            if (!$isDryRun && $billingPeriod->overage_minutes > 0) {
                $subscription = Subscription::find($billingPeriod->subscription_id);
                
                if (!$subscription || !$subscription->stripe_id) {
                    throw new \Exception('Subscription or Stripe ID not found');
                }

                // Find the metered price item in the subscription
                // This assumes you have a metered price item in your subscription
                // You might need to adjust this based on your Stripe setup
                $subscriptionItem = $subscription->items()
                    ->where('stripe_product', 'prod_overage_minutes') // Adjust product ID
                    ->first();

                if ($subscriptionItem) {
                    // Report usage to Stripe
                    $usageRecord = $this->stripe->subscriptionItems->createUsageRecord(
                        $subscriptionItem->stripe_id,
                        [
                            'quantity' => (int) $billingPeriod->overage_minutes,
                            'timestamp' => $billingPeriod->end_date->timestamp,
                            'action' => 'set', // 'set' replaces the usage, 'increment' adds to it
                        ]
                    );

                    $this->info("  Reported {$billingPeriod->overage_minutes} overage minutes to Stripe");
                    
                    Log::info('Usage reported to Stripe', [
                        'subscription_item_id' => $subscriptionItem->stripe_id,
                        'quantity' => $billingPeriod->overage_minutes,
                        'usage_record_id' => $usageRecord->id
                    ]);
                }
            }

            // Mark period as reported
            if (!$isDryRun) {
                $billingPeriod->status = 'reported';
                $billingPeriod->save();
            }

            DB::commit();
            
            Log::info('Usage reported to Stripe', [
                'billing_period_id' => $billingPeriod->id,
                'company_id' => $billingPeriod->company_id,
                'total_minutes' => $usage['total_minutes'],
                'overage_minutes' => $billingPeriod->overage_minutes,
                'dry_run' => $isDryRun
            ]);

            return 'reported';
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate usage for a billing period
     */
    private function calculateUsageForPeriod(BillingPeriod $billingPeriod): array
    {
        // Get all calls for this company within the billing period
        $calls = Call::where('company_id', $billingPeriod->company_id)
            ->when($billingPeriod->branch_id, function ($query) use ($billingPeriod) {
                return $query->where('branch_id', $billingPeriod->branch_id);
            })
            ->whereBetween('created_at', [
                $billingPeriod->start_date->startOfDay(),
                $billingPeriod->end_date->endOfDay()
            ])
            ->where('status', 'completed')
            ->get();

        $totalMinutes = 0;
        $callCount = 0;

        foreach ($calls as $call) {
            // Calculate duration in minutes (rounded up)
            $durationMinutes = ceil($call->duration_sec / 60);
            $totalMinutes += $durationMinutes;
            $callCount++;
        }

        return [
            'total_minutes' => $totalMinutes,
            'call_count' => $callCount,
            'calls' => $calls
        ];
    }
}
