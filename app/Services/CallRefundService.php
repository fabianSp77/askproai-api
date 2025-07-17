<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CallCharge;
use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallRefundService
{
    /**
     * Refund a single call
     *
     * @param Call $call
     * @param string $reason
     * @param float|null $amount Optional specific amount, otherwise full refund
     * @return CallCharge|null
     */
    public function refundCall(Call $call, string $reason, ?float $amount = null): ?CallCharge
    {
        $charge = CallCharge::where('call_id', $call->id)->first();
        
        if (!$charge) {
            // If no charge exists, create a charge first
            $charge = CallCharge::chargeCall($call);
            if (!$charge) {
                Log::warning('Could not create charge for call', ['call_id' => $call->id]);
                return null;
            }
        }

        if ($charge->refund_status !== 'none') {
            Log::warning('Attempted to refund already refunded call', ['call_id' => $call->id]);
            return null;
        }

        // Calculate refund amount
        $refundAmount = $amount ?? $charge->amount_charged;
        
        // Validate refund amount
        if ($refundAmount > $charge->amount_charged) {
            $refundAmount = $charge->amount_charged;
        }

        return $this->processRefund($charge, $refundAmount, $reason);
    }

    /**
     * Refund multiple calls
     *
     * @param array $callIds
     * @param string $reason
     * @param int $percentage Percentage to refund (0-100)
     * @return array Results with success/failure info
     */
    public function refundMultipleCalls(array $callIds, string $reason, int $percentage = 100): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total_refunded' => 0,
            'total_amount' => 0
        ];

        $charges = CallCharge::whereIn('call_id', $callIds)
            ->where('refund_status', 'none')
            ->get();

        foreach ($charges as $charge) {
            try {
                $refundAmount = ($charge->amount_charged * $percentage) / 100;
                $refundedCharge = $this->processRefund($charge, $refundAmount, $reason);
                
                if ($refundedCharge) {
                    $results['success'][] = $charge->call_id;
                    $results['total_refunded']++;
                    $results['total_amount'] += $refundAmount;
                } else {
                    $results['failed'][] = $charge->call_id;
                }
            } catch (\Exception $e) {
                Log::error('Error refunding call', [
                    'call_id' => $charge->call_id,
                    'error' => $e->getMessage()
                ]);
                $results['failed'][] = $charge->call_id;
            }
        }

        return $results;
    }

    /**
     * Process a refund for a call charge
     *
     * @param CallCharge $charge
     * @param float $refundAmount
     * @param string $reason
     * @return CallCharge|null
     */
    private function processRefund(CallCharge $charge, float $refundAmount, string $reason): ?CallCharge
    {
        return DB::transaction(function () use ($charge, $refundAmount, $reason) {
            // Get prepaid balance
            $balance = PrepaidBalance::where('company_id', $charge->company_id)->first();
            
            if (!$balance) {
                Log::error('No prepaid balance found for company', ['company_id' => $charge->company_id]);
                return null;
            }

            // Create refund transaction
            $transaction = $balance->addBalance(
                $refundAmount,
                sprintf('Gutschrift für Anruf #%s - %s', $charge->call_id, $reason),
                BalanceTransaction::TYPE_REFUND,
                $charge->call_id
            );

            // Update charge record
            $charge->update([
                'refunded_amount' => $refundAmount,
                'refund_status' => $refundAmount >= $charge->amount_charged ? 'full' : 'partial',
                'refunded_at' => now(),
                'refund_reason' => $reason,
                'refund_transaction_id' => $transaction->id
            ]);

            // Log the refund
            Log::info('Call refunded', [
                'call_id' => $charge->call_id,
                'company_id' => $charge->company_id,
                'amount' => $refundAmount,
                'reason' => $reason
            ]);

            return $charge;
        });
    }

    /**
     * Calculate total refund amount for multiple calls
     *
     * @param array $callIds
     * @param int $percentage
     * @return float
     */
    public function calculateRefundAmount(array $callIds, int $percentage = 100): float
    {
        $totalCharged = CallCharge::whereIn('call_id', $callIds)
            ->where('refund_status', 'none')
            ->sum('amount_charged');

        return ($totalCharged * $percentage) / 100;
    }

    /**
     * Get refund statistics for a company
     *
     * @param int $companyId
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return array
     */
    public function getRefundStatistics(int $companyId, $startDate = null, $endDate = null): array
    {
        $query = CallCharge::where('company_id', $companyId)
            ->where('refund_status', '!=', 'none');

        if ($startDate && $endDate) {
            $query->whereBetween('refunded_at', [$startDate, $endDate]);
        }

        $stats = [
            'total_refunds' => $query->count(),
            'total_amount' => $query->sum('refunded_amount'),
            'by_reason' => [],
            'by_status' => []
        ];

        // Group by reason
        $byReason = clone $query;
        $stats['by_reason'] = $byReason->groupBy('refund_reason')
            ->selectRaw('refund_reason, COUNT(*) as count, SUM(refunded_amount) as amount')
            ->get()
            ->keyBy('refund_reason')
            ->toArray();

        // Group by status
        $byStatus = clone $query;
        $stats['by_status'] = $byStatus->groupBy('refund_status')
            ->selectRaw('refund_status, COUNT(*) as count, SUM(refunded_amount) as amount')
            ->get()
            ->keyBy('refund_status')
            ->toArray();

        return $stats;
    }

    /**
     * Check if refund rate exceeds threshold
     *
     * @param int $companyId
     * @param float $threshold Percentage threshold (e.g., 2.0 for 2%)
     * @param int $days Number of days to check
     * @return array
     */
    public function checkRefundThreshold(int $companyId, float $threshold = 2.0, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        // Total calls
        $totalCalls = CallCharge::where('company_id', $companyId)
            ->where('charged_at', '>=', $startDate)
            ->count();

        // Refunded calls
        $refundedCalls = CallCharge::where('company_id', $companyId)
            ->where('charged_at', '>=', $startDate)
            ->where('refund_status', '!=', 'none')
            ->count();

        $percentage = $totalCalls > 0 ? ($refundedCalls / $totalCalls) * 100 : 0;

        return [
            'total_calls' => $totalCalls,
            'refunded_calls' => $refundedCalls,
            'refund_percentage' => round($percentage, 2),
            'threshold_exceeded' => $percentage > $threshold,
            'threshold' => $threshold
        ];
    }
}