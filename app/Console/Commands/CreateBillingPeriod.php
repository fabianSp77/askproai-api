<?php

namespace App\Console\Commands;

use App\Models\BillingPeriod;
use App\Models\Company;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateBillingPeriod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:create-periods 
                            {--company= : Process specific company ID}
                            {--force : Force creation even if period exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create billing periods for all active subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting billing period creation...');
        
        $startTime = now();
        $created = 0;
        $skipped = 0;
        $errors = 0;

        // Get active subscriptions
        $query = Subscription::query()
            ->where('stripe_status', 'active')
            ->whereNotNull('company_id');

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('No active subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$subscriptions->count()} active subscriptions...");

        foreach ($subscriptions as $subscription) {
            try {
                $result = $this->createBillingPeriodForSubscription($subscription);
                
                if ($result === 'created') {
                    $created++;
                    $this->line("✓ Created billing period for company {$subscription->company_id}");
                } elseif ($result === 'skipped') {
                    $skipped++;
                } else {
                    $errors++;
                    $this->error("✗ Error creating billing period for company {$subscription->company_id}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error processing subscription {$subscription->id}: {$e->getMessage()}");
                Log::error('CreateBillingPeriod Error', [
                    'subscription_id' => $subscription->id,
                    'company_id' => $subscription->company_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $duration = now()->diffInSeconds($startTime);
        
        $this->info('');
        $this->info('Billing period creation completed:');
        $this->info("✓ Created: {$created}");
        $this->info("→ Skipped: {$skipped}");
        $this->error("✗ Errors: {$errors}");
        $this->info("⏱ Duration: {$duration}s");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Create billing period for a subscription
     */
    private function createBillingPeriodForSubscription(Subscription $subscription): string
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Check if billing period already exists for this month
        $existingPeriod = BillingPeriod::where('company_id', $subscription->company_id)
            ->where('subscription_id', $subscription->id)
            ->whereDate('start_date', $startOfMonth)
            ->first();

        if ($existingPeriod && !$this->option('force')) {
            return 'skipped';
        }

        DB::beginTransaction();
        try {
            // Get the company's pricing
            $company = Company::with('pricing')->find($subscription->company_id);
            if (!$company || !$company->pricing) {
                throw new \Exception('Company or pricing not found');
            }

            // Create new billing period
            $billingPeriod = BillingPeriod::create([
                'company_id' => $subscription->company_id,
                'subscription_id' => $subscription->id,
                'start_date' => $startOfMonth,
                'end_date' => $endOfMonth,
                'status' => 'pending',
                'included_minutes' => $company->pricing->included_minutes ?? 0,
                'price_per_minute' => $company->pricing->price_per_minute ?? 0,
                'base_fee' => $company->pricing->monthly_base_fee ?? 0,
                'used_minutes' => 0, // Will be calculated later
                'overage_minutes' => 0,
                'overage_cost' => 0,
                'total_cost' => $company->pricing->monthly_base_fee ?? 0,
                'currency' => $company->pricing->currency ?? 'EUR',
            ]);

            // If we're mid-month, calculate prorated amounts
            if ($now->day > 1 && $now->month == $startOfMonth->month) {
                $daysInMonth = $now->daysInMonth;
                $daysRemaining = $daysInMonth - $now->day + 1;
                $proration = $daysRemaining / $daysInMonth;
                
                $billingPeriod->included_minutes = round($billingPeriod->included_minutes * $proration);
                $billingPeriod->base_fee = round($billingPeriod->base_fee * $proration, 2);
                $billingPeriod->total_cost = $billingPeriod->base_fee;
                $billingPeriod->is_prorated = true;
                $billingPeriod->proration_factor = $proration;
                $billingPeriod->save();
            }

            DB::commit();
            
            Log::info('Billing period created', [
                'billing_period_id' => $billingPeriod->id,
                'company_id' => $subscription->company_id,
                'subscription_id' => $subscription->id,
                'period' => "{$startOfMonth->format('Y-m-d')} to {$endOfMonth->format('Y-m-d')}"
            ]);

            return 'created';
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
