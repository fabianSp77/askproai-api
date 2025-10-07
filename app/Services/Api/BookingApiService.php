<?php

namespace App\Services\Api;

use App\Models\Service;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CalcomEventMap;
use App\Services\Booking\CompositeBookingService;
use App\Services\CalcomV2Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service class for handling booking-related business logic
 * Extracted from BookingController to improve testability and separation of concerns
 */
class BookingApiService
{
    private CompositeBookingService $compositeService;
    private CalcomV2Client $calcom;

    public function __construct(
        CompositeBookingService $compositeService,
        CalcomV2Client $calcom
    ) {
        $this->compositeService = $compositeService;
        $this->calcom = $calcom;
    }

    /**
     * Create a new booking (simple or composite)
     *
     * @param array $data Validated booking data
     * @return array Result with appointment data
     * @throws \Exception
     */
    public function createBooking(array $data): array
    {
        $service = Service::findOrFail($data['service_id']);
        $customer = $this->getOrCreateCustomer($data['customer']);

        if ($service->isComposite()) {
            return $this->createCompositeBooking($service, $customer, $data);
        }

        return $this->createSimpleBooking($service, $customer, $data);
    }

    /**
     * Create a composite booking
     *
     * @param Service $service
     * @param Customer $customer
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function createCompositeBooking(Service $service, Customer $customer, array $data): array
    {
        $segments = $this->buildSegmentsFromService($service, $data);

        if (empty($segments)) {
            throw new \Exception('Unable to build segments for composite booking');
        }

        $appointment = $this->compositeService->bookComposite([
            'company_id' => $customer->company_id,  // Use customer's company_id for multi-tenant isolation
            'branch_id' => $data['branch_id'],
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email
            ],
            'segments' => $segments,
            'timeZone' => $data['timeZone'],
            'source' => $data['source'] ?? 'api'
        ]);

        return [
            'appointment_id' => $appointment->id,
            'composite_uid' => $appointment->composite_group_uid,
            'status' => $appointment->status,
            'starts_at' => $appointment->starts_at,
            'ends_at' => $appointment->ends_at,
            'segments' => $appointment->segments,
            'confirmation_code' => substr($appointment->composite_group_uid, 0, 8)
        ];
    }

    /**
     * Create a simple booking
     *
     * @param Service $service
     * @param Customer $customer
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function createSimpleBooking(Service $service, Customer $customer, array $data): array
    {
        DB::beginTransaction();

        try {
            // Get event mapping
            $eventMapping = CalcomEventMap::where('service_id', $service->id)
                ->where('staff_id', $data['staff_id'] ?? null)
                ->where('sync_status', 'synced')
                ->first();

            if (!$eventMapping) {
                throw new \Exception('No Cal.com event mapping found for this service');
            }

            // Calculate end time
            $start = Carbon::parse($data['start']);
            $end = $start->copy()->addMinutes($service->duration_minutes);

            // Create booking in Cal.com
            $response = $this->calcom->createBooking([
                'eventTypeId' => $eventMapping->event_type_id,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'timeZone' => $data['timeZone'],
                'name' => $customer->name,
                'email' => $customer->email
            ]);

            if (!$response->successful()) {
                throw new \Exception('Cal.com booking failed: ' . $response->body());
            }

            $bookingData = $response->json('data');

            // Create appointment record
            $appointment = Appointment::create([
                'company_id' => $customer->company_id,  // Use customer's company_id for multi-tenant isolation
                'branch_id' => $data['branch_id'],
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'staff_id' => $data['staff_id'] ?? null,
                'is_composite' => false,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'booked',
                'source' => $data['source'] ?? 'api',
                'calcom_v2_booking_id' => $bookingData['id'] ?? null,
                'metadata' => [
                    'booking_response' => $bookingData
                ]
            ]);

            DB::commit();

            return [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'confirmation_code' => substr($appointment->id, 0, 8)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Simple booking failed', [
                'service_id' => $service->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Reschedule an existing appointment
     *
     * @param int $appointmentId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function rescheduleAppointment(int $appointmentId, array $data): array
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->isComposite()) {
            return $this->rescheduleCompositeAppointment($appointment, $data);
        }

        return $this->rescheduleSimpleAppointment($appointment, $data);
    }

    /**
     * Reschedule a composite appointment
     *
     * @param Appointment $appointment
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function rescheduleCompositeAppointment(Appointment $appointment, array $data): array
    {
        $segments = $this->buildRescheduledSegments($appointment, $data['start']);

        $newAppointment = $this->compositeService->rescheduleComposite($appointment, [
            'company_id' => $appointment->company_id,
            'branch_id' => $appointment->branch_id,
            'service_id' => $appointment->service_id,
            'customer_id' => $appointment->customer_id,
            'customer' => [
                'name' => $appointment->customer->name,
                'email' => $appointment->customer->email
            ],
            'segments' => $segments,
            'timeZone' => $data['timeZone']
        ]);

        return [
            'new_appointment_id' => $newAppointment->id,
            'old_appointment_id' => $appointment->id,
            'starts_at' => $newAppointment->starts_at,
            'ends_at' => $newAppointment->ends_at
        ];
    }

    /**
     * Reschedule a simple appointment
     *
     * @param Appointment $appointment
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function rescheduleSimpleAppointment(Appointment $appointment, array $data): array
    {
        $start = Carbon::parse($data['start']);
        $end = $start->copy()->addMinutes($appointment->service->duration_minutes);

        $response = $this->calcom->rescheduleBooking($appointment->calcom_v2_booking_id, [
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'timeZone' => $data['timeZone'],
            'reason' => $data['reason'] ?? 'Customer requested reschedule'
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to reschedule in Cal.com');
        }

        $appointment->update([
            'starts_at' => $start,
            'ends_at' => $end,
            'metadata' => array_merge($appointment->metadata ?? [], [
                'rescheduled_at' => now()->toIso8601String(),
                'reschedule_reason' => $data['reason'] ?? null
            ])
        ]);

        return [
            'appointment_id' => $appointment->id,
            'starts_at' => $appointment->starts_at,
            'ends_at' => $appointment->ends_at
        ];
    }

    /**
     * Cancel an appointment
     *
     * @param int $appointmentId
     * @param string|null $reason
     * @return array
     * @throws \Exception
     */
    public function cancelAppointment(int $appointmentId, ?string $reason = null): array
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->isComposite()) {
            $success = $this->compositeService->cancelComposite($appointment);
        } else {
            $response = $this->calcom->cancelBooking(
                $appointment->calcom_v2_booking_id,
                $reason ?? 'Customer requested cancellation'
            );

            $success = $response->successful();

            if ($success) {
                $appointment->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'cancelled_at' => now()->toIso8601String(),
                        'cancellation_reason' => $reason
                    ])
                ]);
            }
        }

        if (!$success) {
            throw new \Exception('Failed to cancel appointment');
        }

        return [
            'appointment_id' => $appointment->id,
            'status' => 'cancelled'
        ];
    }

    /**
     * Get or create customer record
     *
     * @param array $data
     * @return Customer
     */
    private function getOrCreateCustomer(array $data): Customer
    {
        return Customer::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'company_id' => auth()->user()->company_id ?? 1
            ]
        );
    }

    /**
     * Build segments from service definition
     *
     * @param Service $service
     * @param array $data
     * @return array
     */
    private function buildSegmentsFromService(Service $service, array $data): array
    {
        $segments = [];
        $serviceSegments = $service->segments;

        if (empty($serviceSegments)) {
            return [];
        }

        $currentTime = Carbon::parse($data['start']);

        foreach ($serviceSegments as $index => $segment) {
            $duration = $segment['durationMin'] ?? 60;
            $endTime = $currentTime->copy()->addMinutes($duration);

            $segments[] = [
                'key' => $segment['key'],
                'name' => $segment['name'] ?? "Segment {$segment['key']}",
                'starts_at' => $currentTime->toIso8601String(),
                'ends_at' => $endTime->toIso8601String(),
                'staff_id' => $data['segments'][$index]['staff_id'] ?? null
            ];

            // Add gap after segment (except for last)
            if ($index < count($serviceSegments) - 1) {
                $gap = $segment['gapAfterMin'] ?? 30;
                $currentTime = $endTime->copy()->addMinutes($gap);
            }
        }

        return $segments;
    }

    /**
     * Build rescheduled segments
     *
     * @param Appointment $appointment
     * @param string $newStart
     * @return array
     */
    private function buildRescheduledSegments(Appointment $appointment, string $newStart): array
    {
        $segments = [];
        $oldSegments = $appointment->segments;
        $currentTime = Carbon::parse($newStart);

        foreach ($oldSegments as $segment) {
            $duration = Carbon::parse($segment['starts_at'])->diffInMinutes(Carbon::parse($segment['ends_at']));
            $endTime = $currentTime->copy()->addMinutes($duration);

            $segments[] = [
                'key' => $segment['key'],
                'name' => $segment['name'] ?? "Segment {$segment['key']}",
                'starts_at' => $currentTime->toIso8601String(),
                'ends_at' => $endTime->toIso8601String(),
                'staff_id' => $segment['staff_id']
            ];

            // Calculate gap to next segment
            if (next($oldSegments)) {
                $nextOldSegment = current($oldSegments);
                $gap = Carbon::parse($segment['ends_at'])->diffInMinutes(Carbon::parse($nextOldSegment['starts_at']));
                $currentTime = $endTime->copy()->addMinutes($gap);
            }
        }

        return $segments;
    }

    /**
     * Validate booking availability
     *
     * @param int $serviceId
     * @param int $branchId
     * @param string $start
     * @param int|null $staffId
     * @return bool
     */
    public function validateBookingAvailability(int $serviceId, int $branchId, string $start, ?int $staffId = null): bool
    {
        // Integrate with AvailabilityService
        $availabilityService = app(\App\Services\Booking\AvailabilityService::class);
        $date = Carbon::parse($start)->startOfDay();

        $slots = $availabilityService->getAvailableSlots(
            $serviceId,
            $branchId,
            $date,
            $staffId
        );

        // Check if the requested start time is in available slots
        $requestedTime = Carbon::parse($start);

        foreach ($slots as $slot) {
            $slotStart = Carbon::parse($slot['start']);
            if ($slotStart->equalTo($requestedTime)) {
                return true;
            }
        }

        return false;
    }
}