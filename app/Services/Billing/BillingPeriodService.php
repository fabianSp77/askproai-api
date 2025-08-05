<?php

namespace App\Services\Billing;

use App\Models\BillingPeriod;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\StripeServiceWithCircuitBreaker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingPeriodService
{
    protected StripeServiceWithCircuitBreaker $stripeService;
    protected UsageCalculationService $usageService;

    public function __construct(
        StripeServiceWithCircuitBreaker $stripeService,
        UsageCalculationService $usageService
    ) {
        $this->stripeService = $stripeService;
        $this->usageService = $usageService;
    }

    /**
     * Process a billing period - calculate usage and finalize.
     */
    public function processPeriod(BillingPeriod $period): void
    {
        if ($period->status !== 'active') {
            throw new \Exception('Only active periods can be processed');
        }

        if (!$period->end_date->isPast()) {
            throw new \Exception('Period has not ended yet');
        }

        DB::beginTransaction();
        try {
            // Calculate usage from calls
            $this->calculatePeriodUsage($period);
            
            // Update status
            $period->update(['status' => 'processed']);
            
            DB::commit();
            
            Log::info('Billing period processed', [
                'period_id' => $period->id,
                'company_id' => $period->company_id,
                'total_minutes' => $period->total_minutes,
                'total_cost' => $period->total_cost,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process billing period', [
                'period_id' => $period->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate usage for a billing period.
     */
    public function calculatePeriodUsage(BillingPeriod $period): void
    {
        // Get all calls for the period
        $calls = Call::where('company_id', $period->company_id)
            ->whereBetween('start_timestamp', [$period->start_date, $period->end_date->endOfDay()])
            ->where('status', 'completed')
            ->get();

        // Calculate total minutes
        $totalSeconds = $calls->sum('duration_sec');
        $totalMinutes = round($totalSeconds / 60, 2);

        // Calculate overage
        $overageMinutes = max(0, $totalMinutes - $period->included_minutes);
        $overageCost = round($overageMinutes * $period->price_per_minute, 2);
        $totalCost = round($period->base_fee + $overageCost, 2);

        // Update period
        $period->update([
            'total_minutes' => $totalMinutes,
            'used_minutes' => $totalMinutes,
            'overage_minutes' => round($overageMinutes, 0),
            'overage_cost' => $overageCost,
            'total_cost' => $totalCost,
        ]);
    }

    /**
     * Create an invoice for a processed billing period.
     */
    public function createInvoice(BillingPeriod $period): Invoice
    {
        if ($period->status !== 'processed') {
            throw new \Exception('Only processed periods can be invoiced');
        }

        if ($period->is_invoiced) {
            throw new \Exception('Period is already invoiced');
        }

        DB::beginTransaction();
        try {
            // Create invoice
            $invoice = Invoice::create([
                'company_id' => $period->company_id,
                'number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'due_date' => now()->addDays(14),
                'subtotal' => $period->total_cost,
                'tax_rate' => 19,
                'tax_amount' => round($period->total_cost * 0.19, 2),
                'total' => round($period->total_cost * 1.19, 2),
                'status' => 'draft',
                'currency' => $period->currency ?? 'EUR',
                'metadata' => [
                    'billing_period_id' => $period->id,
                    'period_start' => $period->start_date->format('Y-m-d'),
                    'period_end' => $period->end_date->format('Y-m-d'),
                ],
            ]);

            // Create invoice items
            if ($period->base_fee > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Base Fee - ' . $period->start_date->format('F Y'),
                    'quantity' => 1,
                    'unit_price' => $period->base_fee,
                    'total' => $period->base_fee,
                ]);
            }

            if ($period->included_minutes > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => sprintf(
                        'Included Minutes: %s (Used: %s)',
                        number_format($period->included_minutes),
                        number_format($period->used_minutes, 1)
                    ),
                    'quantity' => 1,
                    'unit_price' => 0,
                    'total' => 0,
                ]);
            }

            if ($period->overage_minutes > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Overage Minutes',
                    'quantity' => $period->overage_minutes,
                    'unit_price' => $period->price_per_minute,
                    'total' => $period->overage_cost,
                ]);
            }

            // Update period
            $period->update([
                'is_invoiced' => true,
                'invoiced_at' => now(),
                'invoice_id' => $invoice->id,
                'status' => 'invoiced',
            ]);

            // Create in Stripe if customer exists
            if ($period->company->stripe_customer_id) {
                try {
                    $stripeInvoice = $this->stripeService->createInvoice($period->company, [
                        'amount' => $invoice->total,
                        'description' => 'Invoice ' . $invoice->number,
                        'metadata' => [
                            'invoice_id' => $invoice->id,
                            'billing_period_id' => $period->id,
                        ],
                    ]);

                    if ($stripeInvoice) {
                        $invoice->update([
                            'stripe_invoice_id' => $stripeInvoice->id,
                        ]);
                        
                        $period->update([
                            'stripe_invoice_id' => $stripeInvoice->id,
                            'stripe_invoice_created_at' => now(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to create Stripe invoice', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Invoice created for billing period', [
                'period_id' => $period->id,
                'invoice_id' => $invoice->id,
                'total' => $invoice->total,
            ]);

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create invoice', [
                'period_id' => $period->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/INV-' . $year . '-(\d+)/', $lastInvoice->number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('INV-%d-%04d', $year, $nextNumber);
    }

    /**
     * Create billing periods for all active companies.
     */
    public function createPeriodsForMonth(Carbon $date): int
    {
        $created = 0;
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        // Get all active companies
        $companies = \App\Models\Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            // Check if period already exists
            $exists = BillingPeriod::where('company_id', $company->id)
                ->where('start_date', $startDate)
                ->exists();

            if (!$exists) {
                // Get pricing configuration
                $pricing = $company->pricing ?? [
                    'base_fee' => 49.00,
                    'included_minutes' => 500,
                    'price_per_minute' => 0.10,
                ];

                BillingPeriod::create([
                    'company_id' => $company->id,
                    'subscription_id' => $company->activeSubscription()?->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'pending',
                    'base_fee' => $pricing['base_fee'] ?? 0,
                    'included_minutes' => $pricing['included_minutes'] ?? 0,
                    'price_per_minute' => $pricing['price_per_minute'] ?? 0.10,
                    'currency' => 'EUR',
                    'is_prorated' => false,
                ]);

                $created++;
            }
        }

        return $created;
    }
}