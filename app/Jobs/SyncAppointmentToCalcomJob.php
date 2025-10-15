<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\CalcomV2Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Sync Appointment to Cal.com (Bidirectional Sync)
 *
 * This job handles syncing appointment changes from our database back to Cal.com
 * to maintain consistency and prevent double-bookings.
 *
 * Features:
 * - Loop Prevention: Checks sync_origin to avoid infinite webhook loops
 * - Retry Logic: 3 attempts with exponential backoff (1s, 5s, 30s)
 * - Error Tracking: Updates sync_status and flags for manual review
 * - Queue Support: Async processing with Laravel Horizon
 *
 * @see https://docs.cal.com/api-reference/v2
 */
class SyncAppointmentToCalcomJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts before permanent failure
     */
    public int $tries = 3;

    /**
     * Exponential backoff between retries (seconds)
     * First retry: 1s, Second: 5s, Third: 30s
     */
    public array $backoff = [1, 5, 30];

    /**
     * Job timeout (seconds)
     */
    public int $timeout = 30;

    /**
     * Appointment to sync
     */
    public Appointment $appointment;

    /**
     * Sync action: 'create' | 'cancel' | 'reschedule'
     */
    public string $action;

    /**
     * Create a new job instance
     *
     * @param Appointment $appointment Appointment to sync
     * @param string $action Sync action (create/cancel/reschedule)
     */
    public function __construct(Appointment $appointment, string $action)
    {
        $this->appointment = $appointment;
        $this->action = $action;

        // Store job ID for tracking
        $jobId = $this->job?->uuid() ?? uniqid('job_');

        $this->appointment->update([
            'sync_job_id' => $jobId,
            'calcom_sync_status' => 'pending',
            'last_sync_attempt_at' => now(),
        ]);
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::channel('calcom')->info('🔄 Starting Cal.com sync', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'attempt' => $this->attempts(),
            'sync_origin' => $this->appointment->sync_origin,
        ]);

        // ═══════════════════════════════════════════════════════════
        // CRITICAL: Loop Prevention Check
        // ═══════════════════════════════════════════════════════════
        if ($this->shouldSkipSync()) {
            Log::channel('calcom')->info('⏭️ Skipping sync (loop prevention)', [
                'appointment_id' => $this->appointment->id,
                'sync_origin' => $this->appointment->sync_origin,
                'reason' => 'Origin is calcom or already synced'
            ]);
            return;
        }

        try {
            $client = new CalcomV2Client($this->appointment->company);

            $response = match($this->action) {
                'create' => $this->syncCreate($client),
                'cancel' => $this->syncCancel($client),
                'reschedule' => $this->syncReschedule($client),
                default => throw new \InvalidArgumentException("Unknown action: {$this->action}")
            };

            if ($response->successful()) {
                $this->markSyncSuccess($response->json());
            } else {
                $this->handleSyncError($response->status(), $response->body());
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Check if sync should be skipped (Loop Prevention)
     *
     * @return bool True if sync should be skipped
     */
    protected function shouldSkipSync(): bool
    {
        // Skip if origin is 'calcom' (already in Cal.com)
        if ($this->appointment->sync_origin === 'calcom') {
            return true;
        }

        // Skip if already synced recently (within last 30 seconds)
        if ($this->appointment->calcom_sync_status === 'synced' &&
            $this->appointment->sync_verified_at &&
            $this->appointment->sync_verified_at->isAfter(now()->subSeconds(30))) {
            return true;
        }

        // Skip if pending with active job (avoid duplicate jobs)
        if ($this->appointment->calcom_sync_status === 'pending' &&
            $this->appointment->sync_job_id &&
            $this->appointment->sync_job_id !== ($this->job?->uuid() ?? uniqid('job_'))) {
            return true;
        }

        return false;
    }

    /**
     * Sync CREATE action to Cal.com
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response
     */
    protected function syncCreate(CalcomV2Client $client)
    {
        $service = $this->appointment->service;

        if (!$service || !$service->calcom_event_type_id) {
            throw new \RuntimeException("Service has no Cal.com event type (service_id: {$service?->id})");
        }

        // Build booking payload
        $payload = [
            'eventTypeId' => $service->calcom_event_type_id,
            'start' => $this->appointment->starts_at->toIso8601String(),
            'end' => $this->appointment->ends_at->toIso8601String(),
            'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin',
            'name' => $this->appointment->customer->name ?? 'Customer',
            'email' => $this->appointment->customer->email ?? 'noreply@example.com',
            'metadata' => [
                'crm_appointment_id' => $this->appointment->id,
                'sync_origin' => $this->appointment->sync_origin ?? 'system',
                'created_via' => 'crm_sync',
                'synced_at' => now()->toIso8601String(),
            ],
        ];

        // Add phone if available
        if ($this->appointment->customer->phone) {
            $payload['phone'] = $this->appointment->customer->phone;
        }

        Log::channel('calcom')->debug('📤 Sending CREATE to Cal.com', [
            'appointment_id' => $this->appointment->id,
            'event_type_id' => $service->calcom_event_type_id,
            'starts_at' => $payload['start'],
        ]);

        return $client->createBooking($payload);
    }

    /**
     * Sync CANCEL action to Cal.com
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response
     */
    protected function syncCancel(CalcomV2Client $client)
    {
        $calcomBookingId = $this->appointment->calcom_v2_booking_id;

        if (!$calcomBookingId) {
            throw new \RuntimeException("No Cal.com booking ID to cancel (appointment_id: {$this->appointment->id})");
        }

        Log::channel('calcom')->debug('📤 Sending CANCEL to Cal.com', [
            'appointment_id' => $this->appointment->id,
            'calcom_booking_id' => $calcomBookingId,
            'reason' => $this->appointment->cancellation_reason ?? 'Cancelled via CRM',
        ]);

        return $client->cancelBooking(
            bookingId: (int) $calcomBookingId,
            reason: $this->appointment->cancellation_reason ?? 'Cancelled via CRM'
        );
    }

    /**
     * Sync RESCHEDULE action to Cal.com
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response
     */
    protected function syncReschedule(CalcomV2Client $client)
    {
        $calcomBookingId = $this->appointment->calcom_v2_booking_id;

        if (!$calcomBookingId) {
            throw new \RuntimeException("No Cal.com booking ID to reschedule (appointment_id: {$this->appointment->id})");
        }

        Log::channel('calcom')->debug('📤 Sending RESCHEDULE to Cal.com', [
            'appointment_id' => $this->appointment->id,
            'calcom_booking_id' => $calcomBookingId,
            'new_start' => $this->appointment->starts_at->toIso8601String(),
        ]);

        return $client->rescheduleBooking(
            bookingId: (int) $calcomBookingId,
            data: [
                'start' => $this->appointment->starts_at->toIso8601String(),
                'end' => $this->appointment->ends_at->toIso8601String(),
                'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin',
                'reason' => 'Rescheduled via CRM',
            ]
        );
    }

    /**
     * Mark sync as successful
     *
     * @param array $responseData Response data from Cal.com API
     */
    protected function markSyncSuccess(array $responseData): void
    {
        $this->appointment->update([
            'calcom_sync_status' => 'synced',
            'sync_verified_at' => now(),
            'sync_error_message' => null,
            'sync_error_code' => null,
            'sync_job_id' => null,
            // Store Cal.com booking ID if creating
            'calcom_v2_booking_id' => $responseData['id'] ?? $this->appointment->calcom_v2_booking_id,
        ]);

        Log::channel('calcom')->info('✅ Cal.com sync successful', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'attempt' => $this->attempts(),
            'calcom_booking_id' => $responseData['id'] ?? null,
        ]);
    }

    /**
     * Handle sync error (API returned error)
     *
     * @param int $statusCode HTTP status code
     * @param string $body Response body
     */
    protected function handleSyncError(int $statusCode, string $body): void
    {
        $errorCode = "HTTP_{$statusCode}";
        $errorMessage = "Cal.com API error: {$body}";

        $this->appointment->update([
            'calcom_sync_status' => 'failed',
            'sync_error_code' => $errorCode,
            'sync_error_message' => substr($errorMessage, 0, 255),  // Limit length
            'sync_attempt_count' => $this->appointment->sync_attempt_count + 1,
        ]);

        // Flag for manual review if all retries exhausted
        if ($this->attempts() >= $this->tries) {
            $this->appointment->update([
                'requires_manual_review' => true,
                'manual_review_flagged_at' => now(),
            ]);

            Log::channel('calcom')->error('🚨 Cal.com sync permanently failed - manual review required', [
                'appointment_id' => $this->appointment->id,
                'action' => $this->action,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ]);
        }

        throw new \RuntimeException($errorMessage);
    }

    /**
     * Handle exception (network error, timeout, etc.)
     *
     * @param \Exception $e Exception thrown
     */
    protected function handleException(\Exception $e): void
    {
        Log::channel('calcom')->error('❌ Cal.com sync failed', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->appointment->update([
            'calcom_sync_status' => 'failed',
            'sync_error_code' => get_class($e),
            'sync_error_message' => substr($e->getMessage(), 0, 255),
            'sync_attempt_count' => $this->appointment->sync_attempt_count + 1,
        ]);

        // Flag for manual review if all retries exhausted
        if ($this->attempts() >= $this->tries) {
            $this->appointment->update([
                'requires_manual_review' => true,
                'manual_review_flagged_at' => now(),
            ]);

            Log::channel('calcom')->critical('🚨 Cal.com sync permanently failed after max retries', [
                'appointment_id' => $this->appointment->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
            ]);
        }

        throw $e;  // Re-throw to trigger retry
    }

    /**
     * Handle job failure (called after all retries exhausted)
     *
     * @param \Throwable $exception Exception that caused failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('calcom')->critical('💀 Cal.com sync job permanently failed', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Ensure manual review flag is set
        $this->appointment->update([
            'requires_manual_review' => true,
            'manual_review_flagged_at' => now(),
            'calcom_sync_status' => 'failed',
            'sync_job_id' => null,
        ]);

        // TODO: Send alert to monitoring system (Slack/PagerDuty)
        // TODO: Integrate with Sentry for error tracking
    }
}
