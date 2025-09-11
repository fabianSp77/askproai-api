<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Appointment;
use App\Models\CalcomBooking;
use App\Models\CalcomEventType;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Company;
use App\Services\CalcomV2Service;
use Carbon\Carbon;

class CalcomWebhookControllerV2 extends Controller
{
    protected CalcomV2Service $calcomService;

    public function __construct(CalcomV2Service $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    /**
     * GET /api/calcom/webhook
     * Cal.com-Ping (keine Signatur-Prüfung).
     */
    public function ping(): JsonResponse
    {
        return response()->json(['ping' => 'ok', 'version' => 'v2']);
    }

    /**
     * POST /api/calcom/webhook
     * Handle Cal.com V2 webhook events
     */
    public function handle(Request $request): JsonResponse
    {
        $triggerEvent = $request->input('triggerEvent');
        $payload = $request->input('payload', []);
        
        // Log webhook event
        Log::channel('calcom')->info('[Cal.com V2] Webhook received', [
            'event' => $triggerEvent,
            'booking_uid' => $payload['uid'] ?? null,
            'booking_id' => $payload['id'] ?? null,
        ]);

        try {
            // Process based on event type
            switch ($triggerEvent) {
                case 'BOOKING_CREATED':
                case 'booking.created':
                    $this->handleBookingCreated($payload);
                    break;
                    
                case 'BOOKING_CANCELLED':
                case 'booking.cancelled':
                    $this->handleBookingCancelled($payload);
                    break;
                    
                case 'BOOKING_RESCHEDULED':
                case 'booking.rescheduled':
                    $this->handleBookingRescheduled($payload);
                    break;
                    
                case 'BOOKING_CONFIRMED':
                case 'booking.confirmed':
                    $this->handleBookingConfirmed($payload);
                    break;
                    
                case 'BOOKING_REJECTED':
                case 'booking.rejected':
                    $this->handleBookingRejected($payload);
                    break;
                    
                case 'BOOKING_REQUESTED':
                case 'booking.requested':
                    $this->handleBookingRequested($payload);
                    break;
                    
                case 'BOOKING_PAYMENT_INITIATED':
                case 'booking.payment_initiated':
                    $this->handlePaymentInitiated($payload);
                    break;
                    
                case 'MEETING_ENDED':
                case 'meeting.ended':
                    $this->handleMeetingEnded($payload);
                    break;
                    
                case 'RECORDING_READY':
                case 'recording.ready':
                    $this->handleRecordingReady($payload);
                    break;
                    
                default:
                    Log::warning('[Cal.com V2] Unknown webhook event', [
                        'event' => $triggerEvent
                    ]);
            }

            // Store webhook for audit
            $this->storeWebhookAudit($triggerEvent, $payload, 'success');

            return response()->json(['received' => true, 'status' => 'processed']);

        } catch (\Exception $e) {
            Log::error('[Cal.com V2] Webhook processing failed', [
                'event' => $triggerEvent,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Store failed webhook for retry
            $this->storeWebhookAudit($triggerEvent, $payload, 'failed', $e->getMessage());

            // Return success to prevent Cal.com retries (we'll handle internally)
            return response()->json(['received' => true, 'status' => 'error']);
        }
    }

    /**
     * Handle booking created event
     */
    protected function handleBookingCreated(array $payload): void
    {
        Log::info('[Cal.com V2] Processing booking.created', [
            'uid' => $payload['uid'] ?? null
        ]);

        // Find or create customer
        $customer = $this->findOrCreateCustomerFromPayload($payload);
        
        // Find staff if mapped
        $staff = $this->findStaffFromPayload($payload);
        
        // Find branch
        $branch = $this->findBranchFromPayload($payload, $staff);
        
        // Parse dates
        $startsAt = Carbon::parse($payload['startTime'] ?? $payload['start']);
        $endsAt = Carbon::parse($payload['endTime'] ?? $payload['end']);

        // Create or update appointment
        $appointment = Appointment::updateOrCreate(
            [
                'calcom_v2_booking_id' => $payload['id'],
            ],
            [
                'customer_id' => $customer->id,
                'staff_id' => $staff?->id,
                'branch_id' => $branch?->id,
                'company_id' => $branch?->company_id ?? Company::first()->id,
                'calcom_booking_uid' => $payload['uid'] ?? null,
                'calcom_user_id' => $payload['userId'] ?? null,
                'calcom_team_id' => $payload['teamId'] ?? null,
                'calcom_event_type_id' => $payload['eventTypeId'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
                'notes' => $payload['description'] ?? null,
                'meeting_url' => $payload['meetingUrl'] ?? null,
                'location_type' => $payload['location']['type'] ?? null,
                'location_value' => $payload['location']['value'] ?? $payload['location'] ?? null,
                'attendees' => json_encode($payload['attendees'] ?? []),
                'responses' => json_encode($payload['responses'] ?? []),
                'is_recurring' => !empty($payload['recurringEventId']),
                'recurring_event_id' => $payload['recurringEventId'] ?? null,
                'price' => $payload['payment']['amount'] ?? null,
                'source' => 'cal.com',
                'booking_metadata' => json_encode($payload['metadata'] ?? []),
                'payload' => json_encode($payload),
            ]
        );

        // Also create/update CalcomBooking record
        CalcomBooking::updateOrCreate(
            ['calcom_uid' => $payload['uid'] ?? $payload['id']],
            [
                'appointment_id' => $appointment->id,
                'status' => $payload['status'] ?? 'ACCEPTED',
                'raw_payload' => $payload,
            ]
        );

        Log::info('[Cal.com V2] Booking created/updated', [
            'appointment_id' => $appointment->id,
            'calcom_uid' => $payload['uid'] ?? null
        ]);
    }

    /**
     * Handle booking cancelled event
     */
    protected function handleBookingCancelled(array $payload): void
    {
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'] ?? null)
            ->first();

        if ($appointment) {
            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $payload['cancellationReason'] ?? 'Cancelled via Cal.com',
                'payload' => json_encode($payload),
            ]);

            // Update CalcomBooking status
            CalcomBooking::where('appointment_id', $appointment->id)
                ->update([
                    'status' => 'CANCELLED',
                    'raw_payload' => $payload,
                ]);

            Log::info('[Cal.com V2] Booking cancelled', [
                'appointment_id' => $appointment->id
            ]);
        } else {
            Log::warning('[Cal.com V2] Booking not found for cancellation', [
                'booking_id' => $payload['id'] ?? null,
                'uid' => $payload['uid'] ?? null
            ]);
        }
    }

    /**
     * Handle booking rescheduled event
     */
    protected function handleBookingRescheduled(array $payload): void
    {
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'] ?? null)
            ->first();

        if ($appointment) {
            $startsAt = Carbon::parse($payload['startTime'] ?? $payload['start']);
            $endsAt = Carbon::parse($payload['endTime'] ?? $payload['end']);

            $appointment->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'rescheduled_from_uid' => $payload['previousUid'] ?? $appointment->calcom_booking_uid,
                'meeting_url' => $payload['meetingUrl'] ?? $appointment->meeting_url,
                'payload' => json_encode($payload),
            ]);

            Log::info('[Cal.com V2] Booking rescheduled', [
                'appointment_id' => $appointment->id,
                'new_time' => $startsAt->toDateTimeString()
            ]);
        } else {
            // Create new appointment if not found
            $this->handleBookingCreated($payload);
        }
    }

    /**
     * Handle booking confirmed event (for bookings requiring confirmation)
     */
    protected function handleBookingConfirmed(array $payload): void
    {
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'] ?? null)
            ->first();

        if ($appointment) {
            $appointment->update([
                'status' => 'confirmed',
                'payload' => json_encode($payload),
            ]);

            CalcomBooking::where('appointment_id', $appointment->id)
                ->update([
                    'status' => 'ACCEPTED',
                    'raw_payload' => $payload,
                ]);

            Log::info('[Cal.com V2] Booking confirmed', [
                'appointment_id' => $appointment->id
            ]);
        } else {
            $this->handleBookingCreated($payload);
        }
    }

    /**
     * Handle booking rejected event
     */
    protected function handleBookingRejected(array $payload): void
    {
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'] ?? null)
            ->first();

        if ($appointment) {
            $appointment->update([
                'status' => 'cancelled',
                'rejected_reason' => $payload['rejectionReason'] ?? 'Rejected via Cal.com',
                'payload' => json_encode($payload),
            ]);

            CalcomBooking::where('appointment_id', $appointment->id)
                ->update([
                    'status' => 'REJECTED',
                    'raw_payload' => $payload,
                ]);

            Log::info('[Cal.com V2] Booking rejected', [
                'appointment_id' => $appointment->id
            ]);
        }
    }

    /**
     * Handle booking requested event (for bookings requiring confirmation)
     */
    protected function handleBookingRequested(array $payload): void
    {
        // Create appointment with pending status
        $customer = $this->findOrCreateCustomerFromPayload($payload);
        $staff = $this->findStaffFromPayload($payload);
        $branch = $this->findBranchFromPayload($payload, $staff);
        
        $startsAt = Carbon::parse($payload['startTime'] ?? $payload['start']);
        $endsAt = Carbon::parse($payload['endTime'] ?? $payload['end']);

        $appointment = Appointment::updateOrCreate(
            [
                'calcom_v2_booking_id' => $payload['id'],
            ],
            [
                'customer_id' => $customer->id,
                'staff_id' => $staff?->id,
                'branch_id' => $branch?->id,
                'company_id' => $branch?->company_id ?? Company::first()->id,
                'calcom_booking_uid' => $payload['uid'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'pending', // Requires confirmation
                'source' => 'cal.com',
                'payload' => json_encode($payload),
            ]
        );

        Log::info('[Cal.com V2] Booking requested (pending confirmation)', [
            'appointment_id' => $appointment->id
        ]);
    }

    /**
     * Handle payment initiated event
     */
    protected function handlePaymentInitiated(array $payload): void
    {
        Log::info('[Cal.com V2] Payment initiated', [
            'booking_id' => $payload['id'] ?? null,
            'amount' => $payload['payment']['amount'] ?? null
        ]);

        // Update appointment with payment info if needed
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['id'])
            ->orWhere('calcom_booking_uid', $payload['uid'] ?? null)
            ->first();

        if ($appointment && isset($payload['payment']['amount'])) {
            $appointment->update([
                'price' => $payload['payment']['amount'],
                'booking_metadata' => json_encode(array_merge(
                    json_decode($appointment->booking_metadata ?? '{}', true),
                    ['payment' => $payload['payment'] ?? []]
                )),
            ]);
        }
    }

    /**
     * Handle meeting ended event
     */
    protected function handleMeetingEnded(array $payload): void
    {
        Log::info('[Cal.com V2] Meeting ended', [
            'booking_id' => $payload['bookingId'] ?? null,
            'duration' => $payload['duration'] ?? null
        ]);

        // Update appointment status or log meeting duration
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['bookingId'])
            ->first();

        if ($appointment) {
            $appointment->update([
                'status' => 'completed',
                'booking_metadata' => json_encode(array_merge(
                    json_decode($appointment->booking_metadata ?? '{}', true),
                    [
                        'meeting_ended_at' => now()->toDateTimeString(),
                        'actual_duration' => $payload['duration'] ?? null
                    ]
                )),
            ]);
        }
    }

    /**
     * Handle recording ready event
     */
    protected function handleRecordingReady(array $payload): void
    {
        Log::info('[Cal.com V2] Recording ready', [
            'booking_id' => $payload['bookingId'] ?? null,
            'recording_url' => $payload['recordingUrl'] ?? null
        ]);

        // Store recording URL in appointment metadata
        $appointment = Appointment::where('calcom_v2_booking_id', $payload['bookingId'])
            ->first();

        if ($appointment) {
            $appointment->update([
                'booking_metadata' => json_encode(array_merge(
                    json_decode($appointment->booking_metadata ?? '{}', true),
                    [
                        'recording_url' => $payload['recordingUrl'] ?? null,
                        'recording_ready_at' => now()->toDateTimeString()
                    ]
                )),
            ]);
        }
    }

    /**
     * Find or create customer from webhook payload
     */
    protected function findOrCreateCustomerFromPayload(array $payload): Customer
    {
        $attendee = $payload['attendees'][0] ?? $payload['responses'] ?? [];
        
        $email = $attendee['email'] ?? $payload['responses']['email'] ?? 'unknown@example.com';
        $name = $attendee['name'] ?? $payload['responses']['name'] ?? 'Unknown Customer';
        $phone = $attendee['phoneNumber'] ?? $payload['responses']['phone'] ?? null;

        return Customer::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $phone,
                'locale' => $attendee['locale'] ?? 'de',
                'timezone' => $attendee['timeZone'] ?? 'Europe/Berlin',
                'notes' => 'Created via Cal.com webhook',
            ]
        );
    }

    /**
     * Find staff from webhook payload
     */
    protected function findStaffFromPayload(array $payload): ?Staff
    {
        if (!isset($payload['userId'])) {
            return null;
        }

        // Check if user is mapped to staff
        $calcomUser = DB::table('calcom_users')
            ->where('calcom_user_id', $payload['userId'])
            ->first();

        if ($calcomUser && $calcomUser->staff_id) {
            return Staff::find($calcomUser->staff_id);
        }

        // Try to find by organizer email if provided
        if (isset($payload['organizer']['email'])) {
            return Staff::where('email', $payload['organizer']['email'])->first();
        }

        return null;
    }

    /**
     * Find branch from webhook payload
     */
    protected function findBranchFromPayload(array $payload, ?Staff $staff): ?Branch
    {
        // If staff has home branch, use it
        if ($staff && $staff->home_branch_id) {
            return Branch::find($staff->home_branch_id);
        }

        // Try to find from team mapping
        if (isset($payload['teamId'])) {
            $team = DB::table('calcom_teams')
                ->where('calcom_team_id', $payload['teamId'])
                ->first();
                
            if ($team && $team->branch_id) {
                return Branch::find($team->branch_id);
            }
        }

        // Fallback to first branch
        return Branch::first();
    }

    /**
     * Store webhook for audit trail
     */
    protected function storeWebhookAudit(string $event, array $payload, string $status, ?string $error = null): void
    {
        try {
            DB::table('webhook_logs')->insert([
                'source' => 'calcom_v2',
                'event' => $event,
                'payload' => json_encode($payload),
                'status' => $status,
                'error' => $error,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table might not exist, log to file instead
            Log::channel('calcom')->info('[Cal.com V2] Webhook audit', [
                'event' => $event,
                'status' => $status,
                'error' => $error,
            ]);
        }
    }
}