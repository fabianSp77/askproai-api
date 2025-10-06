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
     * Business Rules:
     * 1. Must meet hours_before deadline from policy
     * 2. Must not exceed max_cancellations_per_month quota
     * 3. For composite appointments, use strictest policy among segments
     * 4. Fee calculated based on notice period
     */
    public function canCancel(Appointment $appointment, ?Carbon $now = null): PolicyResult
    {
        $now = $now ?? Carbon::now();

        // Get applicable policy
        $policy = $this->resolvePolicy($appointment, 'cancellation');

        if (!$policy) {
            // No policy = default allow with no fee
            return PolicyResult::allow(fee: 0.0, details: ['policy' => 'default']);
        }

        $hoursNotice = $now->diffInHours($appointment->starts_at, false);

        // Check 1: Deadline
        $requiredHours = $policy['hours_before'] ?? 0;
        if ($hoursNotice < $requiredHours) {
            $fee = $this->calculateFee($appointment, 'cancellation', $hoursNotice);
            return PolicyResult::deny(
                reason: "Cancellation requires {$requiredHours} hours notice. Only {$hoursNotice} hours remain.",
                details: [
                    'hours_notice' => $hoursNotice,
                    'required_hours' => $requiredHours,
                    'fee_if_forced' => $fee,
                ]
            );
        }

        // Check 2: Monthly quota
        $maxPerMonth = $policy['max_cancellations_per_month'] ?? null;
        if ($maxPerMonth !== null) {
            $recentCount = $this->getModificationCount(
                $appointment->customer_id,
                'cancel',
                30
            );

            if ($recentCount >= $maxPerMonth) {
                return PolicyResult::deny(
                    reason: "Monthly cancellation quota exceeded ({$recentCount}/{$maxPerMonth})",
                    details: [
                        'quota_used' => $recentCount,
                        'quota_max' => $maxPerMonth,
                    ]
                );
            }
        }

        // Calculate fee if applicable
        $fee = $this->calculateFee($appointment, 'cancellation', $hoursNotice);

        return PolicyResult::allow(
            fee: $fee,
            details: [
                'hours_notice' => $hoursNotice,
                'required_hours' => $requiredHours,
                'policy' => $policy,
            ]
        );
    }

    /**
     * Check if appointment can be rescheduled
     *
     * Business Rules:
     * 1. Must meet hours_before deadline from policy
     * 2. Must not exceed max_reschedules_per_appointment limit
     * 3. Fee calculated based on notice period
     */
    public function canReschedule(Appointment $appointment, ?Carbon $now = null): PolicyResult
    {
        $now = $now ?? Carbon::now();

        // Get applicable policy
        $policy = $this->resolvePolicy($appointment, 'reschedule');

        if (!$policy) {
            // No policy = default allow with no fee
            return PolicyResult::allow(fee: 0.0, details: ['policy' => 'default']);
        }

        $hoursNotice = $now->diffInHours($appointment->starts_at, false);

        // Check 1: Deadline
        $requiredHours = $policy['hours_before'] ?? 0;
        if ($hoursNotice < $requiredHours) {
            $fee = $this->calculateFee($appointment, 'reschedule', $hoursNotice);
            return PolicyResult::deny(
                reason: "Reschedule requires {$requiredHours} hours notice. Only {$hoursNotice} hours remain.",
                details: [
                    'hours_notice' => $hoursNotice,
                    'required_hours' => $requiredHours,
                    'fee_if_forced' => $fee,
                ]
            );
        }

        // Check 2: Per-appointment reschedule limit
        $maxPerAppointment = $policy['max_reschedules_per_appointment'] ?? null;
        if ($maxPerAppointment !== null) {
            $rescheduleCount = AppointmentModification::where('appointment_id', $appointment->id)
                ->where('modification_type', 'reschedule')
                ->count();

            if ($rescheduleCount >= $maxPerAppointment) {
                return PolicyResult::deny(
                    reason: "This appointment has been rescheduled {$rescheduleCount} times (max: {$maxPerAppointment})",
                    details: [
                        'reschedule_count' => $rescheduleCount,
                        'max_allowed' => $maxPerAppointment,
                    ]
                );
            }
        }

        // Calculate fee if applicable
        $fee = $this->calculateFee($appointment, 'reschedule', $hoursNotice);

        return PolicyResult::allow(
            fee: $fee,
            details: [
                'hours_notice' => $hoursNotice,
                'required_hours' => $requiredHours,
                'policy' => $policy,
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
