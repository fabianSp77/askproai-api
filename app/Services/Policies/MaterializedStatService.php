<?php

namespace App\Services\Policies;

use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MaterializedStatService
 *
 * Manages pre-calculated customer modification statistics for O(1) policy enforcement lookups.
 * Updates materialized views hourly to avoid expensive real-time aggregations during policy checks.
 *
 * This service fixes CRITICAL-001 from FEATURE_AUDIT.md:
 * - Transforms O(n) "COUNT(*) WHERE created_at > NOW() - 30 days" into O(1) indexed lookup
 * - Enables fast quota enforcement (max_cancellations_per_month, etc.)
 * - Scheduled to run hourly via app/Console/Kernel.php
 */
class MaterializedStatService
{
    /**
     * Time windows for stat calculation
     */
    public const WINDOWS = [
        ['days' => 30, 'suffix' => '30d'],
        ['days' => 90, 'suffix' => '90d'],
    ];

    /**
     * Refresh stats for a single customer across all time windows
     *
     * @param Customer $customer
     * @return array Stats created/updated
     */
    public function refreshCustomerStats(Customer $customer): array
    {
        // Bind service context for Model protection (AppointmentModificationStat boot check)
        app()->bind('materializedStatService.updating', fn() => true);

        $stats = [];

        foreach (self::WINDOWS as $window) {
            $periodStart = Carbon::now()->subDays($window['days']);
            $periodEnd = Carbon::now();

            // Calculate cancellation count
            $cancelCount = AppointmentModification::where('customer_id', $customer->id)
                ->where('modification_type', 'cancel')
                ->where('created_at', '>=', $periodStart)
                ->count();

            $cancelStat = AppointmentModificationStat::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'stat_type' => "cancel_{$window['suffix']}",
                    'period_start' => $periodStart->toDateString(),
                ],
                [
                    'company_id' => $customer->company_id,
                    'period_end' => $periodEnd->toDateString(),
                    'count' => $cancelCount,
                    'calculated_at' => Carbon::now(),
                ]
            );

            $stats[] = [
                'stat_type' => "cancel_{$window['suffix']}",
                'count' => $cancelCount,
            ];

            // Calculate reschedule count
            $rescheduleCount = AppointmentModification::where('customer_id', $customer->id)
                ->where('modification_type', 'reschedule')
                ->where('created_at', '>=', $periodStart)
                ->count();

            $rescheduleStat = AppointmentModificationStat::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'stat_type' => "reschedule_{$window['suffix']}",
                    'period_start' => $periodStart->toDateString(),
                ],
                [
                    'company_id' => $customer->company_id,
                    'period_end' => $periodEnd->toDateString(),
                    'count' => $rescheduleCount,
                    'calculated_at' => Carbon::now(),
                ]
            );

            $stats[] = [
                'stat_type' => "reschedule_{$window['suffix']}",
                'count' => $rescheduleCount,
            ];
        }

        // Unbind service context
        app()->bind('materializedStatService.updating', fn() => false);

        return $stats;
    }

    /**
     * Refresh stats for all customers (batch processing)
     *
     * Uses chunking to avoid memory issues with large customer bases
     *
     * @param int $chunkSize Number of customers to process per batch
     * @return array Summary of processed customers
     */
    public function refreshAllStats(int $chunkSize = 100): array
    {
        $processed = 0;
        $errors = 0;
        $startTime = microtime(true);

        Customer::chunk($chunkSize, function ($customers) use (&$processed, &$errors) {
            foreach ($customers as $customer) {
                try {
                    $this->refreshCustomerStats($customer);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('MaterializedStatService: Failed to refresh stats for customer', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        $duration = round(microtime(true) - $startTime, 2);

        $summary = [
            'processed' => $processed,
            'errors' => $errors,
            'duration_seconds' => $duration,
            'rate_per_second' => $processed > 0 ? round($processed / $duration, 2) : 0,
        ];

        Log::info('MaterializedStatService: Batch refresh complete', $summary);

        return $summary;
    }

    /**
     * Clean up old stats (beyond 90-day window)
     *
     * Removes stats where period_end is older than 90 days
     * Scheduled to run daily at 3am
     *
     * @return int Number of records deleted
     */
    public function cleanupOldStats(): int
    {
        $cutoffDate = Carbon::now()->subDays(90);

        $deleted = AppointmentModificationStat::where('period_end', '<', $cutoffDate)
            ->delete();

        if ($deleted > 0) {
            Log::info('MaterializedStatService: Cleaned up old stats', [
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);
        }

        return $deleted;
    }

    /**
     * Get current stats for a customer (with automatic refresh if stale)
     *
     * @param Customer $customer
     * @param string $type 'cancel' or 'reschedule'
     * @param int $days 30 or 90
     * @return int Current count
     */
    public function getCustomerCount(Customer $customer, string $type, int $days = 30): int
    {
        $window = $days <= 30 ? '30d' : '90d';
        $statType = $type === 'cancel' ? "cancel_{$window}" : "reschedule_{$window}";

        $stat = AppointmentModificationStat::where('customer_id', $customer->id)
            ->where('stat_type', $statType)
            ->where('period_end', '>=', Carbon::now()->toDateString())
            ->first();

        // Auto-refresh if stat is stale (older than 2 hours)
        if (!$stat || $stat->calculated_at->lt(Carbon::now()->subHours(2))) {
            $this->refreshCustomerStats($customer);

            // Re-fetch after refresh
            $stat = AppointmentModificationStat::where('customer_id', $customer->id)
                ->where('stat_type', $statType)
                ->where('period_end', '>=', Carbon::now()->toDateString())
                ->first();
        }

        return $stat ? $stat->count : 0;
    }

    /**
     * Refresh stats for customers with recent modifications
     *
     * More efficient than refreshing ALL customers when only a few have new activity
     *
     * @param int $sinceMinutes Check for modifications in last N minutes
     * @return array Summary
     */
    public function refreshRecentlyActive(int $sinceMinutes = 60): array
    {
        $since = Carbon::now()->subMinutes($sinceMinutes);

        // Get unique customer IDs with modifications since cutoff
        $customerIds = AppointmentModification::where('created_at', '>=', $since)
            ->distinct()
            ->pluck('customer_id');

        $processed = 0;
        $errors = 0;

        foreach ($customerIds as $customerId) {
            try {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $this->refreshCustomerStats($customer);
                    $processed++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('MaterializedStatService: Failed to refresh recently active customer', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'since_minutes' => $sinceMinutes,
            'active_customers' => count($customerIds),
            'processed' => $processed,
            'errors' => $errors,
        ];
    }
}
