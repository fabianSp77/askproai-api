<?php

namespace App\Services\Calcom;

use App\Services\Calcom\CalcomV2Client;
use App\Services\Calcom\DTOs\EventTypeDTO;
use App\Services\Calcom\DTOs\BookingDTO;
use App\Services\Calcom\DTOs\SlotDTO;
use App\Services\Calcom\Exceptions\CalcomApiException;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Cal.com V2 Service
 * 
 * High-level service that uses CalcomV2Client for API operations
 * and integrates with the application's domain models
 */
class CalcomV2Service
{
    private CalcomV2Client $client;
    private ?Company $company;

    public function __construct(?Company $company = null)
    {
        $this->company = $company;
        
        $apiKey = $company?->calcom_api_key ?? config('services.calcom.api_key');
        $this->client = new CalcomV2Client($apiKey);
    }

    /**
     * Get all event types for the company
     */
    public function getEventTypes(): Collection
    {
        $response = $this->client->getEventTypes();
        
        return collect($response)->map(fn($data) => EventTypeDTO::fromArray($data));
    }

    /**
     * Get event types for a specific staff member
     */
    public function getStaffEventTypes(Staff $staff): Collection
    {
        $filters = [];
        
        if ($staff->calcom_user_id) {
            $filters['userId'] = $staff->calcom_user_id;
        }
        
        $response = $this->client->getEventTypes($filters);
        
        return collect($response)->map(fn($data) => EventTypeDTO::fromArray($data));
    }

    /**
     * Get available slots for an event type
     */
    public function getAvailableSlots(
        int $eventTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $timeZone = null
    ): Collection {
        $params = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startDate->toIso8601String(),
            'endTime' => $endDate->toIso8601String(),
        ];
        
        if ($timeZone) {
            $params['timeZone'] = $timeZone;
        }
        
        $response = $this->client->getAvailableSlots($params);
        
        return collect($response)->map(fn($data) => SlotDTO::fromArray($data));
    }

    /**
     * Create a booking from an appointment
     */
    public function createBookingFromAppointment(Appointment $appointment): BookingDTO
    {
        $customer = $appointment->customer;
        $eventType = $appointment->calcomEventType;
        
        if (!$eventType) {
            throw new \InvalidArgumentException('Appointment must have a Cal.com event type');
        }
        
        $bookingData = [
            'start' => $appointment->start_time->toIso8601String(),
            'eventTypeId' => $eventType->calcom_id,
            'responses' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'notes' => $appointment->notes,
            ],
            'metadata' => [
                'appointment_id' => $appointment->id,
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'source' => 'askproai',
            ],
            'timeZone' => $appointment->branch->timezone ?? 'UTC',
            'language' => $appointment->language ?? 'en',
        ];
        
        // Add location if specified
        if ($appointment->location) {
            $bookingData['location'] = $appointment->location;
        }
        
        // Add custom fields from appointment metadata
        if ($appointment->metadata && isset($appointment->metadata['custom_fields'])) {
            $bookingData['responses'] = array_merge(
                $bookingData['responses'],
                $appointment->metadata['custom_fields']
            );
        }
        
        $booking = $this->client->createBooking($bookingData);
        
        // Update appointment with Cal.com booking details
        $appointment->update([
            'calcom_uid' => $booking->uid,
            'calcom_booking_id' => $booking->id,
            'status' => $this->mapBookingStatusToAppointmentStatus($booking->status),
        ]);
        
        return $booking;
    }

    /**
     * Get all bookings
     */
    public function getBookings(array $filters = []): Collection
    {
        $response = $this->client->getBookings($filters);
        
        return collect($response)->map(fn($data) => BookingDTO::fromArray($data));
    }

    /**
     * Get a single booking
     */
    public function getBooking(string $uid): BookingDTO
    {
        return $this->client->getBooking($uid);
    }

    /**
     * Reschedule an appointment
     */
    public function rescheduleAppointment(Appointment $appointment, Carbon $newStartTime): BookingDTO
    {
        if (!$appointment->calcom_uid) {
            throw new \InvalidArgumentException('Appointment does not have a Cal.com booking');
        }
        
        $data = [
            'start' => $newStartTime->toIso8601String(),
            'reason' => 'Rescheduled via AskProAI',
        ];
        
        $booking = $this->client->rescheduleBooking($appointment->calcom_uid, $data);
        
        // Update appointment
        $appointment->update([
            'start_time' => $newStartTime,
            'end_time' => $newStartTime->copy()->addMinutes($appointment->duration),
            'rescheduled_at' => now(),
            'rescheduled_from' => $appointment->start_time,
        ]);
        
        return $booking;
    }

    /**
     * Cancel an appointment
     */
    public function cancelAppointment(Appointment $appointment, string $reason = ''): array
    {
        if (!$appointment->calcom_uid) {
            throw new \InvalidArgumentException('Appointment does not have a Cal.com booking');
        }
        
        $result = $this->client->cancelBooking($appointment->calcom_uid, [
            'reason' => $reason ?: 'Cancelled via AskProAI',
        ]);
        
        // Update appointment
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
        
        return $result;
    }

    /**
     * Sync bookings from Cal.com
     */
    public function syncBookings(Carbon $from = null, Carbon $to = null): int
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now()->addDays(30);
        
        $filters = [
            'startTime' => $from->toIso8601String(),
            'endTime' => $to->toIso8601String(),
        ];
        
        $bookings = $this->getBookings($filters);
        $synced = 0;
        
        foreach ($bookings as $booking) {
            // Check if appointment already exists
            $appointment = Appointment::where('calcom_uid', $booking->uid)->first();
            
            if (!$appointment) {
                // Create new appointment from booking
                $this->createAppointmentFromBooking($booking);
                $synced++;
            } else {
                // Update existing appointment
                $this->updateAppointmentFromBooking($appointment, $booking);
                $synced++;
            }
        }
        
        return $synced;
    }

    /**
     * Create appointment from Cal.com booking
     */
    private function createAppointmentFromBooking(BookingDTO $booking): Appointment
    {
        // Find or create customer
        $attendee = $booking->getPrimaryAttendee();
        if (!$attendee) {
            throw new \InvalidArgumentException('Booking has no attendees');
        }
        
        $customer = Customer::firstOrCreate(
            ['email' => $attendee->email],
            [
                'name' => $attendee->name,
                'phone' => $booking->getResponse('phone'),
                'company_id' => $this->company->id,
            ]
        );
        
        // Find staff if possible
        $staff = null;
        if ($booking->userId) {
            $staff = Staff::where('calcom_user_id', $booking->userId)->first();
        }
        
        // Create appointment
        return Appointment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->company->branches()->first()->id, // Default to first branch
            'customer_id' => $customer->id,
            'staff_id' => $staff?->id,
            'calcom_event_type_id' => $booking->eventTypeId,
            'calcom_uid' => $booking->uid,
            'calcom_booking_id' => $booking->id,
            'title' => $booking->title,
            'description' => $booking->description,
            'start_time' => $booking->startTime,
            'end_time' => $booking->endTime,
            'duration' => $booking->getDurationInMinutes(),
            'status' => $this->mapBookingStatusToAppointmentStatus($booking->status),
            'location' => $booking->location,
            'notes' => $booking->getResponse('notes'),
            'metadata' => [
                'calcom_responses' => $booking->responses,
                'calcom_metadata' => $booking->metadata,
            ],
        ]);
    }

    /**
     * Update appointment from Cal.com booking
     */
    private function updateAppointmentFromBooking(Appointment $appointment, BookingDTO $booking): void
    {
        $updates = [];
        
        // Check for changes
        if ($appointment->start_time->toIso8601String() !== $booking->startTime->toIso8601String()) {
            $updates['start_time'] = $booking->startTime;
            $updates['end_time'] = $booking->endTime;
        }
        
        if ($appointment->status !== $this->mapBookingStatusToAppointmentStatus($booking->status)) {
            $updates['status'] = $this->mapBookingStatusToAppointmentStatus($booking->status);
        }
        
        if ($appointment->location !== $booking->location) {
            $updates['location'] = $booking->location;
        }
        
        if (!empty($updates)) {
            $appointment->update($updates);
        }
    }

    /**
     * Map Cal.com booking status to appointment status
     */
    private function mapBookingStatusToAppointmentStatus(string $bookingStatus): string
    {
        return match($bookingStatus) {
            BookingDTO::STATUS_ACCEPTED => 'confirmed',
            BookingDTO::STATUS_PENDING => 'scheduled',
            BookingDTO::STATUS_CANCELLED => 'cancelled',
            BookingDTO::STATUS_REJECTED => 'cancelled',
            default => 'scheduled',
        };
    }

    /**
     * Check available slots with conflict detection
     */
    public function checkAvailability(
        int $eventTypeId,
        Carbon $requestedTime,
        ?int $staffId = null
    ): bool {
        try {
            // Get slots for the requested day
            $startOfDay = $requestedTime->copy()->startOfDay();
            $endOfDay = $requestedTime->copy()->endOfDay();
            
            $slots = $this->getAvailableSlots($eventTypeId, $startOfDay, $endOfDay);
            
            // Check if requested time matches any available slot
            foreach ($slots as $slot) {
                if ($slot->start->equalTo($requestedTime)) {
                    // Additional check for staff conflicts if specified
                    if ($staffId) {
                        $hasConflict = Appointment::where('staff_id', $staffId)
                            ->where('start_time', '<=', $requestedTime)
                            ->where('end_time', '>', $requestedTime)
                            ->whereNotIn('status', ['cancelled', 'no_show'])
                            ->exists();
                            
                        if ($hasConflict) {
                            return false;
                        }
                    }
                    
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            // Log error but don't fail hard
            \Log::error('Failed to check availability', [
                'error' => $e->getMessage(),
                'event_type_id' => $eventTypeId,
                'requested_time' => $requestedTime->toIso8601String(),
            ]);
            
            return false;
        }
    }

    /**
     * Get health status
     */
    public function getHealthStatus(): array
    {
        return $this->client->healthCheck();
    }

    /**
     * Get service metrics
     */
    public function getMetrics(): array
    {
        return $this->client->getMetrics();
    }
}