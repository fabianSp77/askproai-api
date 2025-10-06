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

        // Log the webhook event
        $webhookEvent = $this->logWebhookEvent($request, 'calcom', $data);

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

            // Find matching service FIRST (need company_id for customer)
            $service = null;
            if (isset($payload['eventTypeId'])) {
                $service = Service::where('calcom_event_type_id', $payload['eventTypeId'])->first();
            }

            // Get company_id from service or default
            $companyId = $service?->company_id ?? \App\Models\Company::first()?->id ?? 1;

            // Find or create customer WITH company_id
            $customer = $this->findOrCreateCustomer($customerName, $customerEmail, $customerPhone, $companyId);

            // PHASE 2: Staff Assignment Integration
            // Attempt to assign staff using multi-model assignment system
            $staffId = null;
            $assignmentMetadata = [];

            if ($service) {
                try {
                    $assignmentContext = new AssignmentContext(
                        companyId: $companyId,
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

            // Create appointment (use v2_booking_id for string IDs)
            $appointment = Appointment::updateOrCreate(
                ['calcom_v2_booking_id' => $calcomId],
                array_merge([
                    'customer_id' => $customer->id,
                    'company_id' => $companyId,
                    'service_id' => $service?->id,
                    'staff_id' => $staffId, // From multi-model assignment
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
                ], $assignmentMetadata) // Merge assignment metadata (model_used, was_fallback, assignment_metadata)
            );

            Log::channel('calcom')->info('[Cal.com] Appointment created from booking', [
                'appointment_id' => $appointment->id,
                'calcom_id' => $calcomId,
                'customer' => $customer->name,
                'staff_id' => $staffId,
                'assignment_model' => $assignmentMetadata['assignment_model_used'] ?? 'none',
                'time' => $startTime->format('Y-m-d H:i')
            ]);

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
     * Handle BOOKING.UPDATED webhook
     */
    protected function handleBookingUpdated(array $payload): ?Appointment
    {
        try {
            $calcomId = $payload['id'] ?? $payload['uid'] ?? null;

            $appointment = Appointment::where('calcom_v2_booking_id', $calcomId)
                ->first();

            if ($appointment) {
                $appointment->update([
                    'starts_at' => Carbon::parse($payload['startTime']),
                    'ends_at' => Carbon::parse($payload['endTime']),
                    'status' => $payload['status'] === 'CANCELLED' ? 'cancelled' : 'rescheduled',
                    'notes' => $appointment->notes . "\n\nRescheduled on " . now()->format('Y-m-d H:i'),
                    'metadata' => json_encode(array_merge(
                        json_decode($appointment->metadata ?? '{}', true),
                        ['last_update' => $payload]
                    )),
                ]);

                Log::channel('calcom')->info('[Cal.com] Appointment rescheduled', [
                    'appointment_id' => $appointment->id,
                    'new_time' => $payload['startTime']
                ]);
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

            $appointment = Appointment::where('calcom_v2_booking_id', $calcomId)
                ->first();

            if ($appointment) {
                $appointment->update([
                    'status' => 'cancelled',
                    'notes' => $appointment->notes . "\n\nCancelled on " . now()->format('Y-m-d H:i') .
                              "\nReason: " . ($payload['cancellationReason'] ?? 'No reason provided'),
                    'metadata' => json_encode(array_merge(
                        json_decode($appointment->metadata ?? '{}', true),
                        ['cancellation' => $payload]
                    )),
                ]);

                Log::channel('calcom')->info('[Cal.com] Appointment cancelled', [
                    'appointment_id' => $appointment->id,
                    'reason' => $payload['cancellationReason'] ?? null
                ]);
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
