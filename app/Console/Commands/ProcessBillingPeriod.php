<?php

namespace App\Console\Commands;

use App\Models\BillingPeriod;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\StripeInvoiceService;
use App\Services\StripeServiceWithCircuitBreaker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBillingPeriod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-periods 
                            {--company= : Process specific company ID}
                            {--period= : Process specific billing period ID}
                            {--dry-run : Run without creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process billing periods and create invoices';

    private StripeServiceWithCircuitBreaker $stripeService;

    public function __construct(StripeServiceWithCircuitBreaker $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting billing period processing...');
        
        $startTime = now();
        $processed = 0;
        $skipped = 0;
        $errors = 0;
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
        }

        // Get billing periods to process
        $query = BillingPeriod::query()
            ->whereIn('status', ['pending', 'reported'])
            ->where('end_date', '<', now())
            ->where('is_invoiced', false);

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        if ($periodId = $this->option('period')) {
            $query->where('id', $periodId);
        }

        $billingPeriods = $query->get();

        if ($billingPeriods->isEmpty()) {
            $this->warn('No billing periods ready for processing.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$billingPeriods->count()} billing periods...");

        foreach ($billingPeriods as $billingPeriod) {
            try {
                $result = $this->processBillingPeriod($billingPeriod, $isDryRun);
                
                if ($result === 'processed') {
                    $processed++;
                    $this->line("✓ Processed billing period {$billingPeriod->id} (Company: {$billingPeriod->company_id})");
                } elseif ($result === 'skipped') {
                    $skipped++;
                    $this->info("→ Skipped period {$billingPeriod->id} (no charges or dry run)");
                } else {
                    $errors++;
                    $this->error("✗ Error processing period {$billingPeriod->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error processing period {$billingPeriod->id}: {$e->getMessage()}");
                Log::error('ProcessBillingPeriod Error', [
                    'billing_period_id' => $billingPeriod->id,
                    'company_id' => $billingPeriod->company_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $duration = now()->diffInSeconds($startTime);
        
        $this->info('');
        $this->info('Billing period processing completed:');
        $this->info("✓ Processed: {$processed}");
        $this->info("→ Skipped: {$skipped}");
        $this->error("✗ Errors: {$errors}");
        $this->info("⏱ Duration: {$duration}s");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process a single billing period
     */
    private function processBillingPeriod(BillingPeriod $billingPeriod, bool $isDryRun): string
    {
        DB::beginTransaction();
        try {
            // Skip if already invoiced
            if ($billingPeriod->is_invoiced) {
                DB::commit();
                return 'skipped';
            }

            // Skip if no charges
            if ($billingPeriod->total_cost <= 0) {
                $this->info("  Period {$billingPeriod->id}: No charges to invoice");
                
                // Mark as processed even if no invoice created
                if (!$isDryRun) {
                    $billingPeriod->status = 'processed';
                    $billingPeriod->is_invoiced = true;
                    $billingPeriod->invoiced_at = now();
                    $billingPeriod->save();
                }
                
                DB::commit();
                return 'skipped';
            }

            $this->info("  Period {$billingPeriod->id}: Total cost {$billingPeriod->total_cost} {$billingPeriod->currency}");
            $this->info("    Base fee: {$billingPeriod->base_fee}");
            $this->info("    Overage: {$billingPeriod->overage_cost} ({$billingPeriod->overage_minutes} minutes)");

            // Create invoice if not dry run
            if (!$isDryRun) {
                // Check if Stripe service is available
                if (!$this->stripeService->isAvailable()) {
                    throw new \Exception('Stripe service is currently unavailable (circuit breaker open)');
                }

                // Create invoice using Stripe service
                $invoice = $this->stripeService->createInvoiceForBillingPeriod($billingPeriod);
                
                if (!$invoice) {
                    throw new \Exception('Failed to create invoice');
                }

                // Update billing period
                $billingPeriod->status = 'processed';
                $billingPeriod->is_invoiced = true;
                $billingPeriod->invoiced_at = now();
                $billingPeriod->stripe_invoice_id = $invoice->stripe_invoice_id;
                $billingPeriod->stripe_invoice_created_at = now();
                $billingPeriod->save();

                $this->info("    Invoice created: {$invoice->invoice_number} (Stripe: {$invoice->stripe_invoice_id})");
                
                // Send invoice notification
                $this->sendInvoiceNotification($invoice);
            }

            DB::commit();
            
            Log::info('Billing period processed', [
                'billing_period_id' => $billingPeriod->id,
                'company_id' => $billingPeriod->company_id,
                'total_cost' => $billingPeriod->total_cost,
                'invoice_created' => !$isDryRun,
                'dry_run' => $isDryRun
            ]);

            return 'processed';
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send invoice notification to customer
     */
    private function sendInvoiceNotification(Invoice $invoice): void
    {
        try {
            // TODO: Implement email notification
            // For now, just log
            Log::info('Invoice notification queued', [
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'email' => $invoice->company->email
            ]);
            
            // Queue email job (to be implemented)
            // \App\Jobs\SendInvoiceEmailJob::dispatch($invoice);
            
        } catch (\Exception $e) {
            Log::error('Failed to send invoice notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
