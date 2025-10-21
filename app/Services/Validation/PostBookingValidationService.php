<?php

namespace App\Services\Validation;

use App\Models\Call;
use App\Models\Appointment;
use App\Services\Monitoring\DataConsistencyMonitor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Post-Booking Validation Service
 *
 * Verifies that appointments actually exist in database after Cal.com booking succeeds.
 * Provides rollback capabilities and retry logic with exponential backoff.
 *
 * FEATURES:
 * - Post-creation validation
 * - Automatic rollback on failure
 * - Exponential backoff retry
 * - Detailed validation reporting
 *
 * USAGE:
 * $validation = $this->postValidation->validateAppointmentCreation($call, $appointmentId, $calcomBookingId);
 * if (!$validation->success) {
 *     $this->postValidation->rollbackOnFailure($call, $validation->reason);
 * }
 */
class PostBookingValidationService
{
    private DataConsistencyMonitor $consistencyMonitor;

    // Validation thresholds
    private const MAX_APPOINTMENT_AGE_SECONDS = 300; // 5 minutes
    private const MAX_RETRY_ATTEMPTS = 3;
    private const BASE_DELAY_SECONDS = 1;

    public function __construct(DataConsistencyMonitor $consistencyMonitor)
    {
        $this->consistencyMonitor = $consistencyMonitor;
    }

    /**
     * Validate that appointment was successfully created and linked
     *
     * @param Call $call The call that triggered the booking
     * @param int|null $appointmentId Expected appointment ID
     * @param string|null $calcomBookingId Expected Cal.com booking ID
     * @return ValidationResult
     */
    public function validateAppointmentCreation(
        Call $call,
        ?int $appointmentId = null,
        ?string $calcomBookingId = null
    ): ValidationResult {
        Log::info('🔍 Starting post-booking validation', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
            'expected_appointment_id' => $appointmentId,
            'expected_calcom_booking_id' => $calcomBookingId
        ]);

        // Validation 1: Check if appointment exists by ID
        if ($appointmentId) {
            $appointment = Appointment::find($appointmentId);

            if (!$appointment) {
                return $this->createFailureResult(
                    'appointment_not_found',
                    'Appointment record not found in database',
                    [
                        'expected_id' => $appointmentId,
                        'call_id' => $call->id
                    ]
                );
            }
        } else {
            // Try to find appointment by call_id relationship
            $appointment = Appointment::where('call_id', $call->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$appointment) {
                return $this->createFailureResult(
                    'appointment_not_linked',
                    'No appointment found for call_id',
                    [
                        'call_id' => $call->id,
                        'retell_call_id' => $call->retell_call_id
                    ]
                );
            }
        }

        // Validation 2: Check appointment is linked to call
        if ($appointment->call_id !== $call->id) {
            return $this->createFailureResult(
                'appointment_wrong_call',
                'Appointment linked to different call',
                [
                    'appointment_id' => $appointment->id,
                    'appointment_call_id' => $appointment->call_id,
                    'expected_call_id' => $call->id
                ]
            );
        }

        // Validation 3: Check Cal.com booking ID matches
        if ($calcomBookingId && $appointment->calcom_v2_booking_id !== $calcomBookingId) {
            return $this->createFailureResult(
                'calcom_booking_id_mismatch',
                'Cal.com booking ID does not match',
                [
                    'appointment_id' => $appointment->id,
                    'appointment_calcom_id' => $appointment->calcom_v2_booking_id,
                    'expected_calcom_id' => $calcomBookingId
                ]
            );
        }

        // Validation 4: Check appointment was created recently (within last 5 minutes)
        $appointmentAge = now()->diffInSeconds($appointment->created_at);
        if ($appointmentAge > self::MAX_APPOINTMENT_AGE_SECONDS) {
            return $this->createFailureResult(
                'appointment_too_old',
                'Appointment timestamp is too old',
                [
                    'appointment_id' => $appointment->id,
                    'created_at' => $appointment->created_at->toIso8601String(),
                    'age_seconds' => $appointmentAge,
                    'max_age_seconds' => self::MAX_APPOINTMENT_AGE_SECONDS
                ]
            );
        }

        // Validation 5: Check call flags are consistent
        $flagsConsistent = $this->validateCallFlags($call);
        if (!$flagsConsistent->success) {
            return $flagsConsistent;
        }

        // All validations passed
        Log::info('✅ Post-booking validation successful', [
            'call_id' => $call->id,
            'appointment_id' => $appointment->id,
            'calcom_booking_id' => $appointment->calcom_v2_booking_id,
            'validation_duration_ms' => $this->getElapsedMs()
        ]);

        return $this->createSuccessResult($appointment);
    }

    /**
     * Validate call flags are consistent with appointment creation
     *
     * @param Call $call
     * @return ValidationResult
     */
    private function validateCallFlags(Call $call): ValidationResult
    {
        $issues = [];

        // Check appointment_made flag
        if (!$call->appointment_made) {
            $issues[] = 'appointment_made is false';
        }

        // Check session_outcome
        if ($call->session_outcome !== 'appointment_booked') {
            $issues[] = "session_outcome is '{$call->session_outcome}' instead of 'appointment_booked'";
        }

        // Check appointment_link_status
        if ($call->appointment_link_status !== 'linked') {
            $issues[] = "appointment_link_status is '{$call->appointment_link_status}' instead of 'linked'";
        }

        if (!empty($issues)) {
            return $this->createFailureResult(
                'call_flags_inconsistent',
                'Call flags are inconsistent with appointment creation',
                [
                    'call_id' => $call->id,
                    'issues' => $issues,
                    'appointment_made' => $call->appointment_made,
                    'session_outcome' => $call->session_outcome,
                    'appointment_link_status' => $call->appointment_link_status
                ]
            );
        }

        return $this->createSuccessResult(null);
    }

    /**
     * Rollback call flags when appointment creation fails
     *
     * @param Call $call
     * @param string $reason Failure reason for logging
     * @return void
     */
    public function rollbackOnFailure(Call $call, string $reason): void
    {
        Log::warning('🔄 Rolling back call flags due to appointment validation failure', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
            'reason' => $reason,
            'original_appointment_made' => $call->appointment_made,
            'original_session_outcome' => $call->session_outcome
        ]);

        DB::transaction(function () use ($call, $reason) {
            $call->update([
                'appointment_made' => false,
                'session_outcome' => 'creation_failed',
                'appointment_link_status' => 'creation_failed',
                'booking_failed' => true,
                'booking_failure_reason' => $reason,
                'requires_manual_processing' => true,
            ]);

            // Record rollback event
            DB::table('data_consistency_alerts')->insert([
                'alert_type' => 'appointment_rollback',
                'entity_type' => 'call',
                'entity_id' => $call->id,
                'description' => "Rolled back appointment flags: {$reason}",
                'metadata' => json_encode([
                    'retell_call_id' => $call->retell_call_id,
                    'rollback_reason' => $reason,
                    'rolled_back_at' => now()->toIso8601String()
                ]),
                'detected_at' => now(),
                'auto_corrected' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        // Trigger monitoring alert
        $this->consistencyMonitor->alertInconsistency(
            'appointment_validation_failed_rollback',
            [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'reason' => $reason,
                'action_taken' => 'flags_rolled_back'
            ]
        );

        Log::info('✅ Call flags rolled back successfully', [
            'call_id' => $call->id,
            'appointment_made' => $call->appointment_made,
            'session_outcome' => $call->session_outcome
        ]);
    }

    /**
     * Retry operation with exponential backoff
     *
     * @param callable $operation The operation to retry
     * @param int $maxAttempts Maximum number of attempts
     * @return mixed Operation result
     * @throws \Exception Last exception if all retries fail
     */
    public function retryWithBackoff(callable $operation, int $maxAttempts = self::MAX_RETRY_ATTEMPTS)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                Log::debug('🔄 Retry attempt', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts
                ]);

                $result = $operation();

                // Success - log and return
                Log::info('✅ Retry operation succeeded', [
                    'attempt' => $attempt,
                    'total_attempts' => $maxAttempts
                ]);

                return $result;

            } catch (\Exception $e) {
                $lastException = $e;

                Log::warning('⚠️ Retry attempt failed', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e)
                ]);

                if ($attempt >= $maxAttempts) {
                    Log::error('❌ All retry attempts exhausted', [
                        'total_attempts' => $maxAttempts,
                        'last_error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    break; // Give up after max attempts
                }

                // Calculate delay with exponential backoff + jitter
                $baseDelay = self::BASE_DELAY_SECONDS * pow(2, $attempt - 1); // 1s, 2s, 4s
                $jitter = random_int(0, (int)($baseDelay * 100)); // 0-10% jitter
                $delaySec = $baseDelay + ($jitter / 1000);

                Log::debug('⏳ Waiting before retry', [
                    'delay_seconds' => $delaySec,
                    'base_delay' => $baseDelay,
                    'jitter_ms' => $jitter
                ]);

                usleep((int)($delaySec * 1000000)); // Convert to microseconds
            }
        }

        // All retries exhausted - throw last exception
        throw $lastException;
    }

    /**
     * Create success validation result
     *
     * @param Appointment|null $appointment
     * @return ValidationResult
     */
    private function createSuccessResult(?Appointment $appointment): ValidationResult
    {
        return new ValidationResult(
            success: true,
            reason: null,
            details: [
                'appointment_id' => $appointment?->id,
                'validated_at' => now()->toIso8601String()
            ]
        );
    }

    /**
     * Create failure validation result
     *
     * @param string $reason
     * @param string $description
     * @param array $details
     * @return ValidationResult
     */
    private function createFailureResult(string $reason, string $description, array $details = []): ValidationResult
    {
        Log::error('❌ Post-booking validation failed', [
            'reason' => $reason,
            'description' => $description,
            'details' => $details
        ]);

        return new ValidationResult(
            success: false,
            reason: $reason,
            details: array_merge($details, [
                'description' => $description,
                'validated_at' => now()->toIso8601String()
            ])
        );
    }

    /**
     * Get elapsed time in milliseconds (for performance tracking)
     *
     * @return float
     */
    private function getElapsedMs(): float
    {
        static $startTime;
        if (!$startTime) {
            $startTime = microtime(true);
        }
        return (microtime(true) - $startTime) * 1000;
    }
}

/**
 * Validation Result DTO
 */
class ValidationResult
{
    public function __construct(
        public bool $success,
        public ?string $reason = null,
        public array $details = []
    ) {}
}
