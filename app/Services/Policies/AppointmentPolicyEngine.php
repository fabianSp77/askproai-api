<?php

namespace App\Services\Policies;

use App\Models\Appointment;
use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Models\Customer;
use App\ValueObjects\PolicyResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppointmentPolicyEngine
{
    public function __construct(
        private PolicyConfigurationService $policyService
    ) {
    }

    /**
     * Check if appointment can be cancelled
     *
     * Business Rules (ADR-005: Non-blocking Cancellation):
     * - Always allow cancellation (no cutoff)
     * - No quota limits
     * - Return reschedule_first_enabled flag for agent guidance
     * - No fees applied
     */
    public function canCancel(Appointment $appointment, ?Carbon $now = null): PolicyResult
    {
        $now = $now ?? Carbon::now();

        // Get applicable policy
        $policy = $this->resolvePolicy($appointment, 'cancellation');

        $hoursNotice = $now->diffInHours($appointment->starts_at, false);

        // Non-blocking: Always allow cancellation (ADR-005)
        // Reschedule-first always enabled for non-blocking policy
        return PolicyResult::allow(
            fee: 0.0,
            details: [
                'hours_notice' => $hoursNotice,
                'required_hours' => 0, // Non-blocking
                'policy' => $policy ?? 'default',
                'reschedule_first_enabled' => true, // ADR-005: Always offer reschedule first
            ]
        );
    }

    /**
     * Check if appointment can be rescheduled
     *
     * Business Rules (ADR-005: Non-blocking Cancellation):
     * - Always allow reschedule (no cutoff)
     * - No per-appointment limits
     * - Return reschedule_first_enabled flag for agent guidance
     * - No fees applied
     */
    public function canReschedule(Appointment $appointment, ?Carbon $now = null): PolicyResult
    {
        $now = $now ?? Carbon::now();

        // Get applicable policy
        $policy = $this->resolvePolicy($appointment, 'reschedule');

        $hoursNotice = $now->diffInHours($appointment->starts_at, false);

        // Non-blocking: Always allow reschedule (ADR-005)
        // Reschedule-first always enabled for non-blocking policy
        return PolicyResult::allow(
            fee: 0.0,
            details: [
                'hours_notice' => $hoursNotice,
                'required_hours' => 0, // Non-blocking
                'policy' => $policy ?? 'default',
                'reschedule_first_enabled' => true, // ADR-005: Always offer reschedule first
            ]
        );
    }

    /**
     * Calculate modification fee based on policy and notice period
     *
     * Default fee tiers (if not in policy):
     * - >48h: 0€
     * - 24-48h: 10€
     * - <24h: 15€
     */
    public function calculateFee(Appointment $appointment, string $modificationType, ?float $hoursNotice = null): float
    {
        $policy = $this->resolvePolicy($appointment, $modificationType);

        // Calculate hours notice if not provided
        if ($hoursNotice === null) {
            $now = Carbon::now();
            $hoursNotice = $now->diffInHours($appointment->starts_at, false);
        }

        // If policy exists, check for configured fees
        if ($policy) {
            // Check for fixed fee in policy
            if (isset($policy['fee'])) {
                return (float) $policy['fee'];
            }

            // Check for tiered fees in policy
            if (isset($policy['fee_tiers'])) {
                return $this->calculateTieredFee($hoursNotice, $policy['fee_tiers']);
            }

            // Check for percentage-based fee
            if (isset($policy['fee_percentage']) && isset($appointment->price)) {
                $percentage = (float) $policy['fee_percentage'];
                return round(($appointment->price * $percentage) / 100, 2);
            }
        }

        // Default tiered structure (applies even without policy)
        $defaultTiers = [
            ['min_hours' => 48, 'fee' => 0.0],
            ['min_hours' => 24, 'fee' => 10.0],
            ['min_hours' => 0, 'fee' => 15.0],
        ];

        return $this->calculateTieredFee($hoursNotice, $defaultTiers);
    }

    /**
     * Get remaining modification quota for customer
     *
     * @param Customer $customer
     * @param string $type 'cancel'/'cancellation' or 'reschedule'
     * @return int Remaining quota or PHP_INT_MAX if unlimited
     */
    public function getRemainingModifications(Customer $customer, string $type): int
    {
        // Normalize type for policy lookup
        $policyType = in_array($type, ['cancel', 'cancellation']) ? 'cancellation' : 'reschedule';
        $modType = in_array($type, ['cancel', 'cancellation']) ? 'cancel' : 'reschedule';

        // Get policy from customer's company
        $policy = $this->policyService->resolvePolicy($customer->company, $policyType);

        if (!$policy) {
            return PHP_INT_MAX; // No limit
        }

        $maxPerMonth = $policy['max_cancellations_per_month'] ?? $policy['max_reschedules_per_month'] ?? null;

        if ($maxPerMonth === null) {
            return PHP_INT_MAX; // No limit
        }

        $used = $this->getModificationCount($customer->id, $modType, 30);

        return max(0, $maxPerMonth - $used);
    }

    /**
     * Resolve policy for appointment
     *
     * Policy hierarchy (most specific wins):
     * 1. Staff (if assigned)
     * 2. Service (if assigned)
     * 3. Branch
     * 4. Company
     */
    private function resolvePolicy(Appointment $appointment, string $policyType): ?array
    {
        // Try staff first
        if ($appointment->staff) {
            $policy = $this->policyService->resolvePolicy($appointment->staff, $policyType);
            if ($policy) {
                return $policy;
            }
        }

        // Try service
        if ($appointment->service ?? null) {
            $policy = $this->policyService->resolvePolicy($appointment->service, $policyType);
            if ($policy) {
                return $policy;
            }
        }

        // Try branch
        if ($appointment->branch) {
            $policy = $this->policyService->resolvePolicy($appointment->branch, $policyType);
            if ($policy) {
                return $policy;
            }
        }

        // Try company
        if ($appointment->company) {
            $policy = $this->policyService->resolvePolicy($appointment->company, $policyType);
            if ($policy) {
                return $policy;
            }
        }

        return null;
    }

    /**
     * Calculate fee from tiered structure
     */
    private function calculateTieredFee(float $hoursNotice, array $tiers): float
    {
        // Sort tiers by min_hours descending
        usort($tiers, fn($a, $b) => ($b['min_hours'] ?? 0) <=> ($a['min_hours'] ?? 0));

        foreach ($tiers as $tier) {
            if ($hoursNotice >= ($tier['min_hours'] ?? 0)) {
                return (float) ($tier['fee'] ?? 0);
            }
        }

        // Fallback to last tier (lowest hours)
        return (float) (end($tiers)['fee'] ?? 0);
    }

    /**
     * Get modification count in rolling window
     *
     * @param int $customerId
     * @param string $type 'cancel' or 'reschedule'
     * @param int $days
     * @return int
     */
    private function getModificationCount(int $customerId, string $type, int $days): int
    {
        // Determine stat_type based on time window (30 days or 90 days)
        // Match AppointmentModificationStat::STAT_TYPES
        $window = $days <= 30 ? '30d' : '90d';
        $statType = $type === 'cancel'
            ? "cancel_{$window}"
            : "reschedule_{$window}";

        // Try to use materialized stats first for performance (O(1) lookup)
        $stat = AppointmentModificationStat::where('customer_id', $customerId)
            ->where('stat_type', $statType)
            ->where('period_end', '>=', Carbon::now()->toDateString())
            ->first();

        if ($stat) {
            return $stat->count;
        }

        // Fallback to real-time count
        return AppointmentModification::where('customer_id', $customerId)
            ->where('modification_type', $type)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->count();
    }
}
