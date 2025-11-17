<?php

namespace App\Services\Policy;

use App\Models\Branch;
use App\Models\Call;
use App\Models\PolicyConfiguration;
use App\ValueObjects\AnonymousCallDetector;
use Illuminate\Support\Facades\Log;

/**
 * BranchPolicyEnforcer
 *
 * ✅ Phase 2: Central policy enforcement service for branch-level operational policies
 *
 * Enforces three-tier policy hierarchy:
 * 1. **Hard-coded Security Rules** (HIGHEST PRIORITY - cannot be overridden)
 *    - Anonymous callers CANNOT: reschedule, cancel, inquiry
 *    - Anonymous callers CAN: book, check availability, service info, opening hours
 *
 * 2. **Branch-Level Policy Configuration** (PolicyConfiguration lookup)
 *    - Customizable per-branch operational policies
 *    - Can be overridden from company-level policies
 *
 * 3. **Default Behavior** (if no policy exists)
 *    - Permissive: Allow most operations
 *    - Only restrict where explicitly configured
 *
 * Performance:
 * - Uses PolicyConfiguration::getCachedPolicy() for O(1) lookups (~0.5ms cache hit)
 * - Integrates with AnonymousCallDetector (existing, well-tested component)
 *
 * Usage:
 * ```php
 * $enforcer = app(BranchPolicyEnforcer::class);
 * $result = $enforcer->isOperationAllowed($branch, $call, 'booking');
 *
 * if (!$result['allowed']) {
 *     return response()->json(['error' => $result['message']], 403);
 * }
 * ```
 */
class BranchPolicyEnforcer
{
    /**
     * Operation type mapping to policy types
     *
     * Maps simple operation strings to PolicyConfiguration constants
     */
    private const OPERATION_TO_POLICY_MAP = [
        'booking' => PolicyConfiguration::POLICY_TYPE_BOOKING,
        'reschedule' => PolicyConfiguration::POLICY_TYPE_RESCHEDULE,
        'cancel' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
        'appointment_inquiry' => PolicyConfiguration::POLICY_TYPE_APPOINTMENT_INQUIRY,
        'availability_inquiry' => PolicyConfiguration::POLICY_TYPE_AVAILABILITY_INQUIRY,
        'callback' => PolicyConfiguration::POLICY_TYPE_CALLBACK_SERVICE,
        'service_info' => PolicyConfiguration::POLICY_TYPE_SERVICE_INFORMATION,
        'opening_hours' => PolicyConfiguration::POLICY_TYPE_OPENING_HOURS,
    ];

    /**
     * Check if operation is allowed for branch and caller
     *
     * Enforcement hierarchy:
     * 1. Hard-coded security rules (anonymous caller restrictions)
     * 2. Branch-level policy configuration
     * 3. Default permissive behavior
     *
     * @param Branch $branch Branch for which to check policy
     * @param Call $call Call record (for caller identification)
     * @param string $operation Operation to check (booking, reschedule, cancel, etc.)
     * @return array{allowed: bool, reason?: string, message?: string, policy?: array}
     */
    public function isOperationAllowed(
        Branch $branch,
        Call $call,
        string $operation
    ): array {
        $isAnonymous = AnonymousCallDetector::isAnonymous($call);

        Log::info('🛡️ Policy Check', [
            'branch_id' => $branch->id,
            'call_id' => $call->id,
            'operation' => $operation,
            'is_anonymous' => $isAnonymous,
            'from_number' => $call->from_number,
        ]);

        // 1. HIGHEST PRIORITY: Check hard-coded security rules
        $securityCheck = $this->checkAnonymousSecurityRules($operation, $isAnonymous);
        if (!$securityCheck['allowed']) {
            Log::warning('🚨 Anonymous Security Rule Violation', [
                'branch_id' => $branch->id,
                'call_id' => $call->id,
                'operation' => $operation,
                'reason' => $securityCheck['reason'],
            ]);

            return $securityCheck;
        }

        // 2. Check branch-level policy configuration
        $policyType = $this->mapOperationToPolicyType($operation);
        if (!$policyType) {
            // Unknown operation - default to allow
            Log::warning('⚠️ Unknown operation type, defaulting to allow', [
                'operation' => $operation,
            ]);

            return ['allowed' => true, 'reason' => 'unknown_operation_default_allow'];
        }

        $policy = PolicyConfiguration::getCachedPolicy($branch, $policyType);

        if (!$policy) {
            // No policy configured - default to allow (permissive)
            Log::debug('✅ No policy found, defaulting to allow', [
                'branch_id' => $branch->id,
                'policy_type' => $policyType,
            ]);

            return ['allowed' => true, 'reason' => 'no_policy_default_allow'];
        }

        // 3. Evaluate policy configuration
        return $this->evaluatePolicy($policy, $call, $isAnonymous);
    }

    /**
     * Hard-coded security rules for anonymous callers
     *
     * CRITICAL SECURITY: These rules CANNOT be overridden by policy configuration
     *
     * Rationale (from AppointmentCustomerResolver security audit 2025-10-19):
     * - Without verified phone number, we cannot confirm caller identity
     * - Anonymous callers must not be able to modify/view others' appointments
     * - Prevents social engineering attacks ("I'm Max, cancel my appointment")
     *
     * Allowed for anonymous:
     * - booking: OK (new appointment creation, no existing data access)
     * - availability_inquiry: OK (public information, no PII)
     * - service_information: OK (public information)
     * - opening_hours: OK (public information)
     *
     * BLOCKED for anonymous:
     * - reschedule: Requires customer verification
     * - cancel: Requires customer verification
     * - appointment_inquiry: Contains PII, requires verification
     *
     * @param string $operation Operation to check
     * @param bool $isAnonymous Whether caller is anonymous
     * @return array{allowed: bool, reason?: string, message?: string}
     */
    private function checkAnonymousSecurityRules(string $operation, bool $isAnonymous): array
    {
        if (!$isAnonymous) {
            // Regular callers pass all security checks
            return ['allowed' => true];
        }

        // Anonymous caller restrictions
        $allowedForAnonymous = [
            'booking',
            'availability_inquiry',
            'service_info',
            'opening_hours',
            'callback',  // Callback requests are OK for anonymous (contact info captured)
        ];

        if (in_array($operation, $allowedForAnonymous)) {
            return ['allowed' => true, 'reason' => 'anonymous_allowed'];
        }

        // BLOCKED: Operations requiring identity verification
        return [
            'allowed' => false,
            'reason' => 'anonymous_caller_restricted',
            'message' => 'Diese Funktion erfordert eine verifizierte Telefonnummer. Bitte rufen Sie mit einer nicht unterdrückten Nummer an.',
            'security_rule' => 'HARD_CODED_ANONYMOUS_RESTRICTION',
        ];
    }

    /**
     * Map operation string to PolicyConfiguration constant
     *
     * @param string $operation Simple operation name
     * @return string|null PolicyConfiguration constant or null if unknown
     */
    private function mapOperationToPolicyType(string $operation): ?string
    {
        return self::OPERATION_TO_POLICY_MAP[$operation] ?? null;
    }

    /**
     * Evaluate policy configuration for specific call
     *
     * Checks policy config JSON for operation enablement and restrictions
     *
     * Expected policy config schema:
     * ```json
     * {
     *   "enabled": true,
     *   "allowed_hours": {
     *     "monday": ["09:00-20:00"],
     *     "tuesday": ["09:00-20:00"]
     *   },
     *   "disabled_message": "Diese Funktion ist derzeit nicht verfügbar."
     * }
     * ```
     *
     * @param PolicyConfiguration $policy Policy to evaluate
     * @param Call $call Call record
     * @param bool $isAnonymous Whether caller is anonymous
     * @return array{allowed: bool, reason?: string, message?: string, policy?: array}
     */
    private function evaluatePolicy(
        PolicyConfiguration $policy,
        Call $call,
        bool $isAnonymous
    ): array {
        $config = $policy->getEffectiveConfig();

        // Check if operation is explicitly disabled
        if (isset($config['enabled']) && !$config['enabled']) {
            Log::info('🛑 Policy explicitly disabled', [
                'policy_id' => $policy->id,
                'policy_type' => $policy->policy_type,
                'branch_id' => $policy->configurable_id,
            ]);

            return [
                'allowed' => false,
                'reason' => 'policy_disabled',
                'message' => $config['disabled_message'] ?? 'Diese Funktion ist derzeit nicht verfügbar.',
                'policy' => $config,
            ];
        }

        // Check time restrictions (allowed_hours)
        if (isset($config['allowed_hours'])) {
            $timeCheck = $this->checkTimeRestrictions($config['allowed_hours']);
            if (!$timeCheck['allowed']) {
                Log::info('⏰ Policy time restriction', [
                    'policy_id' => $policy->id,
                    'current_time' => now()->format('H:i'),
                    'current_day' => strtolower(now()->format('l')),
                ]);

                return $timeCheck;
            }
        }

        // Policy allows operation
        Log::debug('✅ Policy check passed', [
            'policy_id' => $policy->id,
            'policy_type' => $policy->policy_type,
        ]);

        return [
            'allowed' => true,
            'policy' => $config,
        ];
    }

    /**
     * Check if current time is within allowed hours
     *
     * @param array $allowedHours Time ranges by day of week
     * @return array{allowed: bool, reason?: string, message?: string}
     */
    private function checkTimeRestrictions(array $allowedHours): array
    {
        $now = now('Europe/Berlin');
        $dayOfWeek = strtolower($now->format('l')); // 'monday', 'tuesday', etc.
        $currentTime = $now->format('H:i');

        // Check if today has any allowed hours
        if (!isset($allowedHours[$dayOfWeek]) || empty($allowedHours[$dayOfWeek])) {
            return [
                'allowed' => false,
                'reason' => 'outside_allowed_hours',
                'message' => 'Diese Funktion ist heute nicht verfügbar.',
            ];
        }

        // Check if current time is within any allowed range
        foreach ($allowedHours[$dayOfWeek] as $timeRange) {
            if (is_string($timeRange) && str_contains($timeRange, '-')) {
                [$start, $end] = explode('-', $timeRange);

                if ($currentTime >= $start && $currentTime <= $end) {
                    return ['allowed' => true];
                }
            }
        }

        return [
            'allowed' => false,
            'reason' => 'outside_allowed_hours',
            'message' => 'Diese Funktion ist außerhalb der erlaubten Zeiten nicht verfügbar.',
        ];
    }
}
