<?php

namespace App\Traits;

use App\Jobs\ProcessAlertJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait TracksPaymentFailures
{
    /**
     * Record a payment failure.
     */
    protected function recordPaymentFailure(
        string $paymentMethod,
        string $errorCode,
        string $errorMessage,
        ?string $customerId = null,
        ?array $metadata = []
    ): void {
        try {
            DB::table('payment_failures')->insert([
                'payment_method' => $paymentMethod,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'customer_id' => $customerId,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
            ]);

            Log::warning('Payment failure recorded', [
                'payment_method' => $paymentMethod,
                'error_code' => $errorCode,
                'customer_id' => $customerId,
            ]);

            // Dispatch alert job for monitoring
            ProcessAlertJob::dispatch('payment_failure', [
                'payment_method' => $paymentMethod,
                'error_code' => $errorCode,
            ])->onQueue('alerts');
        } catch (\Exception $e) {
            Log::error('Failed to record payment failure', [
                'error' => $e->getMessage(),
                'payment_error' => $errorCode,
            ]);
        }
    }

    /**
     * Get recent payment failures for analysis.
     */
    protected function getRecentPaymentFailures(int $minutes = 5): array
    {
        return DB::table('payment_failures')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Check if customer has too many payment failures.
     */
    protected function hasExcessivePaymentFailures(string $customerId, int $threshold = 3, int $minutes = 60): bool
    {
        $count = DB::table('payment_failures')
            ->where('customer_id', $customerId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();

        return $count >= $threshold;
    }
}
