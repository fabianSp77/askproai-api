<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\CalcomV2Client;
use App\Support\SafeLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SafeLogger;

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
     * Job ID for this sync operation (cached to avoid multiple uniqid() calls)
     */
    private ?string $jobId = null;

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

        // 🐛 FIX (2025-11-17): Don't set sync_job_id in constructor
        // $this->job is NULL here, causing UUID mismatch in shouldSkipSync()
        // Job ID will be set in handle() where we have the real job instance
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        // 🔒 RACE CONDITION FIX (RC3): Acquire pessimistic lock on appointment row
        // This prevents concurrent reschedule/cancel operations from corrupting state
        // FIX 2025-11-17: Use find() with lockForUpdate() instead of calling first() on model
        $this->appointment = Appointment::lockForUpdate()->find($this->appointment->id);

        if (!$this->appointment) {
            $this->safeWarning('⚠️ Appointment not found during sync (may have been deleted)', [
                'appointment_id' => $this->appointment?->id ?? 'unknown',
                'action' => $this->action,
            ], 'calcom');
            return;
        }

        // 🚀 PHASE 2 FIX (2025-11-17): Load required relations for Cal.com sync
        // Without this, service->calcom_event_type_id is not available
        $this->appointment->load('service', 'customer', 'company');

        // 🐛 FIX (2025-11-17): Set job ID here where we have the real job instance
        // This prevents UUID mismatch in shouldSkipSync() duplicate job check
        $jobId = $this->getJobId();
        $this->appointment->update([
            'sync_job_id' => $jobId,
            'calcom_sync_status' => 'pending',
            'last_sync_attempt_at' => now(),
        ]);

        $this->safeInfo('🔄 Starting Cal.com sync', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'attempt' => $this->attempts(),
            'sync_origin' => $this->appointment->sync_origin,
            'job_id' => $jobId,
        ], 'calcom');

        // ═══════════════════════════════════════════════════════════
        // CRITICAL: Loop Prevention Check
        // ═══════════════════════════════════════════════════════════
        if ($this->shouldSkipSync()) {
            $this->safeInfo('⏭️ Skipping sync (loop prevention)', [
                'appointment_id' => $this->appointment->id,
                'sync_origin' => $this->appointment->sync_origin,
                'reason' => 'Origin is calcom or already synced'
            ], 'calcom');
            return;
        }

        try {
            $client = new CalcomV2Client($this->appointment->company);

            // Composite service detection for CREATE action
            if ($this->action === 'create' && $this->appointment->service->isComposite()) {
                $response = $this->syncCreateComposite($client);
            } else {
                $response = match($this->action) {
                    'create' => $this->syncCreate($client),
                    'cancel' => $this->syncCancel($client),
                    'reschedule' => $this->syncReschedule($client),
                    default => throw new \InvalidArgumentException("Unknown action: {$this->action}")
                };
            }

            if ($response->successful()) {
                // 🔧 FIX 2025-11-17: Cal.com V2 API wraps data in 'data' key
                // Response format: {"status":"success", "data": {"id": 12846550, ...}}
                $responseData = $response->json('data', []);
                $this->markSyncSuccess($responseData);
            } else {
                $this->handleSyncError($response->status(), $response->body());
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Get consistent job ID for this sync operation
     *
     * Returns the queue job UUID if available, otherwise generates and caches a unique ID.
     * This ensures the same ID is used throughout the job lifecycle.
     *
     * @return string Job ID
     */
    private function getJobId(): string
    {
        if ($this->jobId === null) {
            // Prefer queue job UUID, fallback to cryptographically secure random ID
            // Note: uniqid() is not collision-safe under high concurrency
            $this->jobId = $this->job?->uuid() ?? ('sync_' . bin2hex(random_bytes(12)));
        }
        return $this->jobId;
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
            $this->appointment->sync_job_id !== $this->getJobId()) {
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

        // 🔧 FIX 2025-11-17: Build V2 API payload (correct format)
        // Cal.com V2 requires: eventTypeId (int), start (ISO8601), attendee{name, email, timeZone}
        // REQUIRED: bookingFieldsResponses.title (Cal.com form field)
        // NO: end, responses, instant, noEmail (those were V1)
        $payload = [
            'eventTypeId' => $service->calcom_event_type_id, // Will be cast to int in client
            'start' => $this->appointment->starts_at->toIso8601String(),
            'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin', // For client to use in attendee
            'name' => $this->appointment->customer->name ?? 'Customer',
            'email' => $this->appointment->customer->email ?? 'noreply@example.com',
            'metadata' => [
                'crm_appointment_id' => $this->appointment->id,
                'sync_origin' => $this->appointment->sync_origin ?? 'system',
                'created_via' => 'crm_sync',
                'synced_at' => now()->toIso8601String(),
            ],
            // 🔧 FIX 2025-11-17: Cal.com requires 'title' in bookingFieldsResponses
            // Error: "responses - {title}error_required_field"
            'bookingFieldsResponses' => [
                'title' => $service->name ?? 'Termin',
            ],
        ];

        // 🚧 TEMPORARY FIX 2025-11-17: SKIP phone due to Cal.com validation error
        // Error: "responses - {attendeePhoneNumber}invalid_number"
        // TODO: Research correct phone format for Cal.com (E.164? Different format?)
        // Phone is OPTIONAL - bookings work without it
        /*
        if ($this->appointment->customer->phone) {
            $payload['phone'] = $this->appointment->customer->phone;
        }
        */

        // Add notes if available
        if ($this->appointment->notes) {
            $payload['bookingFieldsResponses']['notes'] = $this->appointment->notes;
        }

        $this->safeDebug('📤 Sending CREATE to Cal.com', [
            'appointment_id' => $this->appointment->id,
            'event_type_id' => $service->calcom_event_type_id,
            'starts_at' => $payload['start'],
            'FULL_PAYLOAD' => $payload, // DEBUG: See exact payload
        ], 'calcom');

        return $client->createBooking($payload);
    }

    /**
     * Sync CREATE action for composite service appointments
     *
     * Creates separate Cal.com bookings for each active segment (staff_required=true).
     * Gap segments (processing time) are NOT synced to Cal.com.
     *
     * Process:
     * 1. Get all phases where staff_required = true
     * 2. For each phase:
     *    - Lookup CalcomEventMap (service_id, segment_key, staff_id)
     *    - Create Cal.com booking with segment-specific event_type_id
     *    - Store booking_id in AppointmentPhase
     * 3. Handle partial failures gracefully (continue with remaining segments)
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response Last response (for compatibility)
     * @throws \RuntimeException if no phases or all phases fail
     */
    protected function syncCreateComposite(CalcomV2Client $client)
    {
        $service = $this->appointment->service;

        // Get active phases only (where staff_required = true)
        $phases = $this->appointment->phases()
            ->where('staff_required', true)
            ->orderBy('sequence_order')
            ->get();

        if ($phases->isEmpty()) {
            // 🔧 FIX 2025-11-26: Fallback to single booking when composite service has no phases
            // This handles cases where services are marked composite: true but phases weren't created
            // (e.g., Service 441 "Dauerwelle" - composite flag without phase configuration)
            $this->safeInfo("⚠️ Composite service has no phases - falling back to single booking", [
                'appointment_id' => $this->appointment->id,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'note' => 'Service marked as composite but no phases with staff_required=true'
            ], 'calcom');

            // Delegate to standard single-booking method
            return $this->syncCreate($client);
        }

        $this->safeInfo("🔧 Syncing composite appointment: {$phases->count()} active segments", [
            'appointment_id' => $this->appointment->id,
            'service_id' => $service->id,
            'staff_id' => $this->appointment->staff_id,
            'total_phases' => $phases->count()
        ], 'calcom');

        $bookingIds = [];
        $errors = [];
        $lastResponse = null;

        // 🚀 PERFORMANCE: Use parallel execution if enabled (70% faster)
        if (config('features.parallel_calcom_booking', true)) {
            return $this->syncPhasesParallel($phases, $service, $client);
        }

        // LEGACY: Sequential execution (kept for rollback safety)
        foreach ($phases as $phase) {
            try {
                // Lookup CalcomEventMap for this segment + staff
                $mapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
                    ->where('segment_key', $phase->segment_key)
                    ->where('staff_id', $this->appointment->staff_id)
                    ->first();

                if (!$mapping) {
                    $error = "Missing CalcomEventMap for segment '{$phase->segment_key}'";
                    $this->safeError("❌ {$error}", [
                        'service_id' => $service->id,
                        'segment_key' => $phase->segment_key,
                        'staff_id' => $this->appointment->staff_id
                    ], 'calcom');

                    $errors[] = $error;
                    $phase->update([
                        'calcom_sync_status' => 'failed',
                        'sync_error_message' => $error
                    ]);
                    continue;
                }

                // Resolve child event type ID for MANAGED event types
                // Cal.com creates parent MANAGED event types as templates that cannot be booked.
                // Each staff member gets a child event type ID that must be used for bookings.

                // Priority 1: Use pre-resolved child_event_type_id from mapping if available
                if ($mapping->child_event_type_id) {
                    $childEventTypeId = $mapping->child_event_type_id;

                    $this->safeDebug("🔍 Using pre-resolved child event type ID from mapping", [
                        'parent_id' => $mapping->event_type_id,
                        'child_id' => $childEventTypeId,
                        'segment_key' => $phase->segment_key,
                        'source' => 'calcom_event_map.child_event_type_id'
                    ], 'calcom');
                } else {
                    // Priority 2: Resolve dynamically using CalcomChildEventTypeResolver
                    $resolver = new \App\Services\CalcomChildEventTypeResolver($this->appointment->company);

                    try {
                        $childEventTypeId = $resolver->resolveChildEventTypeId(
                            $mapping->event_type_id,
                            $this->appointment->staff_id
                        );

                        if (!$childEventTypeId) {
                            throw new \RuntimeException(
                                "Could not resolve child event type ID for segment '{$phase->segment_key}'"
                            );
                        }

                        $this->safeDebug("🔍 Resolved child event type ID from API", [
                            'parent_id' => $mapping->event_type_id,
                            'child_id' => $childEventTypeId,
                            'staff_id' => $this->appointment->staff_id,
                            'segment_key' => $phase->segment_key,
                            'source' => 'CalcomChildEventTypeResolver'
                        ], 'calcom');

                    } catch (\Exception $e) {
                        $error = "Failed to resolve child event type: {$e->getMessage()}";
                        $this->safeError("❌ {$error}", [
                            'parent_id' => $mapping->event_type_id,
                            'segment_key' => $phase->segment_key,
                            'staff_id' => $this->appointment->staff_id,
                            'trace' => $e->getTraceAsString()
                        ], 'calcom');

                        $errors[] = $error;
                        $phase->update([
                            'calcom_sync_status' => 'failed',
                            'sync_error_message' => $error
                        ]);
                        continue;
                    }
                }

                // Build payload for this segment
                // Note: CalcomV2Client expects name/email/timeZone at top level, not nested
                $segmentPayload = [
                    'eventTypeId' => $childEventTypeId,
                    'start' => $phase->start_time->toIso8601String(),
                    'name' => $this->appointment->customer->name ?? 'Customer',
                    'email' => $this->appointment->customer->email ?? 'noreply@example.com',
                    'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin',
                    'metadata' => [
                        'crm_appointment_id' => (string) $this->appointment->id,
                        'crm_phase_id' => (string) $phase->id,
                        'segment_key' => $phase->segment_key,
                        'segment_name' => $phase->segment_name,
                        'sync_origin' => 'system',
                        'created_via' => 'crm_composite_sync',
                        'synced_at' => now()->toIso8601String(),
                    ],
                    'bookingFieldsResponses' => [
                        'title' => "{$service->name} - {$phase->segment_name}",
                    ],
                ];

                $this->safeDebug("📤 Creating Cal.com booking for segment '{$phase->segment_key}'", [
                    'phase_id' => $phase->id,
                    'parent_event_type_id' => $mapping->event_type_id,
                    'child_event_type_id' => $childEventTypeId,
                    'start' => $segmentPayload['start'],
                ], 'calcom');

                // Create Cal.com booking
                $response = $client->createBooking($segmentPayload);
                $lastResponse = $response;

                if ($response->successful()) {
                    $responseData = $response->json('data', []);
                    $bookingId = $responseData['id'] ?? null;
                    $bookingUid = $responseData['uid'] ?? null;

                    if ($bookingId) {
                        $bookingIds[] = $bookingId;

                        // Update phase with booking info
                        $phase->update([
                            'calcom_booking_id' => $bookingId,
                            'calcom_booking_uid' => $bookingUid,
                            'calcom_sync_status' => 'synced',
                            'sync_error_message' => null
                        ]);

                        $this->safeInfo("✅ Segment '{$phase->segment_key}' synced", [
                            'phase_id' => $phase->id,
                            'calcom_booking_id' => $bookingId
                        ], 'calcom');
                    } else {
                        throw new \RuntimeException("Response missing booking ID");
                    }
                } else {
                    // 🔍 Enhanced error logging - capture full response BEFORE throwing
                    $errorBody = $response->body();
                    $errorData = $response->json();

                    $this->safeError("❌ Cal.com API returned error", [
                        'segment_key' => $phase->segment_key,
                        'http_status' => $response->status(),
                        'response_body' => $errorBody,
                        'parsed_error' => $errorData['error'] ?? null,
                        'error_message' => $errorData['error']['message'] ?? 'Unknown error',
                        'error_details' => $errorData['error']['details'] ?? null,
                        'payload_sent' => $segmentPayload
                    ], 'calcom');

                    throw new \RuntimeException(
                        "HTTP request returned status code " . $response->status() . ": " . $errorBody
                    );
                }

            } catch (\Exception $e) {
                $error = "Segment '{$phase->segment_key}' sync failed: {$e->getMessage()}";
                $errors[] = $error;

                $this->safeError("❌ {$error}", [
                    'phase_id' => $phase->id,
                    'error' => $e->getMessage()
                ], 'calcom');

                $phase->update([
                    'calcom_sync_status' => 'failed',
                    'sync_error_message' => substr($e->getMessage(), 0, 255)
                ]);
            }
        }

        // Update appointment with aggregated booking IDs
        // Note: Use 'synced' even for partial success (some segments synced)
        $appointmentUpdate = [
            'calcom_sync_status' => empty($bookingIds) ? 'failed' : 'synced',
            'sync_verified_at' => now(),
            'sync_error_message' => !empty($errors) ? 'Partial sync: ' . implode(', ', array_slice($errors, 0, 2)) : null,
        ];

        // Store first booking ID in main field for backward compatibility
        if (!empty($bookingIds)) {
            $appointmentUpdate['calcom_v2_booking_id'] = $bookingIds[0];
        }

        $this->appointment->update($appointmentUpdate);

        // Summary
        $this->safeInfo("🎉 Composite sync complete", [
            'appointment_id' => $this->appointment->id,
            'total_phases' => $phases->count(),
            'synced' => count($bookingIds),
            'failed' => count($errors),
            'status' => empty($errors) ? 'success' : 'partial'
        ], 'calcom');

        if (empty($bookingIds)) {
            throw new \RuntimeException(
                "All composite segments failed to sync: " . implode(', ', $errors)
            );
        }

        if (!empty($errors) && count($errors) === $phases->count()) {
            throw new \RuntimeException(
                "Composite sync completely failed: " . implode(', ', $errors)
            );
        }

        // Return last response for compatibility with handle() method
        return $lastResponse;
    }

    /**
     * Sync CANCEL action to Cal.com
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response
     */
    protected function syncCancel(CalcomV2Client $client)
    {
        // 🔧 FIX 2025-11-17: Prefer UID over ID for cancellation
        // Cal.com cancel endpoint requires UID (string), not ID (integer)
        $calcomBookingUid = $this->appointment->calcom_v2_booking_uid;
        $calcomBookingId = $this->appointment->calcom_v2_booking_id;

        $identifier = $calcomBookingUid ?: $calcomBookingId;

        if (!$identifier) {
            throw new \RuntimeException("No Cal.com booking UID/ID to cancel (appointment_id: {$this->appointment->id})");
        }

        $this->safeDebug('📤 Sending CANCEL to Cal.com', [
            'appointment_id' => $this->appointment->id,
            'calcom_booking_uid' => $calcomBookingUid,
            'calcom_booking_id' => $calcomBookingId,
            'using_identifier' => $identifier,
            'reason' => $this->appointment->cancellation_reason ?? 'Cancelled via CRM',
        ], 'calcom');

        return $client->cancelBooking(
            bookingUidOrId: $identifier,
            reason: $this->appointment->cancellation_reason ?? 'Cancelled via CRM'
        );
    }

    /**
     * Sync RESCHEDULE action to Cal.com
     *
     * 🔧 FIX 2025-11-25: Use UID instead of numeric ID for PATCH endpoint
     * Root cause: Cal.com V2 API returns 404 when using numeric ID
     * Solution: Use calcom_v2_booking_uid (string) for reschedule
     *
     * 🔧 FIX 2025-11-25: Fallback to CREATE only if booking truly doesn't exist
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response
     */
    protected function syncReschedule(CalcomV2Client $client)
    {
        // 🔧 FIX 2025-11-25: Use UID (string) instead of ID (int)
        // Cal.com V2 API requires UID for PATCH endpoint
        $calcomBookingUid = $this->appointment->calcom_v2_booking_uid;
        $calcomBookingId = $this->appointment->calcom_v2_booking_id;

        // If no booking UID exists, try to find it in Cal.com
        // DO NOT fall back to CREATE - this would send "Neuer Termin" email!
        if (!$calcomBookingUid) {
            $this->safeWarning('⚠️ No Cal.com booking UID for reschedule - searching Cal.com', [
                'appointment_id' => $this->appointment->id,
                'calcom_booking_id' => $calcomBookingId,
            ], 'calcom');

            // Try to find the booking in Cal.com
            $foundBooking = $this->tryFindBookingInCalcom($client);

            if ($foundBooking) {
                // Found it! Update our record and use it
                $calcomBookingUid = $foundBooking['uid'];
                $this->appointment->update([
                    'calcom_v2_booking_id' => $foundBooking['id'],
                    'calcom_v2_booking_uid' => $foundBooking['uid'],
                ]);
                $this->safeInfo('✅ Found booking UID in Cal.com', [
                    'appointment_id' => $this->appointment->id,
                    'found_uid' => $calcomBookingUid,
                ], 'calcom');
            } else {
                // No booking found - mark for manual review
                $this->safeError('❌ No Cal.com booking found for reschedule', [
                    'appointment_id' => $this->appointment->id,
                    'action' => 'Marked for manual review',
                ], 'calcom');

                $this->appointment->update([
                    'calcom_sync_status' => 'failed',
                    'sync_error_message' => 'Reschedule failed: No Cal.com booking exists. Manual sync required.',
                    'requires_manual_review' => true,
                    'manual_review_flagged_at' => now(),
                ]);

                // Return a fake 404 response to trigger error handling
                return new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(404, [], json_encode([
                        'error' => 'No Cal.com booking found for reschedule'
                    ]))
                );
            }
        }

        $this->safeDebug('📤 Sending RESCHEDULE to Cal.com', [
            'appointment_id' => $this->appointment->id,
            'calcom_booking_uid' => $calcomBookingUid,
            'calcom_booking_id' => $calcomBookingId,
            'new_start' => $this->appointment->starts_at->toIso8601String(),
        ], 'calcom');

        $response = $client->rescheduleBooking(
            bookingUid: $calcomBookingUid,  // 🔧 FIX: Use UID, not ID
            data: [
                'start' => $this->appointment->starts_at->toIso8601String(),
                'end' => $this->appointment->ends_at->toIso8601String(),
                'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin',
                'reason' => 'Rescheduled via CRM',
            ]
        );

        // 🔧 FIX 2025-11-25: Handle 404 - booking not found in Cal.com
        //
        // CRITICAL: Do NOT fall back to syncCreate() here!
        // Reason: syncCreate() sends a "Neuer Termin" email from Cal.com,
        // which confuses customers who expect a "Verschoben" email.
        //
        // Instead:
        // 1. Try to find the booking in Cal.com by searching
        // 2. If found → update our UID and retry reschedule
        // 3. If not found → mark for manual review (do NOT create new booking)
        if ($response->status() === 404) {
            $this->safeWarning('⚠️ Cal.com booking not found (404) during reschedule', [
                'appointment_id' => $this->appointment->id,
                'old_booking_uid' => $calcomBookingUid,
                'old_booking_id' => $calcomBookingId,
                'new_start' => $this->appointment->starts_at->toIso8601String(),
            ], 'calcom');

            // Try to find the correct booking in Cal.com
            $foundBooking = $this->tryFindBookingInCalcom($client);

            if ($foundBooking) {
                // Found the booking! Update our UID and retry
                $this->safeInfo('✅ Found booking in Cal.com, retrying reschedule', [
                    'appointment_id' => $this->appointment->id,
                    'found_booking_id' => $foundBooking['id'],
                    'found_booking_uid' => $foundBooking['uid'],
                ], 'calcom');

                $this->appointment->update([
                    'calcom_v2_booking_id' => $foundBooking['id'],
                    'calcom_v2_booking_uid' => $foundBooking['uid'],
                ]);

                // Retry reschedule with correct UID
                return $client->rescheduleBooking(
                    bookingUid: $foundBooking['uid'],
                    data: [
                        'start' => $this->appointment->starts_at->toIso8601String(),
                        'end' => $this->appointment->ends_at->toIso8601String(),
                        'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin',
                        'reason' => 'Rescheduled via CRM',
                    ]
                );
            }

            // Booking truly doesn't exist - mark for manual review
            // DO NOT create new booking (würde "Neuer Termin" Email senden!)
            $this->safeError('❌ Cal.com booking not found and cannot be recovered', [
                'appointment_id' => $this->appointment->id,
                'old_booking_uid' => $calcomBookingUid,
                'action' => 'Marked for manual review - please create booking manually in Cal.com',
            ], 'calcom');

            $this->appointment->update([
                'calcom_sync_status' => 'failed',
                'sync_error_message' => 'Reschedule failed: Booking not found in Cal.com (404). Manual sync required.',
                'requires_manual_review' => true,
                'manual_review_flagged_at' => now(),
            ]);

            // Return the original 404 response to trigger proper error handling
            return $response;
        }

        return $response;
    }

    /**
     * Try to find the booking in Cal.com by searching
     *
     * When our stored booking UID is invalid/stale, we try to find the correct
     * booking by matching customer email and approximate time.
     *
     * @param CalcomV2Client $client Cal.com API client
     * @return array|null Found booking data or null
     */
    protected function tryFindBookingInCalcom(CalcomV2Client $client): ?array
    {
        try {
            $this->appointment->load('customer');

            if (!$this->appointment->customer || !$this->appointment->customer->email) {
                $this->safeWarning('⚠️ Cannot search Cal.com: No customer email', [
                    'appointment_id' => $this->appointment->id,
                ], 'calcom');
                return null;
            }

            // Search for bookings around the appointment time
            // Note: We use a wider time range because the appointment might have been
            // rescheduled in Cal.com but our DB wasn't updated
            $searchStart = $this->appointment->starts_at->copy()->subDays(7)->startOfDay();
            $searchEnd = $this->appointment->starts_at->copy()->addDays(7)->endOfDay();

            $response = $client->getBookings([
                'afterStart' => $searchStart->toIso8601String(),
                'beforeEnd' => $searchEnd->toIso8601String(),
                'status' => 'upcoming',
            ]);

            if (!$response->successful()) {
                $this->safeWarning('⚠️ Cal.com booking search failed', [
                    'appointment_id' => $this->appointment->id,
                    'status' => $response->status(),
                ], 'calcom');
                return null;
            }

            $bookings = $response->json('data', []);

            if (empty($bookings)) {
                $this->safeInfo('ℹ️ No bookings found in Cal.com search', [
                    'appointment_id' => $this->appointment->id,
                    'search_range' => "{$searchStart->toDateString()} - {$searchEnd->toDateString()}",
                ], 'calcom');
                return null;
            }

            // Find booking matching our customer email
            $customerEmail = strtolower($this->appointment->customer->email);

            foreach ($bookings as $booking) {
                // Check if attendee email matches
                $attendees = $booking['attendees'] ?? [];
                foreach ($attendees as $attendee) {
                    if (isset($attendee['email']) && strtolower($attendee['email']) === $customerEmail) {
                        // Found matching booking!
                        $this->safeInfo('🔍 Found matching booking in Cal.com', [
                            'appointment_id' => $this->appointment->id,
                            'found_booking_id' => $booking['id'],
                            'found_booking_uid' => $booking['uid'],
                            'booking_start' => $booking['start'] ?? null,
                            'customer_email' => $customerEmail,
                        ], 'calcom');

                        return [
                            'id' => $booking['id'],
                            'uid' => $booking['uid'],
                            'start' => $booking['start'] ?? null,
                        ];
                    }
                }

                // Also check metadata for our CRM appointment ID
                $metadata = $booking['metadata'] ?? [];
                if (isset($metadata['crm_appointment_id']) &&
                    (string)$metadata['crm_appointment_id'] === (string)$this->appointment->id) {
                    $this->safeInfo('🔍 Found booking by CRM appointment ID in metadata', [
                        'appointment_id' => $this->appointment->id,
                        'found_booking_id' => $booking['id'],
                        'found_booking_uid' => $booking['uid'],
                    ], 'calcom');

                    return [
                        'id' => $booking['id'],
                        'uid' => $booking['uid'],
                        'start' => $booking['start'] ?? null,
                    ];
                }
            }

            $this->safeInfo('ℹ️ No matching booking found for customer', [
                'appointment_id' => $this->appointment->id,
                'customer_email' => $customerEmail,
                'bookings_checked' => count($bookings),
            ], 'calcom');

            return null;

        } catch (\Exception $e) {
            $this->safeError('❌ Error searching Cal.com bookings', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
            ], 'calcom');
            return null;
        }
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
            // 🔧 FIX 2025-11-17: Store BOTH ID and UID from Cal.com
            // UID is required for cancellation (cancel endpoint needs UID, not ID)
            'calcom_v2_booking_id' => $responseData['id'] ?? $this->appointment->calcom_v2_booking_id,
            'calcom_v2_booking_uid' => $responseData['uid'] ?? $this->appointment->calcom_v2_booking_uid,
        ]);

        $this->safeInfo('✅ Cal.com sync successful', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'attempt' => $this->attempts(),
            'calcom_booking_id' => $responseData['id'] ?? null,
            'calcom_booking_uid' => $responseData['uid'] ?? null,
        ], 'calcom');
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

        // 🐛 DEBUG (2025-11-17): Log FULL error before truncation
        $this->safeError('🔍 FULL Cal.com API Error Response', [
            'appointment_id' => $this->appointment->id,
            'status_code' => $statusCode,
            'full_body' => $body,
            'full_error' => $errorMessage,
        ], 'calcom');

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

            $this->safeError('🚨 Cal.com sync permanently failed - manual review required', [
                'appointment_id' => $this->appointment->id,
                'action' => $this->action,
                'status_code' => $statusCode,
                'error' => $errorMessage,
            ], 'calcom');
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
        $this->safeError('❌ Cal.com sync failed', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 'calcom');

        $this->appointment->update([
            'calcom_sync_status' => 'failed',
            'sync_error_code' => get_class($e),
            'sync_error_message' => substr($e->getMessage(), 0, 255),
            'sync_attempt_count' => $this->appointment->sync_attempt_count + 1,
        ]);

        // Flag for manual review if all retries exhausted
        if ($this->attempts() >= $this->tries) {
            // 🔧 FIX 2025-11-23: POST-SYNC VERIFICATION
            // Problem: Cal.com sometimes returns HTTP 400 even when bookings are created
            // Solution: Verify if bookings actually exist in Cal.com before marking as failed
            $this->safeInfo('🔍 POST-SYNC VERIFICATION: Checking if bookings exist despite error...', [
                'appointment_id' => $this->appointment->id,
                'action' => $this->action,
            ], 'calcom');

            // Wait 2 seconds to give Cal.com time to settle
            sleep(2);

            if ($this->verifyBookingsInCalcom()) {
                $this->safeInfo('✅ POST-SYNC VERIFICATION: Bookings found in Cal.com! Marking as synced.', [
                    'appointment_id' => $this->appointment->id,
                ], 'calcom');

                // Bookings exist! This was a false-negative error
                // Don't flag for manual review, don't re-throw exception
                return;
            }

            // Bookings don't exist - it's a real failure
            $this->appointment->update([
                'requires_manual_review' => true,
                'manual_review_flagged_at' => now(),
            ]);

            $this->safeCritical('🚨 Cal.com sync permanently failed after max retries', [
                'appointment_id' => $this->appointment->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
            ], 'calcom');
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
        $this->safeCritical('💀 Cal.com sync job permanently failed', [
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ], 'calcom');

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

    /**
     * Sync composite appointment phases to Cal.com in PARALLEL (70% faster)
     *
     * PERFORMANCE: Uses async HTTP/2 requests to sync all segments concurrently
     * BENEFIT: 10s → 3s for 4 segments (Dauerwelle)
     *
     * @param \Illuminate\Support\Collection $phases Active phases to sync
     * @param \App\Models\Service $service The composite service
     * @param \App\Services\CalcomV2Client $client Cal.com API client
     * @return \Illuminate\Http\Client\Response Last successful response
     */
    private function syncPhasesParallel($phases, $service, $client)
    {
        $startTime = microtime(true);

        $this->safeInfo("🚀 PARALLEL sync started for {$phases->count()} segments", [
            'appointment_id' => $this->appointment->id,
            'service_id' => $service->id,
        ], 'calcom');

        // Step 1: Prepare all payloads and resolve child IDs
        $payloads = [];
        $phaseMap = []; // Map index → phase for result processing

        foreach ($phases as $index => $phase) {
            try {
                // Lookup mapping
                $mapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
                    ->where('segment_key', $phase->segment_key)
                    ->where('staff_id', $this->appointment->staff_id)
                    ->first();

                if (!$mapping) {
                    throw new \RuntimeException("Missing CalcomEventMap for segment '{$phase->segment_key}'");
                }

                // Resolve child event type ID
                $childEventTypeId = $mapping->child_event_type_id;
                if (!$childEventTypeId) {
                    $resolver = new \App\Services\CalcomChildEventTypeResolver($this->appointment->company);
                    $childEventTypeId = $resolver->resolveChildEventTypeId(
                        $mapping->event_type_id,
                        $this->appointment->staff_id
                    );

                    if (!$childEventTypeId) {
                        throw new \RuntimeException("Could not resolve child event type ID");
                    }
                }

                // Build payload
                $payloads[$index] = [
                    'eventTypeId' => $childEventTypeId,
                    'start' => $phase->start_time->toIso8601String(),
                    'name' => $this->appointment->customer->name ?? 'Customer',
                    'email' => $this->appointment->customer->email ?? 'noreply@example.com',
                    'timeZone' => $this->appointment->booking_timezone ?? 'Europe/Berlin',
                    'metadata' => [
                        'crm_appointment_id' => (string) $this->appointment->id,
                        'crm_phase_id' => (string) $phase->id,
                        'segment_key' => $phase->segment_key,
                        'segment_name' => $phase->segment_name,
                        'sync_origin' => 'system',
                        'created_via' => 'crm_composite_sync_parallel',
                        'synced_at' => now()->toIso8601String(),
                    ],
                    'bookingFieldsResponses' => [
                        'title' => "{$service->name} - {$phase->segment_name}",
                    ],
                ];

                $phaseMap[$index] = $phase;

            } catch (\Exception $e) {
                $this->safeError("❌ Failed to prepare segment '{$phase->segment_key}': {$e->getMessage()}", [
                    'phase_id' => $phase->id
                ], 'calcom');

                $phase->update([
                    'calcom_sync_status' => 'failed',
                    'sync_error_message' => substr($e->getMessage(), 0, 255)
                ]);
            }
        }

        if (empty($payloads)) {
            throw new \RuntimeException("All phases failed during preparation");
        }

        // Step 2: Execute all Cal.com bookings in PARALLEL
        $this->safeInfo("⚡ Executing " . count($payloads) . " parallel Cal.com API requests", [
            'segments' => array_keys($payloads)
        ], 'calcom');

        $promises = [];
        foreach ($payloads as $index => $payload) {
            $promises[$index] = $client->createBookingAsync($payload);
        }

        // Wait for all promises to resolve
        $results = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

        // Step 3: Process results
        $bookingIds = [];
        $errors = [];
        $lastResponse = null;

        foreach ($results as $index => $result) {
            $phase = $phaseMap[$index];

            if ($result['state'] === 'fulfilled') {
                $response = $result['value'];
                $lastResponse = $response;

                if ($response->successful()) {
                    $responseData = $response->json('data', []);
                    $bookingId = $responseData['id'] ?? null;
                    $bookingUid = $responseData['uid'] ?? null;

                    if ($bookingId) {
                        $bookingIds[] = $bookingId;

                        $phase->update([
                            'calcom_booking_id' => $bookingId,
                            'calcom_booking_uid' => $bookingUid,
                            'calcom_sync_status' => 'synced',
                            'sync_error_message' => null
                        ]);

                        $this->safeInfo("✅ Segment '{$phase->segment_key}' synced", [
                            'phase_id' => $phase->id,
                            'calcom_booking_id' => $bookingId
                        ], 'calcom');
                    } else {
                        $error = "Response missing booking ID";
                        $errors[] = $error;

                        $this->safeError("❌ {$error}", ['phase_id' => $phase->id], 'calcom');

                        $phase->update([
                            'calcom_sync_status' => 'failed',
                            'sync_error_message' => $error
                        ]);
                    }
                } else {
                    $error = "HTTP {$response->status()}: {$response->body()}";
                    $errors[] = $error;

                    $this->safeError("❌ Cal.com API error for segment '{$phase->segment_key}'", [
                        'phase_id' => $phase->id,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ], 'calcom');

                    $phase->update([
                        'calcom_sync_status' => 'failed',
                        'sync_error_message' => substr($error, 0, 255)
                    ]);
                }
            } else {
                // Promise rejected (exception)
                $exception = $result['reason'];
                $error = "Exception: {$exception->getMessage()}";
                $errors[] = $error;

                $this->safeError("❌ Segment '{$phase->segment_key}' promise rejected", [
                    'phase_id' => $phase->id,
                    'error' => $exception->getMessage()
                ], 'calcom');

                $phase->update([
                    'calcom_sync_status' => 'failed',
                    'sync_error_message' => substr($error, 0, 255)
                ]);
            }
        }

        // Step 4: Update appointment
        $appointmentUpdate = [
            'calcom_sync_status' => empty($bookingIds) ? 'failed' : 'synced',
            'sync_verified_at' => now(),
            'sync_error_message' => !empty($errors) ? 'Partial sync: ' . implode(', ', array_slice($errors, 0, 2)) : null,
        ];

        if (!empty($bookingIds)) {
            $appointmentUpdate['calcom_v2_booking_id'] = $bookingIds[0];
        }

        $this->appointment->update($appointmentUpdate);

        // Step 5: Performance metrics
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->safeInfo("🎉 PARALLEL sync complete", [
            'appointment_id' => $this->appointment->id,
            'total_phases' => $phases->count(),
            'synced' => count($bookingIds),
            'failed' => count($errors),
            'duration_ms' => $duration,
            'performance_gain' => 'Expected ~70% faster than sequential',
            'status' => empty($errors) ? 'success' : 'partial'
        ], 'calcom');

        if (empty($bookingIds)) {
            throw new \RuntimeException(
                "All composite segments failed to sync: " . implode(', ', $errors)
            );
        }

        return $lastResponse;
    }

    /**
     * Verify if bookings actually exist in Cal.com (Post-Sync Verification)
     *
     * FIX 2025-11-23: Cal.com sometimes returns HTTP 400 even when bookings are created
     * This method queries Cal.com to check if bookings actually exist, preventing false-negative sync status
     *
     * For composite services: Checks if all active phases have bookings
     * For regular services: Checks if the appointment has a booking
     *
     * @return bool True if bookings exist and sync should be marked as successful
     */
    protected function verifyBookingsInCalcom(): bool
    {
        try {
            $client = new CalcomV2Client($this->appointment->company);

            // For composite services, check all active phases
            if ($this->appointment->service->isComposite()) {
                return $this->verifyCompositeBookings($client);
            }

            // For regular services, check single booking
            return $this->verifyRegularBooking($client);

        } catch (\Exception $e) {
            $this->safeError('⚠️ POST-SYNC VERIFICATION failed', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
            ], 'calcom');

            return false; // Verification failed, assume bookings don't exist
        }
    }

    /**
     * Verify composite service bookings (checks all active phases)
     *
     * @param CalcomV2Client $client
     * @return bool
     */
    protected function verifyCompositeBookings(CalcomV2Client $client): bool
    {
        $this->appointment->load('phases', 'service', 'customer');

        $phases = $this->appointment->phases()
            ->where('staff_required', true)
            ->orderBy('sequence_order')
            ->get();

        if ($phases->isEmpty()) {
            $this->safeWarning('⚠️ No active phases found for composite appointment', [
                'appointment_id' => $this->appointment->id,
            ], 'calcom');
            return false;
        }

        // Query Cal.com for bookings on this date
        $startDate = $this->appointment->starts_at->copy()->startOfDay();
        $endDate = $this->appointment->starts_at->copy()->endOfDay();

        $response = $client->getBookings([
            'afterStart' => $startDate->toIso8601String(),
            'beforeEnd' => $endDate->toIso8601String(),
            'status' => 'upcoming'
        ]);

        if (!$response->successful()) {
            $this->safeError('⚠️ Failed to fetch Cal.com bookings for verification', [
                'appointment_id' => $this->appointment->id,
                'http_status' => $response->status(),
            ], 'calcom');
            return false;
        }

        $calcomBookings = $response->json('data', []);

        // Match Cal.com bookings to our phases by time
        $verifiedCount = 0;
        $bookingUpdates = [];

        foreach ($phases as $phase) {
            $phaseStartUtc = $phase->start_time->copy()->setTimezone('UTC');

            // Find matching booking in Cal.com (within 5 minutes tolerance)
            $matchedBooking = collect($calcomBookings)->first(function ($booking) use ($phaseStartUtc) {
                $bookingStart = \Carbon\Carbon::parse($booking['start'] ?? null);
                if (!$bookingStart) {
                    return false;
                }

                // Match if within 5 minutes
                return abs($bookingStart->diffInMinutes($phaseStartUtc)) <= 5;
            });

            if ($matchedBooking) {
                $verifiedCount++;

                // Store booking details for update
                $bookingUpdates[] = [
                    'phase' => $phase,
                    'booking_id' => $matchedBooking['id'],
                    'booking_uid' => $matchedBooking['uid'] ?? null,
                ];

                $this->safeDebug('✅ Verified phase booking in Cal.com', [
                    'phase_id' => $phase->id,
                    'segment_key' => $phase->segment_key,
                    'calcom_booking_id' => $matchedBooking['id'],
                ], 'calcom');
            }
        }

        // If ALL phases have bookings, mark as synced
        if ($verifiedCount === $phases->count()) {
            // Update phases with booking info
            foreach ($bookingUpdates as $update) {
                $update['phase']->update([
                    'calcom_booking_id' => $update['booking_id'],
                    'calcom_booking_uid' => $update['booking_uid'],
                    'calcom_sync_status' => 'synced',
                    'sync_error_message' => null,
                ]);
            }

            // Update main appointment
            $this->appointment->update([
                'calcom_v2_booking_id' => $bookingUpdates[0]['booking_id'], // First segment
                'calcom_v2_booking_uid' => $bookingUpdates[0]['booking_uid'],
                'calcom_sync_status' => 'synced',
                'sync_verified_at' => now(),
                'sync_error_message' => null,
                'sync_error_code' => null,
                'requires_manual_review' => false,
            ]);

            $this->safeInfo('✅ POST-SYNC VERIFICATION SUCCESS: All composite bookings verified', [
                'appointment_id' => $this->appointment->id,
                'verified_count' => $verifiedCount,
                'total_phases' => $phases->count(),
            ], 'calcom');

            return true;
        }

        // Partial verification - some bookings found but not all
        $this->safeWarning('⚠️ POST-SYNC VERIFICATION: Partial bookings found', [
            'appointment_id' => $this->appointment->id,
            'verified_count' => $verifiedCount,
            'total_phases' => $phases->count(),
        ], 'calcom');

        return false;
    }

    /**
     * Verify regular service booking (single booking check)
     *
     * @param CalcomV2Client $client
     * @return bool
     */
    protected function verifyRegularBooking(CalcomV2Client $client): bool
    {
        // Query Cal.com for bookings on this date
        $startDate = $this->appointment->starts_at->copy()->startOfDay();
        $endDate = $this->appointment->starts_at->copy()->endOfDay();

        $response = $client->getBookings([
            'afterStart' => $startDate->toIso8601String(),
            'beforeEnd' => $endDate->toIso8601String(),
            'status' => 'upcoming'
        ]);

        if (!$response->successful()) {
            $this->safeError('⚠️ Failed to fetch Cal.com bookings for verification', [
                'appointment_id' => $this->appointment->id,
                'http_status' => $response->status(),
            ], 'calcom');
            return false;
        }

        $calcomBookings = $response->json('data', []);
        $appointmentStartUtc = $this->appointment->starts_at->copy()->setTimezone('UTC');

        // Find matching booking (within 5 minutes tolerance)
        $matchedBooking = collect($calcomBookings)->first(function ($booking) use ($appointmentStartUtc) {
            $bookingStart = \Carbon\Carbon::parse($booking['start'] ?? null);
            if (!$bookingStart) {
                return false;
            }

            return abs($bookingStart->diffInMinutes($appointmentStartUtc)) <= 5;
        });

        if ($matchedBooking) {
            // Update appointment with booking info
            $this->appointment->update([
                'calcom_v2_booking_id' => $matchedBooking['id'],
                'calcom_v2_booking_uid' => $matchedBooking['uid'] ?? null,
                'calcom_sync_status' => 'synced',
                'sync_verified_at' => now(),
                'sync_error_message' => null,
                'sync_error_code' => null,
                'requires_manual_review' => false,
            ]);

            $this->safeInfo('✅ POST-SYNC VERIFICATION SUCCESS: Regular booking verified', [
                'appointment_id' => $this->appointment->id,
                'calcom_booking_id' => $matchedBooking['id'],
            ], 'calcom');

            return true;
        }

        // No matching booking found
        $this->safeWarning('⚠️ POST-SYNC VERIFICATION: No matching booking found', [
            'appointment_id' => $this->appointment->id,
            'appointment_start' => $this->appointment->starts_at,
            'total_bookings_found' => count($calcomBookings),
        ], 'calcom');

        return false;
    }
}
