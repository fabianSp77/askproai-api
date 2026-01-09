<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\ServiceChangeFee;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fee Service
 *
 * Handles billing operations for:
 * - Setup fees (one-time on company onboarding)
 * - Service change fees (professional services)
 */
class FeeService
{
    /**
     * Charge setup fee for a newly onboarded company
     *
     * @param Company $company The company to charge
     * @return Transaction|null The created transaction, or null if already billed/no fee
     */
    public function chargeSetupFee(Company $company): ?Transaction
    {
        $feeSchedule = $company->feeSchedule;

        // Check if already billed
        if ($feeSchedule && $feeSchedule->isSetupFeeBilled()) {
            Log::info('Setup fee already billed', [
                'company_id' => $company->id,
                'billed_at' => $feeSchedule->setup_fee_billed_at,
            ]);
            return null;
        }

        // Determine setup fee amount
        $setupFeeAmount = $feeSchedule?->setup_fee ?? 0;

        // Fallback to pricing plan if no fee schedule or zero amount
        if ($setupFeeAmount <= 0 && $company->pricingPlan?->setup_fee > 0) {
            $setupFeeAmount = $company->pricingPlan->setup_fee;
        }

        if ($setupFeeAmount <= 0) {
            Log::info('No setup fee to charge', ['company_id' => $company->id]);
            return null;
        }

        return DB::transaction(function () use ($company, $feeSchedule, $setupFeeAmount) {
            $amountCents = (int) ($setupFeeAmount * 100);

            // Create transaction record
            $transaction = Transaction::create([
                'tenant_id' => $company->tenant_id ?? $company->id,
                'type' => Transaction::TYPE_FEE,
                'amount_cents' => -$amountCents, // Negative = debit
                'description' => 'Einrichtungsgebühr / Setup Fee',
                'metadata' => [
                    'fee_type' => 'setup',
                    'company_id' => $company->id,
                    'pricing_plan' => $company->pricingPlan?->name,
                    'amount_eur' => $setupFeeAmount,
                ],
            ]);

            // Create or update fee schedule
            if (!$feeSchedule) {
                $feeSchedule = CompanyFeeSchedule::create([
                    'company_id' => $company->id,
                    'setup_fee' => $setupFeeAmount,
                    'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
                ]);
            }

            // Mark as billed
            $feeSchedule->markSetupFeeBilled($transaction);

            Log::info('Setup fee charged', [
                'company_id' => $company->id,
                'amount_cents' => $amountCents,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    /**
     * Create a service change fee record
     *
     * @param Company $company
     * @param string $category One of ServiceChangeFee::CATEGORY_*
     * @param float $amount Amount in EUR
     * @param string $title
     * @param array $options Additional options (description, service_date, hours_worked, etc.)
     * @return ServiceChangeFee
     */
    public function createServiceChangeFee(
        Company $company,
        string $category,
        float $amount,
        string $title,
        array $options = []
    ): ServiceChangeFee {
        return ServiceChangeFee::create([
            'company_id' => $company->id,
            'category' => $category,
            'amount' => $amount,
            'title' => $title,
            'description' => $options['description'] ?? null,
            'service_date' => $options['service_date'] ?? now()->toDateString(),
            'hours_worked' => $options['hours_worked'] ?? null,
            'hourly_rate' => $options['hourly_rate'] ?? null,
            'related_entity_type' => $options['related_entity_type'] ?? null,
            'related_entity_id' => $options['related_entity_id'] ?? null,
            'created_by' => $options['created_by'] ?? auth()->id(),
            'metadata' => $options['metadata'] ?? null,
            'internal_notes' => $options['internal_notes'] ?? null,
        ]);
    }

    /**
     * Charge a service change fee (deduct from balance or create invoice item)
     *
     * @param ServiceChangeFee $fee
     * @param string $method 'balance' or 'invoice'
     * @return Transaction|null
     */
    public function chargeServiceChangeFee(ServiceChangeFee $fee, string $method = 'balance'): ?Transaction
    {
        if ($fee->status !== ServiceChangeFee::STATUS_PENDING) {
            Log::warning('Service change fee is not in pending status', [
                'fee_id' => $fee->id,
                'status' => $fee->status,
            ]);
            return null;
        }

        if ($method === 'balance') {
            return DB::transaction(function () use ($fee) {
                $transaction = Transaction::create([
                    'tenant_id' => $fee->company->tenant_id ?? $fee->company_id,
                    'type' => Transaction::TYPE_FEE,
                    'amount_cents' => -$fee->amount_cents,
                    'description' => "Service: {$fee->title}",
                    'service_change_fee_id' => $fee->id,
                    'metadata' => [
                        'fee_type' => 'service_change',
                        'category' => $fee->category,
                        'service_change_fee_id' => $fee->id,
                    ],
                ]);

                $fee->markAsPaid($transaction);

                Log::info('Service change fee charged to balance', [
                    'fee_id' => $fee->id,
                    'amount_cents' => $fee->amount_cents,
                    'transaction_id' => $transaction->id,
                ]);

                return $transaction;
            });
        }

        // For invoice method, just mark as invoiced (actual invoice creation handled elsewhere)
        return null;
    }

    /**
     * Get pending service change fees for a company
     *
     * @param Company $company
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingFeesForCompany(Company $company)
    {
        return ServiceChangeFee::where('company_id', $company->id)
            ->pending()
            ->orderBy('service_date', 'desc')
            ->get();
    }

    /**
     * Get total pending fees amount for a company
     *
     * @param Company $company
     * @return float Total in EUR
     */
    public function getPendingFeesTotal(Company $company): float
    {
        return (float) ServiceChangeFee::where('company_id', $company->id)
            ->pending()
            ->sum('amount');
    }

    /**
     * Waive a service change fee
     *
     * @param ServiceChangeFee $fee
     * @param int $userId
     * @param string $reason
     * @return ServiceChangeFee
     */
    public function waiveFee(ServiceChangeFee $fee, int $userId, string $reason): ServiceChangeFee
    {
        $fee->waive($userId, $reason);

        Log::info('Service change fee waived', [
            'fee_id' => $fee->id,
            'waived_by' => $userId,
            'reason' => $reason,
        ]);

        return $fee;
    }

    /**
     * Create default fee schedule for a company
     *
     * @param Company $company
     * @return CompanyFeeSchedule
     */
    public function createDefaultFeeSchedule(Company $company): CompanyFeeSchedule
    {
        $pricingPlan = $company->pricingPlan;

        return CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
            'setup_fee' => $pricingPlan?->setup_fee ?? 0,
        ]);
    }

    /**
     * Get or create fee schedule for a company
     *
     * @param Company $company
     * @return CompanyFeeSchedule
     */
    public function getOrCreateFeeSchedule(Company $company): CompanyFeeSchedule
    {
        return $company->feeSchedule ?? $this->createDefaultFeeSchedule($company);
    }
}
