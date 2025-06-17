<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\BillingPeriod;
use App\Services\StripeInvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly 
                            {--company= : Generate for specific company ID}
                            {--month= : Month to generate for (Y-m format)}
                            {--dry-run : Run without creating invoices}';

    protected $description = 'Generate monthly invoices for all companies';

    protected StripeInvoiceService $invoiceService;

    public function __construct(StripeInvoiceService $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    public function handle()
    {
        $this->info('=== MONTHLY INVOICE GENERATION ===');
        
        // Determine which month to process
        $month = $this->option('month') 
            ? Carbon::parse($this->option('month')) 
            : Carbon::now()->subMonth(); // Default to previous month
            
        $this->info("Processing invoices for: " . $month->format('F Y'));
        
        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
        }

        // Get companies to process
        $query = Company::query()
            ->where('active', true)
            ->where('auto_invoice', true);
            
        if ($companyId = $this->option('company')) {
            $query->where('id', $companyId);
        }
        
        $companies = $query->get();
        $this->info("Found {$companies->count()} companies to process");

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($companies as $company) {
            $this->line("\nProcessing: {$company->name} (ID: {$company->id})");
            
            try {
                // Check if invoice already exists for this month
                $existingInvoice = $company->invoices()
                    ->whereMonth('invoice_date', $month->month)
                    ->whereYear('invoice_date', $month->year)
                    ->where('billing_reason', 'subscription_cycle')
                    ->exists();
                    
                if ($existingInvoice) {
                    $this->comment("  → Invoice already exists for this month");
                    $results['skipped']++;
                    continue;
                }

                // Get or create billing period
                $billingPeriod = $this->getOrCreateBillingPeriod($company, $month);
                
                if (!$billingPeriod) {
                    $this->error("  → Could not create billing period");
                    $results['failed']++;
                    continue;
                }

                $this->info("  → Billing period: {$billingPeriod->total_minutes} minutes");
                $this->info("  → Cost: €{$billingPeriod->total_cost}");
                $this->info("  → Revenue: €{$billingPeriod->total_revenue}");

                if ($isDryRun) {
                    $this->comment("  → Would create invoice (dry run)");
                    $results['success']++;
                    continue;
                }

                // Create invoice
                $invoice = $this->invoiceService->createInvoiceForBillingPeriod($billingPeriod);
                
                if ($invoice) {
                    $this->info("  ✓ Invoice created: {$invoice->invoice_number}");
                    $this->info("  → Total: €{$invoice->total}");
                    $results['success']++;
                } else {
                    $this->error("  ✗ Failed to create invoice");
                    $results['failed']++;
                }

            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                Log::error('Monthly invoice generation error', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $results['failed']++;
            }
        }

        // Summary
        $this->line("\n" . str_repeat('=', 50));
        $this->info("SUMMARY:");
        $this->info("  Success: {$results['success']}");
        $this->warn("  Skipped: {$results['skipped']}");
        $this->error("  Failed: {$results['failed']}");
        
        return $results['failed'] === 0 ? 0 : 1;
    }

    /**
     * Get or create billing period for the month.
     */
    protected function getOrCreateBillingPeriod(Company $company, Carbon $month): ?BillingPeriod
    {
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        // Check if billing period already exists
        $billingPeriod = BillingPeriod::where('company_id', $company->id)
            ->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd)
            ->first();

        if ($billingPeriod) {
            return $billingPeriod;
        }

        // Calculate usage for the period
        $usage = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(duration_sec) / 60 as total_minutes,
                SUM(cost) as total_cost
            ')
            ->first();

        // Get pricing model
        $pricing = $company->activePricing;
        if (!$pricing) {
            $this->warn("  → No active pricing model found");
            return null;
        }

        // Calculate revenue
        $totalMinutes = $usage->total_minutes ?? 0;
        $overageMinutes = max(0, $totalMinutes - $pricing->included_minutes);
        $minutePrice = $overageMinutes > 0 && $pricing->overage_price_per_minute
            ? $pricing->overage_price_per_minute
            : $pricing->price_per_minute;
            
        $usageRevenue = $overageMinutes * $minutePrice;
        $totalRevenue = $usageRevenue + ($pricing->monthly_base_fee ?? 0);
        
        $totalCost = $usage->total_cost ?? 0;
        $margin = $totalRevenue - $totalCost;
        $marginPercentage = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;

        // Create billing period
        return BillingPeriod::create([
            'company_id' => $company->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_minutes' => $totalMinutes,
            'included_minutes' => $pricing->included_minutes,
            'overage_minutes' => $overageMinutes,
            'total_cost' => $totalCost,
            'total_revenue' => $totalRevenue,
            'margin' => $margin,
            'margin_percentage' => $marginPercentage,
            'is_invoiced' => false,
            'pricing_model_id' => $pricing->id,
        ]);
    }
}