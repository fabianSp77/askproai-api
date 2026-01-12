<?php

namespace App\Services\Billing;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyServicePricing;
use App\Models\ServiceCase;
use App\Models\ServiceChangeFee;
use App\Models\ServiceOutputConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Monthly Billing Aggregator
 *
 * Aggregates all billable charges for a partner's managed companies.
 * Collects: Call minutes, monthly service fees, service changes, setup fees.
 *
 * Usage:
 *   $aggregator = new MonthlyBillingAggregator();
 *   $charges = $aggregator->aggregateChargesForPartner($partner, $periodStart, $periodEnd);
 */
class MonthlyBillingAggregator
{
    /**
     * Pre-loaded data for batch processing (avoids N+1 queries).
     */
    private Collection $batchCallsData;

    private Collection $batchServicePricings;

    private Collection $batchServiceChanges;

    private bool $batchDataLoaded = false;

    public function __construct()
    {
        $this->batchCallsData = collect();
        $this->batchServicePricings = collect();
        $this->batchServiceChanges = collect();
    }

    public function populateInvoice(
        AggregateInvoice $invoice,
        Company $partner,
        Carbon $periodStart,
        Carbon $periodEnd
    ): AggregateInvoice {
        // OPTIMIZATION: Eager load relationships to prevent N+1 queries
        // Note: pricingPlan relationship may not exist on all setups
        $managedCompanies = $partner->managedCompanies()
            ->with(['feeSchedule', 'tenant'])
            ->get();

        if ($managedCompanies->isEmpty()) {
            Log::warning('Partner has no managed companies', ['partner_id' => $partner->id]);

            return $invoice;
        }

        // OPTIMIZATION: Batch load all related data in 3 queries instead of N*3
        $this->preloadBatchData($managedCompanies, $periodStart, $periodEnd);

        foreach ($managedCompanies as $company) {
            $this->addChargesForCompany($invoice, $company, $periodStart, $periodEnd);
        }

        // Recalculate totals after all items added
        $invoice->calculateTotals();

        // Clear batch data after processing
        $this->clearBatchData();

        return $invoice;
    }

    /**
     * Preload all data for batch processing (reduces N+1 to constant queries).
     */
    private function preloadBatchData(Collection $companies, Carbon $periodStart, Carbon $periodEnd): void
    {
        $companyIds = $companies->pluck('id')->toArray();

        // Batch load calls (grouped by company_id)
        $this->batchCallsData = Call::whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$periodStart, $periodEnd->copy()->endOfDay()])
            ->whereIn('status', ['completed', 'ended'])
            ->get()
            ->groupBy('company_id');

        // Batch load service pricings
        $this->batchServicePricings = CompanyServicePricing::whereIn('company_id', $companyIds)
            ->where('is_active', true)
            ->where('effective_from', '<=', $periodEnd)
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $periodStart);
            })
            ->whereHas('template', fn ($q) => $q->where('pricing_type', 'monthly'))
            ->with('template')
            ->get()
            ->groupBy('company_id');

        // Batch load service change fees
        $this->batchServiceChanges = ServiceChangeFee::whereIn('company_id', $companyIds)
            ->whereBetween('service_date', [$periodStart, $periodEnd->copy()->endOfDay()])
            ->where('status', 'completed')
            ->whereNull('invoice_id')
            ->get()
            ->groupBy('company_id');

        $this->batchDataLoaded = true;

        Log::debug('Batch data preloaded for billing', [
            'company_count' => count($companyIds),
            'calls_loaded' => $this->batchCallsData->flatten()->count(),
            'pricings_loaded' => $this->batchServicePricings->flatten()->count(),
            'changes_loaded' => $this->batchServiceChanges->flatten()->count(),
        ]);
    }

    /**
     * Clear batch data after processing.
     */
    private function clearBatchData(): void
    {
        $this->batchCallsData = collect();
        $this->batchServicePricings = collect();
        $this->batchServiceChanges = collect();
        $this->batchDataLoaded = false;
    }

    /**
     * Get preloaded calls for a company (O(1) lookup).
     */
    private function getBatchCalls(int $companyId): Collection
    {
        return $this->batchCallsData->get($companyId, collect());
    }

    /**
     * Get preloaded service pricings for a company (O(1) lookup).
     */
    private function getBatchServicePricings(int $companyId): Collection
    {
        return $this->batchServicePricings->get($companyId, collect());
    }

    /**
     * Get preloaded service changes for a company (O(1) lookup).
     */
    private function getBatchServiceChanges(int $companyId): Collection
    {
        return $this->batchServiceChanges->get($companyId, collect());
    }

    /**
     * Add all charges for a single company to the invoice.
     */
    public function addChargesForCompany(
        AggregateInvoice $invoice,
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        // 1. Call minutes
        $this->addCallMinutesCharges($invoice, $company, $periodStart, $periodEnd);

        // 2. Monthly service fees
        $this->addMonthlyServiceFees($invoice, $company, $periodStart, $periodEnd);

        // 3. Service change fees
        $this->addServiceChangeFees($invoice, $company, $periodStart, $periodEnd);

        // 4. Setup fees (one-time, check if in period)
        $this->addSetupFees($invoice, $company, $periodStart, $periodEnd);

        // 5. Service Gateway cases (per-case or monthly flat)
        $this->addServiceGatewayCases($invoice, $company, $periodStart, $periodEnd);
    }

    /**
     * Get a summary of charges without creating items (for preview).
     */
    public function getChargesSummary(
        Company $partner,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $summary = [
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'companies' => [],
            'total_cents' => 0,
        ];

        $managedCompanies = $partner->managedCompanies()->get();

        foreach ($managedCompanies as $company) {
            $companyCharges = $this->getCompanyChargesSummary($company, $periodStart, $periodEnd);

            if ($companyCharges['total_cents'] > 0) {
                $summary['companies'][] = $companyCharges;
                $summary['total_cents'] += $companyCharges['total_cents'];
            }
        }

        return $summary;
    }

    /**
     * Get charges summary for a single company.
     */
    private function getCompanyChargesSummary(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $summary = [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'charges' => [],
            'total_cents' => 0,
        ];

        // Call minutes
        $callData = $this->getCallMinutesData($company, $periodStart, $periodEnd);
        if ($callData['total_cents'] > 0) {
            $summary['charges'][] = [
                'type' => 'call_minutes',
                'description' => "Call-Minuten ({$callData['call_count']} Anrufe)",
                'amount_cents' => $callData['total_cents'],
            ];
            $summary['total_cents'] += $callData['total_cents'];
        }

        // Monthly services
        $monthlyFees = $this->getMonthlyServicesData($company, $periodStart, $periodEnd);
        foreach ($monthlyFees as $fee) {
            $summary['charges'][] = [
                'type' => 'monthly_service',
                'description' => $fee['name'],
                'amount_cents' => $fee['amount_cents'],
            ];
            $summary['total_cents'] += $fee['amount_cents'];
        }

        // Service changes
        $serviceChanges = $this->getServiceChangesData($company, $periodStart, $periodEnd);
        foreach ($serviceChanges as $change) {
            $summary['charges'][] = [
                'type' => 'service_change',
                'description' => $change['description'],
                'amount_cents' => $change['amount_cents'],
            ];
            $summary['total_cents'] += $change['amount_cents'];
        }

        // Setup fees
        $setupFees = $this->getSetupFeesData($company, $periodStart, $periodEnd);
        foreach ($setupFees as $fee) {
            $summary['charges'][] = [
                'type' => 'setup_fee',
                'description' => $fee['description'],
                'amount_cents' => $fee['amount_cents'],
            ];
            $summary['total_cents'] += $fee['amount_cents'];
        }

        return $summary;
    }

    // ========================================
    // CALL MINUTES
    // ========================================

    /**
     * Add call minutes charges to invoice.
     */
    private function addCallMinutesCharges(
        AggregateInvoice $invoice,
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $data = $this->getCallMinutesData($company, $periodStart, $periodEnd);

        if ($data['total_cents'] <= 0) {
            return;
        }

        AggregateInvoiceItem::createCallMinutesItem(
            aggregateInvoiceId: $invoice->id,
            companyId: $company->id,
            totalMinutes: $data['total_minutes'],
            callCount: $data['call_count'],
            ratePerMinuteCents: $data['rate_per_minute_cents'],
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );
    }

    /**
     * Get call minutes data for a company.
     *
     * OPTIMIZED: Uses preloaded batch data when available (O(1) lookup).
     * Uses bcmath for precise decimal calculations (4 decimal places).
     */
    private function getCallMinutesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        // OPTIMIZATION: Use preloaded data if available
        if ($this->batchDataLoaded) {
            $calls = $this->getBatchCalls($company->id);
        } else {
            // Fallback to direct query (for getChargesSummary preview)
            $calls = Call::where('company_id', $company->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd->copy()->endOfDay()])
                ->whereIn('status', ['completed', 'ended'])
                ->get();
        }

        if ($calls->isEmpty()) {
            return [
                'call_count' => 0,
                'total_minutes' => 0,
                'rate_per_minute_cents' => 0,
                'total_cents' => 0,
            ];
        }

        // Calculate total duration in seconds
        // Note: Column is 'duration_sec' in the calls table
        $totalSeconds = $calls->sum('duration_sec') ?: 0;
        $callCount = $calls->count();

        // Get rate per minute (from company fee schedule or default)
        // Note: feeSchedule/tenant/pricingPlan already eager loaded
        $ratePerMinuteCents = $this->getCallRateForCompany($company);

        // PRECISION: Use bcmath for accurate decimal calculations
        // Convert seconds to minutes: totalSeconds / 60 (maintain 4 decimal places)
        $totalMinutes = bcdiv((string) $totalSeconds, '60', 4);

        // Calculate total cost: totalMinutes * ratePerMinuteCents (maintain 4 decimal places)
        $totalCostPrecise = bcmul($totalMinutes, (string) $ratePerMinuteCents, 4);

        // Round to integer cents for storage
        $totalCents = (int) round((float) $totalCostPrecise);

        return [
            'call_count' => $callCount,
            'total_minutes' => round((float) $totalMinutes, 2), // Display precision: 2 decimals
            'rate_per_minute_cents' => $ratePerMinuteCents,
            'total_cents' => $totalCents,
        ];
    }

    /**
     * Get call rate for a company (in cents per minute).
     */
    private function getCallRateForCompany(Company $company): int
    {
        // Check if company has a fee schedule with override
        if ($company->feeSchedule && $company->feeSchedule->override_per_minute_rate) {
            return (int) ($company->feeSchedule->override_per_minute_rate * 100);
        }

        // Check tenant rate
        if ($company->tenant && $company->tenant->per_minute_rate) {
            return (int) ($company->tenant->per_minute_rate * 100);
        }

        // Check pricing plan
        if ($company->pricingPlan && $company->pricingPlan->price_per_minute) {
            return (int) ($company->pricingPlan->price_per_minute * 100);
        }

        // Default rate: €0.12 per minute = 12 cents
        return 12;
    }

    // ========================================
    // MONTHLY SERVICE FEES
    // ========================================

    /**
     * Add monthly service fees to invoice.
     */
    private function addMonthlyServiceFees(
        AggregateInvoice $invoice,
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $fees = $this->getMonthlyServicesData($company, $periodStart, $periodEnd);

        foreach ($fees as $fee) {
            AggregateInvoiceItem::createMonthlyServiceItem(
                aggregateInvoiceId: $invoice->id,
                companyId: $company->id,
                serviceName: $fee['name'],
                amountCents: $fee['amount_cents'],
                servicePricingId: $fee['pricing_id'],
            );
        }
    }

    /**
     * Get monthly service fees data.
     *
     * OPTIMIZED: Uses preloaded batch data when available (O(1) lookup).
     */
    private function getMonthlyServicesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        // OPTIMIZATION: Use preloaded data if available
        if ($this->batchDataLoaded) {
            $pricings = $this->getBatchServicePricings($company->id);
        } else {
            // Fallback to direct query (for getChargesSummary preview)
            $pricings = CompanyServicePricing::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('effective_from', '<=', $periodEnd)
                ->where(function ($q) use ($periodStart) {
                    $q->whereNull('effective_until')
                        ->orWhere('effective_until', '>=', $periodStart);
                })
                ->whereHas('template', function ($q) {
                    $q->where('pricing_type', 'monthly');
                })
                ->with('template')
                ->get();
        }

        $fees = [];
        foreach ($pricings as $pricing) {
            $fees[] = [
                'pricing_id' => $pricing->id,
                'name' => $pricing->template->name ?? $pricing->custom_name ?? 'Monatlicher Service',
                'amount_cents' => (int) ($pricing->final_price * 100),
            ];
        }

        return $fees;
    }

    // ========================================
    // SERVICE CHANGE FEES
    // ========================================

    /**
     * Add service change fees to invoice.
     */
    private function addServiceChangeFees(
        AggregateInvoice $invoice,
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $changes = $this->getServiceChangesData($company, $periodStart, $periodEnd);

        foreach ($changes as $change) {
            AggregateInvoiceItem::createServiceChangeItem(
                aggregateInvoiceId: $invoice->id,
                companyId: $company->id,
                description: $change['description'],
                amountCents: $change['amount_cents'],
                serviceChangeFeeId: $change['fee_id'],
            );
        }
    }

    /**
     * Get service change fees data.
     *
     * OPTIMIZED: Uses preloaded batch data when available (O(1) lookup).
     */
    private function getServiceChangesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        // Check if ServiceChangeFee model exists
        if (! class_exists(ServiceChangeFee::class)) {
            return [];
        }

        // OPTIMIZATION: Use preloaded data if available
        if ($this->batchDataLoaded) {
            $fees = $this->getBatchServiceChanges($company->id);
        } else {
            // Fallback to direct query (for getChargesSummary preview)
            $fees = ServiceChangeFee::where('company_id', $company->id)
                ->whereBetween('service_date', [$periodStart, $periodEnd])
                ->where('status', 'completed')
                ->whereNull('invoice_id') // Not yet invoiced
                ->get();
        }

        $changes = [];
        foreach ($fees as $fee) {
            $changes[] = [
                'fee_id' => $fee->id,
                'description' => $fee->description ?? $fee->category,
                'amount_cents' => (int) ($fee->amount * 100),
            ];
        }

        return $changes;
    }

    // ========================================
    // SETUP FEES
    // ========================================

    /**
     * Add setup fees to invoice.
     */
    private function addSetupFees(
        AggregateInvoice $invoice,
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $fees = $this->getSetupFeesData($company, $periodStart, $periodEnd);

        foreach ($fees as $fee) {
            AggregateInvoiceItem::createSetupFeeItem(
                aggregateInvoiceId: $invoice->id,
                companyId: $company->id,
                description: $fee['description'],
                amountCents: $fee['amount_cents'],
            );
        }
    }

    /**
     * Get setup fees data.
     * Setup fees are typically one-time fees charged when a company is created.
     */
    private function getSetupFeesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $fees = [];

        // Check if company was created in this period and has a fee schedule with setup fee
        if ($company->created_at >= $periodStart && $company->created_at <= $periodEnd) {
            $feeSchedule = $company->feeSchedule;

            if ($feeSchedule && $feeSchedule->setup_fee > 0 && ! $feeSchedule->isSetupFeeBilled()) {
                $fees[] = [
                    'description' => 'Einrichtungsgebühr',
                    'amount_cents' => (int) ($feeSchedule->setup_fee * 100),
                ];

                // Mark setup fee as billed using the model's API
                $feeSchedule->markSetupFeeBilled();
            }
        }

        return $fees;
    }

    // =========================================================================
    // SERVICE GATEWAY BILLING
    // =========================================================================

    /**
     * Add Service Gateway charges to invoice.
     *
     * Handles two billing modes:
     * - per_case: Bill each case individually based on output type
     * - monthly_flat: Bill a flat monthly fee for unlimited cases
     */
    private function addServiceGatewayCases(
        AggregateInvoice $invoice,
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $data = $this->getServiceGatewayCasesData($company, $periodStart, $periodEnd);

        // Process per-case billing
        if (! empty($data['per_case'])) {
            $perCase = $data['per_case'];

            $item = AggregateInvoiceItem::createServiceGatewayItem(
                aggregateInvoiceId: $invoice->id,
                companyId: $company->id,
                caseCount: $perCase['case_count'],
                unitPriceCents: $perCase['average_unit_price_cents'],
                totalAmountCents: $perCase['total_amount_cents'],
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                outputType: $perCase['output_types'] ?? null,
            );

            // Mark all cases as billed
            ServiceCase::where('company_id', $company->id)
                ->unbilled()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->update([
                    'billing_status' => ServiceCase::BILLING_BILLED,
                    'billed_at' => now(),
                    'invoice_item_id' => $item->id,
                ]);

            Log::info('[ServiceGateway] Per-case billing added', [
                'company_id' => $company->id,
                'case_count' => $perCase['case_count'],
                'amount_cents' => $perCase['total_amount_cents'],
            ]);
        }

        // Process monthly flat billing
        foreach ($data['monthly_flat'] as $flatFee) {
            AggregateInvoiceItem::createServiceGatewayMonthlyItem(
                aggregateInvoiceId: $invoice->id,
                companyId: $company->id,
                amountCents: $flatFee['amount_cents'],
                configName: $flatFee['config_name'],
                periodStart: $periodStart,
                periodEnd: $periodEnd,
            );

            Log::info('[ServiceGateway] Monthly flat billing added', [
                'company_id' => $company->id,
                'config' => $flatFee['config_name'],
                'amount_cents' => $flatFee['amount_cents'],
            ]);
        }
    }

    /**
     * Get Service Gateway billing data for a company.
     *
     * Returns both per-case charges and monthly flat fees.
     */
    private function getServiceGatewayCasesData(
        Company $company,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $result = [
            'per_case' => null,
            'monthly_flat' => [],
        ];

        // Get active output configurations with billing
        $configs = ServiceOutputConfiguration::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('billing_mode', [
                ServiceOutputConfiguration::BILLING_MODE_PER_CASE,
                ServiceOutputConfiguration::BILLING_MODE_MONTHLY_FLAT,
            ])
            ->get();

        if ($configs->isEmpty()) {
            return $result;
        }

        // Process per-case billing
        $perCaseConfigs = $configs->filter(fn ($c) => $c->usesPerCaseBilling());
        if ($perCaseConfigs->isNotEmpty()) {
            // Get unbilled cases for this period
            $cases = ServiceCase::where('company_id', $company->id)
                ->unbilled()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where('output_status', ServiceCase::OUTPUT_SENT) // Only bill delivered cases
                ->get();

            if ($cases->isNotEmpty()) {
                $totalAmountCents = 0;
                $outputTypes = [];

                foreach ($cases as $case) {
                    // Find the output config that applies to this case (via category)
                    $config = $case->category?->outputConfiguration;
                    if ($config && $config->usesPerCaseBilling()) {
                        $totalAmountCents += $config->calculateCasePrice();
                        $outputTypes[$config->output_type] = true;
                    } else {
                        // Fallback to first per-case config
                        $fallbackConfig = $perCaseConfigs->first();
                        $totalAmountCents += $fallbackConfig->calculateCasePrice();
                        $outputTypes[$fallbackConfig->output_type] = true;
                    }
                }

                $caseCount = $cases->count();
                $avgUnitPrice = $caseCount > 0 ? (int) round($totalAmountCents / $caseCount) : 0;

                $result['per_case'] = [
                    'case_count' => $caseCount,
                    'total_amount_cents' => $totalAmountCents,
                    'average_unit_price_cents' => $avgUnitPrice,
                    'output_types' => implode(', ', array_keys($outputTypes)),
                ];
            }
        }

        // Process monthly flat billing
        $flatConfigs = $configs->filter(fn ($c) => $c->usesMonthlyFlatBilling());
        foreach ($flatConfigs as $config) {
            $result['monthly_flat'][] = [
                'config_id' => $config->id,
                'config_name' => $config->name,
                'amount_cents' => $config->monthly_flat_price_cents ?? 2900,
            ];
        }

        return $result;
    }
}
