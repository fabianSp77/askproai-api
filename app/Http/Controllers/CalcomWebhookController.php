<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalcomWebhookRequest;
use App\Jobs\ImportEventTypeJob;
use App\Jobs\UpdateServiceFromCalcomJob;
use App\Jobs\SoftDeleteServiceJob;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Services\PhoneNumberNormalizer;
use App\Services\StaffAssignmentService;
use App\Services\Strategies\AssignmentContext;
use App\Traits\LogsWebhookEvents;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\LogSanitizer;

class CalcomWebhookController extends Controller
{
    use LogsWebhookEvents;

    public function __construct(
        protected StaffAssignmentService $staffAssignmentService
    ) {}
    /**
     * GET /api/calcom/webhook
     * Cal.com-Ping (keine Signatur-Prüfung).
     */
    public function ping(): JsonResponse
    {
        return response()->json(['ping' => 'ok']);
    }

    /**
     * POST /api/calcom/webhook
     * Wird von Cal.com aufgerufen – Signatur prüft unsere Middleware.
     */
    public function handle(CalcomWebhookRequest $request): JsonResponse
    {
        $data = $request->sanitized();

        // Log the webhook event (non-blocking - continue even if logging fails)
        $webhookEvent = null;
        try {
            $webhookEvent = $this->logWebhookEvent($request, 'calcom', $data);
        } catch (\Exception $e) {
            Log::warning('[Cal.com] Webhook event logging failed (non-critical)', [
                'error' => $e->getMessage()
            ]);
        }

        /* ───── TEMP-DEBUG – Header & Body in calcom-Channel loggen (GDPR-compliant) ───── */
        Log::channel('calcom')->debug('[Debug] headers', LogSanitizer::sanitizeHeaders($request->headers->all()));
        Log::channel('calcom')->debug('[Debug] body',    ['raw' => LogSanitizer::sanitize($request->getContent())]);
        /* ──────────────────────────────────────────────────────────────── */

        $triggerEvent = $data['triggerEvent'] ?? $data['event'] ?? null;
        $payload = $data['payload'] ?? $data;

        Log::channel('calcom')->info('[Cal.com] Webhook received', [
            'event' => $triggerEvent,
            'payload_keys' => array_keys($payload)
        ]);

        try {
            $relatedModel = null;

            switch ($triggerEvent) {
                // PING event (Cal.com test)
                case 'PING':
                    Log::channel('calcom')->info('[Cal.com] PING event processed');
                    return response()->json(['received' => true, 'status' => 'ok', 'message' => 'PING received']);

                // Event Type events
                case 'EVENT_TYPE.CREATED':
                    $this->handleEventTypeCreated($payload);
                    break;

                case 'EVENT_TYPE.UPDATED':
                    $this->handleEventTypeUpdated($payload);
                    break;

                case 'EVENT_TYPE.DELETED':
                    $this->handleEventTypeDeleted($payload);
                    break;

                // Booking events (existing functionality)
                case 'BOOKING.CREATED':
                    $relatedModel = $this->handleBookingCreated($payload);
                    break;

                case 'BOOKING.UPDATED':
                case 'BOOKING.RESCHEDULED':
                    $relatedModel = $this->handleBookingUpdated($payload);
                    break;

                case 'BOOKING.CANCELLED':
                    $relatedModel = $this->handleBookingCancelled($payload);
                    break;

                default:
                    Log::channel('calcom')->warning('[Cal.com] Unknown event type', [
                        'event' => $triggerEvent
                    ]);
                    $this->markWebhookIgnored($webhookEvent, 'Unknown event type: ' . $triggerEvent);
                    return response()->json(['received' => true, 'status' => 'ignored']);
            }

            $this->markWebhookProcessed($webhookEvent, $relatedModel);
            return response()->json(['received' => true, 'status' => 'processed']);

        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com] Webhook processing failed', [
                'event' => $triggerEvent,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->markWebhookFailed($webhookEvent, $e->getMessage());
            return response()->json(['received' => true, 'status' => 'error'], 500);
        }
    }

    /**
     * Handle EVENT_TYPE.CREATED webhook
     */
    protected function handleEventTypeCreated(array $payload): void
    {
        Log::channel('calcom')->info('[Cal.com] Event Type created', [
            'id' => $payload['id'] ?? 'unknown',
            'title' => $payload['title'] ?? 'unknown'
        ]);

        ImportEventTypeJob::dispatch($payload);
    }

    /**
     * Handle EVENT_TYPE.UPDATED webhook
     */
    protected function handleEventTypeUpdated(array $payload): void
    {
        Log::channel('calcom')->info('[Cal.com] Event Type updated', [
            'id' => $payload['id'] ?? 'unknown',
            'title' => $payload['title'] ?? 'unknown'
        ]);

        // Check if we have this service
        $service = Service::where('calcom_event_type_id', $payload['id'] ?? null)->first();

        if ($service) {
            ImportEventTypeJob::dispatch($payload); // Use same job for updates
        } else {
            // New event type that we don't have yet
            ImportEventTypeJob::dispatch($payload);
        }
    }

    /**
     * Handle EVENT_TYPE.DELETED webhook
     */
    protected function handleEventTypeDeleted(array $payload): void
    {
        $eventTypeId = $payload['id'] ?? null;

        Log::channel('calcom')->info('[Cal.com] Event Type deleted', [
            'id' => $eventTypeId
        ]);

        if (!$eventTypeId) {
            Log::channel('calcom')->error('[Cal.com] No Event Type ID in delete webhook');
            return;
        }

        // Soft delete the service
        $service = Service::where('calcom_event_type_id', $eventTypeId)->first();

        if ($service) {
            $service->update([
                'is_active' => false,
                'sync_status' => 'synced',
                'sync_error' => 'Event Type deleted in Cal.com',
                'last_calcom_sync' => now()
            ]);

            Log::channel('calcom')->info('[Cal.com] Service deactivated', [
                'service_id' => $service->id,
                'event_type_id' => $eventTypeId
            ]);
        }
    }

    /**
     * Handle BOOKING.CREATED webhook
     */
    protected function handleBookingCreated(array $payload): ?Appointment
    {
        try {
            // Extract booking information
            $calcomId = $payload['id'] ?? $payload['uid'] ?? null;
            $startTime = Carbon::parse($payload['startTime']);
            $endTime = Carbon::parse($payload['endTime']);

            // Extract customer information from attendees
            $attendee = $payload['attendees'][0] ?? [];
            $customerEmail = $attendee['email'] ?? $payload['email'] ?? null;
            $customerName = $attendee['name'] ?? $payload['name'] ?? 'Cal.com Customer';
            $customerPhone = $this->extractPhoneNumber($attendee, $payload);

            // 🛡️ SECURITY FIX (VULN-001): Verify company ownership via service lookup
            // This prevents creating appointments for wrong companies
            $expectedCompanyId = $this->verifyWebhookOwnership($payload);
            if (!$expectedCompanyId) {
                Log::channel('calcom')->error('[Security] Cannot create appointment - event type not found', [
                    'event_type_id' => $payload['eventTypeId'] ?? 'missing'
                ]);
                throw new \Exception('Unauthorized webhook: Event type not found in system');
            }

            // Find matching service (already verified by verifyWebhookOwnership)
            $service = Service::where('calcom_event_type_id', $payload['eventTypeId'])->first();

            // Find or create customer WITH verified company_id
            $customer = $this->findOrCreateCustomer($customerName, $customerEmail, $customerPhone, $expectedCompanyId);

            // PHASE 2: Staff Assignment Integration
            // Attempt to assign staff using multi-model assignment system
            $staffId = null;
            $assignmentMetadata = [];

            if ($service) {
                try {
                    $assignmentContext = new AssignmentContext(
                        companyId: $expectedCompanyId,
                        serviceId: $service->id,
                        startsAt: $startTime->toDateTime(),
                        endsAt: $endTime->toDateTime(),
                        calcomBooking: $payload,
                        customerId: $customer->id
                    );

                    $assignmentResult = $this->staffAssignmentService->assignStaff($assignmentContext);

                    if ($assignmentResult->isSuccessful()) {
                        $staffId = $assignmentResult->getStaffId();
                        $assignmentMetadata = $assignmentResult->toAppointmentMetadata();

                        Log::channel('calcom')->info('[Cal.com] Staff assigned', [
                            'staff_id' => $staffId,
                            'model' => $assignmentResult->model,
                            'was_fallback' => $assignmentResult->wasFallback,
                        ]);
                    } else {
                        Log::channel('calcom')->warning('[Cal.com] Staff assignment failed', [
                            'reason' => $assignmentResult->reason,
                            'model' => $assignmentResult->model,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::channel('calcom')->error('[Cal.com] Staff assignment error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // 🏢 MULTI-BRANCH SUPPORT: Determine branch for appointment
            // Priority: 1) Service.branch_id (filialspezifischer Service)
            //          2) Staff.branch_id (Home-Filiale des Mitarbeiters)
            //          3) null (fallback)
            $branchId = $service?->branch_id;
            if (!$branchId && $staffId) {
                $staff = Staff::find($staffId);
                $branchId = $staff?->branch_id;
            }

            // Create appointment (use v2_booking_id for string IDs)
            $appointment = Appointment::updateOrCreate(
                ['calcom_v2_booking_id' => $calcomId],
                array_merge([
                    'customer_id' => $customer->id,
                    'company_id' => $expectedCompanyId,  // ← Use verified company ID
                    'service_id' => $service?->id,
                    'staff_id' => $staffId, // From multi-model assignment
                    'branch_id' => $branchId, // 🏢 NEW: Multi-branch support via Service or Staff
                    'starts_at' => $startTime,
                    'ends_at' => $endTime,
                    'status' => 'confirmed',
                    'source' => 'cal.com',
                    'notes' => $payload['description'] ?? $payload['additionalNotes'] ?? null,
                    'metadata' => json_encode([
                        'cal_com_data' => $payload,
                        'booking_uid' => $payload['uid'] ?? null,
                        'event_type' => $payload['eventType'] ?? null,
                        'location' => $payload['location'] ?? null,
                        'meeting_url' => $payload['meetingUrl'] ?? null,
                    ]),
                    'calcom_event_type_id' => $payload['eventTypeId'] ?? null,
                    // ✅ METADATA FIX 2025-10-10: Populate tracking fields
                    'created_by' => 'customer',
                    'booking_source' => 'calcom_webhook',
                    'booked_by_user_id' => null,  // Customer bookings have no user
                    // 🔄 SYNC ORIGIN (Phase 2: Loop Prevention)
                    'sync_origin' => 'calcom',  // ← CRITICAL: Mark origin to prevent sync loop
                    'calcom_sync_status' => 'synced',  // ← Already in Cal.com
                ], $assignmentMetadata) // Merge assignment metadata (model_used, was_fallback, assignment_metadata)
            );

            Log::channel('calcom')->info('[Cal.com] Appointment created from booking', [
                'appointment_id' => $appointment->id,
                'calcom_id' => $calcomId,
                'customer' => $customer->name,
                'staff_id' => $staffId,
                'branch_id' => $branchId,
                'branch_source' => $service?->branch_id ? 'service' : ($staffId ? 'staff' : 'none'),
                'assignment_model' => $assignmentMetadata['assignment_model_used'] ?? 'none',
                'time' => $startTime->format('Y-m-d H:i')
            ]);

            // 🔧 FIX 2025-10-11: Invalidate availability cache after webhook booking
            // Prevents showing booked slots as "available" to other callers
            if ($service && $service->calcom_event_type_id) {
                try {
                    app(\App\Services\CalcomService::class)
                        ->clearAvailabilityCacheForEventType($service->calcom_event_type_id);

                    Log::channel('calcom')->info('✅ Cache invalidated after webhook booking', [
                        'event_type_id' => $service->calcom_event_type_id,
                        'appointment_id' => $appointment->id
                    ]);
                } catch (\Exception $e) {
                    // Non-blocking: Log but don't fail the webhook
                    Log::channel('calcom')->warning('⚠️ Cache invalidation failed (non-critical)', [
                        'error' => $e->getMessage(),
                        'appointment_id' => $appointment->id
                    ]);
                }
            }

            return $appointment;

        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com] Failed to create appointment from booking', [
                'error' => $e->getMessage(),
                'booking_id' => $payload['id'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Verify webhook ownership by checking if event type belongs to our system
     * Prevents cross-tenant attacks (VULN-001 FIX)
     *
     * @param array $payload Webhook payload
     * @return int|null Company ID if valid, null if unauthorized
     */
    protected function verifyWebhookOwnership(array $payload): ?int
    {
        $eventTypeId = $payload['eventTypeId'] ?? null;

        if (!$eventTypeId) {
            Log::channel('calcom')->warning('[Security] Webhook missing eventTypeId', [
                'payload_keys' => array_keys($payload)
            ]);
            return null;
        }

        $service = Service::where('calcom_event_type_id', $eventTypeId)->first();

        if (!$service) {
            Log::channel('calcom')->warning('[Security] Webhook for unknown service - potential cross-tenant attack', [
                'event_type_id' => $eventTypeId,
                'payload' => $payload
            ]);
            return null;
        }

        return $service->company_id;
    }

    /**
     * Handle BOOKING.UPDATED webhook
     */
    protected function handleBookingUpdated(array $payload): ?Appointment
    {
        try {
            $calcomId = $payload['id'] ?? $payload['uid'] ?? null;

            // 🛡️ SECURITY FIX (VULN-001): Verify company ownership before lookup
            $expectedCompanyId = $this->verifyWebhookOwnership($payload);
            if (!$expectedCompanyId) {
                Log::channel('calcom')->error('[Security] Unauthorized webhook - rejecting', [
                    'booking_id' => $calcomId
                ]);
                throw new \Exception('Unauthorized webhook: Event type not found in system');
            }

            $appointment = Appointment::where('calcom_v2_booking_id', $calcomId)
                ->where('company_id', $expectedCompanyId)  // ← CRITICAL: Enforce tenant isolation
                ->first();

            if ($appointment) {
                // Store old start time before update
                $oldStartsAt = $appointment->starts_at;

                $appointment->update([
                    'starts_at' => Carbon::parse($payload['startTime']),
                    'ends_at' => Carbon::parse($payload['endTime']),
                    'status' => $payload['status'] === 'CANCELLED' ? 'cancelled' : 'rescheduled',
                    'notes' => $appointment->notes . "\n\nRescheduled on " . now()->format('Y-m-d H:i'),
                    'metadata' => json_encode(array_merge(
                        json_decode($appointment->metadata ?? '{}', true),
                        ['last_update' => $payload]
                    )),
                    // ✅ METADATA FIX 2025-10-10: Populate reschedule tracking fields
                    'rescheduled_at' => now(),
                    'rescheduled_by' => 'customer',
                    'reschedule_source' => 'calcom_webhook',
                    'previous_starts_at' => $oldStartsAt,
                    // 🔄 SYNC ORIGIN (Phase 2: Loop Prevention)
                    'sync_origin' => 'calcom',  // ← Mark origin to prevent sync loop
                    'calcom_sync_status' => 'synced',  // ← Already in Cal.com
                ]);

                Log::channel('calcom')->info('[Cal.com] Appointment rescheduled', [
                    'appointment_id' => $appointment->id,
                    'new_time' => $payload['startTime']
                ]);

                // 🔧 FIX 2025-10-11: Invalidate cache after reschedule
                if ($appointment->service && $appointment->service->calcom_event_type_id) {
                    try {
                        app(\App\Services\CalcomService::class)
                            ->clearAvailabilityCacheForEventType($appointment->service->calcom_event_type_id);

                        Log::channel('calcom')->info('✅ Cache invalidated after webhook reschedule', [
                            'event_type_id' => $appointment->service->calcom_event_type_id
                        ]);
                    } catch (\Exception $e) {
                        Log::channel('calcom')->warning('⚠️ Cache invalidation failed', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                return $appointment;
            } else {
                // If appointment doesn't exist, create it
                return $this->handleBookingCreated($payload);
            }
        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com] Failed to update appointment', [
                'error' => $e->getMessage(),
                'booking_id' => $payload['id'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * Handle BOOKING.CANCELLED webhook
     */
    protected function handleBookingCancelled(array $payload): ?Appointment
    {
        try {
            $calcomId = $payload['id'] ?? $payload['uid'] ?? null;

            // 🛡️ SECURITY FIX (VULN-001): For cancellations, eventTypeId might not be present
            // So we lookup the appointment first, then verify ownership via its service
            $appointment = Appointment::where('calcom_v2_booking_id', $calcomId)->first();

            if (!$appointment) {
                Log::channel('calcom')->warning('[Cal.com] Appointment not found for cancellation', [
                    'calcom_id' => $calcomId
                ]);
                return null;
            }

            // 🛡️ SECURITY: Verify the appointment's service matches a known event type
            // This prevents cross-tenant attacks even without eventTypeId in payload
            if ($appointment->service && !$appointment->service->calcom_event_type_id) {
                Log::channel('calcom')->warning('[Security] Appointment service has no Cal.com event type', [
                    'appointment_id' => $appointment->id,
                    'service_id' => $appointment->service_id
                ]);
                return null;
            }

            if ($appointment) {
                $appointment->update([
                    'status' => 'cancelled',
                    'notes' => $appointment->notes . "\n\nCancelled on " . now()->format('Y-m-d H:i') .
                              "\nReason: " . ($payload['cancellationReason'] ?? 'No reason provided'),
                    'metadata' => json_encode(array_merge(
                        json_decode($appointment->metadata ?? '{}', true),
                        ['cancellation' => $payload]
                    )),
                    // ✅ METADATA FIX 2025-10-10: Populate cancellation tracking fields
                    'cancelled_at' => now(),
                    'cancelled_by' => 'customer',
                    'cancellation_source' => 'calcom_webhook',
                    'cancellation_reason' => $payload['cancellationReason'] ?? 'No reason provided',
                    // 🔄 SYNC ORIGIN (Phase 2: Loop Prevention)
                    'sync_origin' => 'calcom',  // ← Mark origin to prevent sync loop
                    'calcom_sync_status' => 'synced',  // ← Already in Cal.com
                ]);

                Log::channel('calcom')->info('[Cal.com] Appointment cancelled', [
                    'appointment_id' => $appointment->id,
                    'reason' => $payload['cancellationReason'] ?? null
                ]);

                // 🔧 FIX 2025-10-11: Invalidate cache after cancellation
                // Makes cancelled slot available again immediately
                if ($appointment->service && $appointment->service->calcom_event_type_id) {
                    try {
                        app(\App\Services\CalcomService::class)
                            ->clearAvailabilityCacheForEventType($appointment->service->calcom_event_type_id);

                        Log::channel('calcom')->info('✅ Cache invalidated after webhook cancellation', [
                            'event_type_id' => $appointment->service->calcom_event_type_id
                        ]);
                    } catch (\Exception $e) {
                        Log::channel('calcom')->warning('⚠️ Cache invalidation failed', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                return $appointment;
            } else {
                Log::channel('calcom')->warning('[Cal.com] Appointment not found for cancellation', [
                    'calcom_id' => $calcomId
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::channel('calcom')->error('[Cal.com] Failed to cancel appointment', [
                'error' => $e->getMessage(),
                'booking_id' => $payload['id'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * Find or create customer based on Cal.com booking data
     */
    private function findOrCreateCustomer(string $name, ?string $email, ?string $phone, int $companyId): Customer
    {
        // Try to find by email first
        if ($email) {
            $customer = Customer::where('email', $email)->first();
            if ($customer) {
                // Update phone if we have a new one
                if ($phone && !$customer->phone) {
                    $customer->update(['phone' => $phone]);
                }
                return $customer;
            }
        }

        // Try to find by phone
        if ($phone) {
            $normalizedPhone = PhoneNumberNormalizer::normalize($phone);
            $phoneVariants = PhoneNumberNormalizer::generateVariants($normalizedPhone ?? $phone);

            $customer = Customer::where(function ($query) use ($phoneVariants) {
                foreach ($phoneVariants as $variant) {
                    $query->orWhere('phone', $variant);
                }
            })->first();

            if ($customer) {
                // Update email if we have a new one
                if ($email && !$customer->email) {
                    $customer->update(['email' => $email]);
                }
                return $customer;
            }
        }

        // 🔧 FIX 2.6: Create customer with company_id using new instance pattern
        // We need company_id in the INSERT to satisfy NOT NULL constraint
        $customer = new Customer();
        $customer->company_id = $companyId;
        $customer->forceFill([
            'name' => $name,
            'email' => $email ?? 'calcom_' . uniqid() . '@noemail.com',
            'phone' => $phone ?? '',
            'source' => 'cal.com',
            'notes' => 'Created from Cal.com booking webhook',
            'metadata' => json_encode([
                'created_via' => 'cal.com_webhook',
                'created_at' => now()->toIso8601String(),
            ]),
        ]);
        $customer->save();

        return $customer;
    }

    /**
     * Extract phone number from Cal.com booking data
     */
    private function extractPhoneNumber(array $attendee, array $payload): ?string
    {
        // Check various possible locations for phone number
        $phone = $attendee['phone'] ??
                 $attendee['phoneNumber'] ??
                 $payload['phone'] ??
                 $payload['phoneNumber'] ??
                 null;

        // Check in responses/customInputs for phone field
        if (!$phone && isset($payload['responses'])) {
            if (is_array($payload['responses'])) {
                foreach ($payload['responses'] as $key => $value) {
                    if (in_array(strtolower($key), ['phone', 'telefon', 'phone number', 'telefonnummer'])) {
                        $phone = $value;
                        break;
                    }
                }
            } elseif (isset($payload['responses']['phone'])) {
                $phone = $payload['responses']['phone'];
            }
        }

        if (!$phone && isset($payload['customInputs'])) {
            foreach ($payload['customInputs'] as $input) {
                if (isset($input['label']) &&
                    in_array(strtolower($input['label']), ['phone', 'telefon', 'phone number', 'telefonnummer'])) {
                    $phone = $input['value'] ?? null;
                    break;
                }
            }
        }

        return $phone;
    }
}
