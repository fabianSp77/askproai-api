<?php

namespace App\Listeners\Appointments;

use App\Events\Appointments\AppointmentPolicyViolation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Handle policy violations
 *
 * Actions taken:
 * - Log violation for audit trail
 * - Alert managers for high-severity violations
 * - Track violation patterns for policy optimization
 * - Trigger compliance workflows
 */
class TriggerPolicyEnforcement implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'enforcement';
    public $tries = 2;

    /**
     * Handle the event
     */
    public function handle(AppointmentPolicyViolation $event): void
    {
        try {
            $severity = $event->getSeverity();
            $context = $event->getContext();

            // Log violation with appropriate level
            match ($severity) {
                'high' => Log::warning('🚨 High-severity policy violation', $context),
                'medium' => Log::notice('⚠️  Policy violation detected', $context),
                default => Log::info('ℹ️  Minor policy violation', $context),
            };

            // Store violation in database for compliance
            $this->recordViolation($event);

            // Alert managers for high-severity violations
            if ($severity === 'high') {
                $this->alertManagers($event);
            }

            // Track violation patterns
            $this->trackViolationPattern($event);

        } catch (\Exception $e) {
            Log::error('❌ Failed to enforce policy violation', [
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
            ]);

            // Don't re-throw - enforcement is best-effort
            // Violation is already logged above
        }
    }

    /**
     * Record violation in database
     */
    private function recordViolation(AppointmentPolicyViolation $event): void
    {
        try {
            \DB::table('policy_violations')->insert([
                'appointment_id' => $event->appointment->id,
                'customer_id' => $event->appointment->customer_id,
                'company_id' => $event->appointment->company_id,
                'violation_type' => $event->attemptedAction,
                'violation_reason' => $event->policyResult->reason,
                'severity' => $event->getSeverity(),
                'policy_details' => json_encode($event->policyResult->details),
                'source' => $event->source,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table might not exist yet - just log
            Log::debug('Could not record policy violation in database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Alert managers of high-severity violations
     */
    private function alertManagers(AppointmentPolicyViolation $event): void
    {
        try {
            $branch = $event->appointment->branch;
            if (!$branch) {
                return;
            }

            $managers = $branch->staff()
                ->whereIn('role', ['manager', 'admin'])
                ->get();

            foreach ($managers as $manager) {
                // Send alert notification
                $manager->notify(new \App\Notifications\PolicyViolationAlert(
                    appointment: $event->appointment,
                    violationReason: $event->policyResult->reason,
                    severity: $event->getSeverity()
                ));
            }

        } catch (\Exception $e) {
            Log::warning('Failed to alert managers of policy violation', [
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track violation patterns for analytics
     */
    private function trackViolationPattern(AppointmentPolicyViolation $event): void
    {
        try {
            // Increment violation counter in cache
            $cacheKey = sprintf(
                'policy_violations:%s:%s:%s',
                $event->appointment->company_id,
                $event->attemptedAction,
                now()->format('Y-m-d')
            );

            $current = \Cache::get($cacheKey, 0);
            \Cache::put($cacheKey, $current + 1, 86400 * 30); // 30 days

            // Track per-customer violation frequency
            $customerKey = sprintf(
                'customer_violations:%s:%s',
                $event->appointment->customer_id,
                now()->format('Y-m')
            );

            $currentCustomer = \Cache::get($customerKey, 0);
            \Cache::put($customerKey, $currentCustomer + 1, 86400 * 60); // 60 days

        } catch (\Exception $e) {
            Log::debug('Failed to track violation pattern', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed job
     */
    public function failed(AppointmentPolicyViolation $event, \Throwable $exception): void
    {
        Log::error('🔥 Policy enforcement job permanently failed', [
            'appointment_id' => $event->appointment->id,
            'violation' => $event->policyResult->reason,
            'error' => $exception->getMessage(),
        ]);
    }
}
