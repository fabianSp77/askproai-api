<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\CommissionLedger;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class CommissionCalculator
{
    /**
     * Commission rates based on monthly volume (in cents)
     */
    private array $volumeTiers = [
        0 => 25.0,           // 0-10k€: 25% commission
        1000000 => 27.5,     // 10k-50k€: 27.5% commission  
        5000000 => 30.0,     // 50k-100k€: 30% commission
        10000000 => 32.5,    // 100k+€: 32.5% commission
    ];

    /**
     * Special bonuses for performance
     */
    private array $performanceBonuses = [
        'new_customer' => 500,        // 5€ bonus per new customer
        'retention_3_months' => 1000, // 10€ bonus for 3-month retention
        'high_usage' => 200,          // 2€ bonus per high-usage customer
    ];

    /**
     * Calculate commission for a transaction
     */
    public function calculateCommission(Transaction $transaction): ?CommissionLedger
    {
        // Only calculate commission for usage transactions
        if ($transaction->type !== 'usage') {
            return null;
        }

        $customer = $transaction->tenant;
        
        // Check if customer belongs to a reseller
        if (!$customer->parent_id || $customer->tenant_type !== 'reseller_customer') {
            return null;
        }

        $reseller = Tenant::find($customer->parent_id);
        if (!$reseller || $reseller->tenant_type !== 'reseller') {
            return null;
        }

        // Calculate base commission
        $baseAmount = abs($transaction->amount_cents);
        $commissionRate = $this->getCommissionRate($reseller);
        $commissionAmount = round($baseAmount * ($commissionRate / 100));

        // Apply bonuses
        $bonuses = $this->calculateBonuses($reseller, $customer, $transaction);
        $totalCommission = $commissionAmount + $bonuses['total'];

        // Create commission ledger entry
        $ledger = CommissionLedger::create([
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'transaction_type' => $this->getTransactionType($transaction),
            'amount_cents' => $baseAmount,
            'commission_cents' => $totalCommission,
            'commission_rate' => $commissionRate,
            'bonus_cents' => $bonuses['total'],
            'bonus_details' => $bonuses['details'],
            'status' => 'pending',
            'period' => now()->format('Y-m'),
        ]);

        // Update reseller's pending commission balance
        $this->updateResellerBalance($reseller, $totalCommission);

        return $ledger;
    }

    /**
     * Get commission rate based on reseller's monthly volume
     */
    public function getCommissionRate(Tenant $reseller): float
    {
        // Get current month's volume
        $monthlyVolume = $this->getMonthlyVolume($reseller);

        // Find applicable tier
        $rate = 25.0; // Default rate
        foreach ($this->volumeTiers as $threshold => $tierRate) {
            if ($monthlyVolume >= $threshold) {
                $rate = $tierRate;
            }
        }

        // Check for custom rate override
        if ($reseller->custom_rates && isset($reseller->custom_rates['commission_rate'])) {
            $rate = $reseller->custom_rates['commission_rate'];
        }

        return $rate;
    }

    /**
     * Calculate bonuses for the transaction
     */
    private function calculateBonuses(Tenant $reseller, Tenant $customer, Transaction $transaction): array
    {
        $bonuses = [];
        $total = 0;

        // New customer bonus (first transaction)
        if ($this->isNewCustomer($customer)) {
            $bonuses['new_customer'] = $this->performanceBonuses['new_customer'];
            $total += $this->performanceBonuses['new_customer'];
        }

        // Retention bonus (customer active for 3+ months)
        if ($this->hasRetention($customer, 3)) {
            $bonuses['retention_3_months'] = $this->performanceBonuses['retention_3_months'];
            $total += $this->performanceBonuses['retention_3_months'];
        }

        // High usage bonus (>100€ in current month)
        if ($this->isHighUsageCustomer($customer)) {
            $bonuses['high_usage'] = $this->performanceBonuses['high_usage'];
            $total += $this->performanceBonuses['high_usage'];
        }

        return [
            'total' => $total,
            'details' => $bonuses
        ];
    }

    /**
     * Get reseller's monthly volume in cents
     */
    private function getMonthlyVolume(Tenant $reseller): int
    {
        return CommissionLedger::where('reseller_id', $reseller->id)
            ->where('period', now()->format('Y-m'))
            ->sum('amount_cents');
    }

    /**
     * Check if customer is new (first transaction)
     */
    private function isNewCustomer(Tenant $customer): bool
    {
        return Transaction::where('tenant_id', $customer->id)
            ->where('type', 'usage')
            ->count() === 1;
    }

    /**
     * Check if customer has been active for N months
     */
    private function hasRetention(Tenant $customer, int $months): bool
    {
        $firstTransaction = Transaction::where('tenant_id', $customer->id)
            ->where('type', 'usage')
            ->orderBy('created_at')
            ->first();

        if (!$firstTransaction) {
            return false;
        }

        return $firstTransaction->created_at->diffInMonths(now()) >= $months;
    }

    /**
     * Check if customer is high usage (>10000 cents in current month)
     */
    private function isHighUsageCustomer(Tenant $customer): bool
    {
        $monthlyUsage = Transaction::where('tenant_id', $customer->id)
            ->where('type', 'usage')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum(DB::raw('ABS(amount_cents)'));

        return $monthlyUsage > 10000; // 100€
    }

    /**
     * Update reseller's pending commission balance
     */
    private function updateResellerBalance(Tenant $reseller, int $commissionAmount): void
    {
        // Store in reseller's settings
        $settings = $reseller->settings ?? [];
        $settings['pending_commission'] = ($settings['pending_commission'] ?? 0) + $commissionAmount;
        $reseller->settings = $settings;
        $reseller->save();
    }

    /**
     * Process monthly payouts for all resellers
     */
    public function processMonthlyPayouts(): array
    {
        $results = [];
        $resellers = Tenant::where('tenant_type', 'reseller')->get();

        foreach ($resellers as $reseller) {
            $result = $this->processResellerPayout($reseller);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Process payout for a single reseller
     */
    public function processResellerPayout(Tenant $reseller): array
    {
        // Get all pending commissions for the reseller
        $pendingCommissions = CommissionLedger::where('reseller_id', $reseller->id)
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subDay()) // Only process commissions older than 24h
            ->get();

        $totalAmount = $pendingCommissions->sum('commission_cents');

        // Check minimum payout threshold (10€)
        if ($totalAmount < 1000) {
            return [
                'reseller_id' => $reseller->id,
                'status' => 'below_threshold',
                'amount' => $totalAmount,
                'message' => 'Betrag unter Mindestauszahlung von 10€'
            ];
        }

        DB::beginTransaction();
        try {
            // Mark commissions as processing
            $pendingCommissions->each(function ($commission) {
                $commission->update(['status' => 'processing']);
            });

            // Create payout record
            $payout = \App\Models\ResellerPayout::create([
                'reseller_id' => $reseller->id,
                'amount_cents' => $totalAmount,
                'commission_count' => $pendingCommissions->count(),
                'period' => now()->format('Y-m'),
                'status' => 'pending',
                'payout_method' => $reseller->payout_method ?? 'bank_transfer',
                'payout_details' => $reseller->payout_details ?? [],
            ]);

            // Queue payout processing (Stripe Connect or SEPA)
            dispatch(new \App\Jobs\ProcessResellerPayout($payout));

            DB::commit();

            return [
                'reseller_id' => $reseller->id,
                'status' => 'success',
                'amount' => $totalAmount,
                'payout_id' => $payout->id,
                'message' => 'Auszahlung wurde zur Verarbeitung eingereicht'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'reseller_id' => $reseller->id,
                'status' => 'error',
                'amount' => $totalAmount,
                'message' => 'Fehler bei Auszahlung: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get transaction type for commission tracking
     */
    private function getTransactionType(Transaction $transaction): string
    {
        if ($transaction->call_id) {
            return 'call_minutes';
        }
        if ($transaction->appointment_id) {
            return 'appointment';
        }
        return 'api_usage';
    }
}