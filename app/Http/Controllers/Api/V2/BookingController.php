<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Customer;
use App\Services\Booking\CompositeBookingService;
use App\Services\Booking\SimpleBookingService;
use App\Services\CalcomV2Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Requests\Api\V2\CreateBookingRequest;
use App\Http\Requests\Api\V2\RescheduleBookingRequest;
use App\Traits\ApiResponse;

class BookingController extends Controller
{
    use ApiResponse;

    private CompositeBookingService $compositeService;
    private CalcomV2Client $calcom;

    public function __construct(CompositeBookingService $compositeService, CalcomV2Client $calcom)
    {
        $this->compositeService = $compositeService;
        $this->calcom = $calcom;
    }

    /**
     * Create a booking (simple or composite)
     */
    public function create(CreateBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // SECURITY: Verify service belongs to authenticated user's company
        // Prevents cross-company service booking vulnerability (CVSS 8.2)
        $service = Service::where('company_id', auth()->user()->company_id)
            ->findOrFail($validated['service_id']);

        // Get or create customer
        $customer = $this->getOrCreateCustomer($validated['customer']);

        // Determine booking type
        if ($service->isComposite()) {
            return $this->createCompositeBooking($service, $customer, $validated);
        } else {
            return $this->createSimpleBooking($service, $customer, $validated);
        }
    }

    /**
     * Create composite booking
     */
    private function createCompositeBooking(Service $service, Customer $customer, array $data): JsonResponse
    {
        // Build segments from service definition, not from payload
        $segments = $this->buildSegmentsFromService($service, $data);

        if (empty($segments)) {
            return $this->errorResponse('Unable to build segments for composite booking');
        }

        try {
            $appointment = $this->compositeService->bookComposite([
                'company_id' => $service->company_id,
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

            return $this->createdResponse([
                'appointment_id' => $appointment->id,
                'composite_uid' => $appointment->composite_group_uid,
                'status' => $appointment->status,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'segments' => $appointment->segments,
                'confirmation_code' => substr($appointment->composite_group_uid, 0, 8)
            ], 'Composite booking created successfully');

        } catch (\Exception $e) {
            Log::error('Composite booking failed', [
                'service_id' => $service->id,
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'Booking failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Create simple booking
     */
    private function createSimpleBooking(Service $service, Customer $customer, array $data): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Get event mapping
            $eventMapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
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
                'company_id' => $service->company_id,
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

            return $this->createdResponse([
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'confirmation_code' => substr($appointment->id, 0, 8)
            ], 'Booking created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Simple booking failed', [
                'service_id' => $service->id,
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'Booking failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Reschedule appointment
     */
    public function reschedule($id, RescheduleBookingRequest $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validated();

        try {
            if ($appointment->isComposite()) {
                // Reschedule all segments atomically
                $newAppointment = $this->compositeService->rescheduleComposite($appointment, [
                    'company_id' => $appointment->company_id,
                    'branch_id' => $appointment->branch_id,
                    'service_id' => $appointment->service_id,
                    'customer_id' => $appointment->customer_id,
                    'customer' => [
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email
                    ],
                    'segments' => $this->buildRescheduledSegments($appointment, $validated['start']),
                    'timeZone' => $validated['timeZone']
                ]);

                return $this->successResponse([
                    'new_appointment_id' => $newAppointment->id,
                    'old_appointment_id' => $appointment->id,
                    'starts_at' => $newAppointment->starts_at,
                    'ends_at' => $newAppointment->ends_at
                ], 'Appointment rescheduled successfully');

            } else {
                // Simple reschedule
                $start = Carbon::parse($validated['start']);
                $end = $start->copy()->addMinutes($appointment->service->duration_minutes);

                $response = $this->calcom->rescheduleBooking($appointment->calcom_v2_booking_id, [
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                    'timeZone' => $validated['timeZone'],
                    'reason' => $validated['reason'] ?? 'Customer requested reschedule'
                ]);

                if ($response->successful()) {
                    $appointment->update([
                        'starts_at' => $start,
                        'ends_at' => $end,
                        'metadata' => array_merge($appointment->metadata ?? [], [
                            'rescheduled_at' => now()->toIso8601String(),
                            'reschedule_reason' => $validated['reason'] ?? null
                        ])
                    ]);

                    return $this->successResponse([
                        'appointment_id' => $appointment->id,
                        'starts_at' => $appointment->starts_at,
                        'ends_at' => $appointment->ends_at
                    ], 'Appointment rescheduled successfully');
                }

                throw new \Exception('Failed to reschedule in Cal.com');
            }

        } catch (\Exception $e) {
            Log::error('Reschedule failed', [
                'appointment_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'Reschedule failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel($id, Request $request): JsonResponse
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            if ($appointment->isComposite()) {
                // Cancel all segments atomically
                $success = $this->compositeService->cancelComposite($appointment);
            } else {
                // Simple cancel
                $response = $this->calcom->cancelBooking(
                    $appointment->calcom_v2_booking_id,
                    $validated['reason'] ?? 'Customer requested cancellation'
                );

                $success = $response->successful();

                if ($success) {
                    $appointment->update([
                        'status' => 'cancelled',
                        'metadata' => array_merge($appointment->metadata ?? [], [
                            'cancelled_at' => now()->toIso8601String(),
                            'cancellation_reason' => $validated['reason'] ?? null
                        ])
                    ]);
                }
            }

            if ($success) {
                return $this->successResponse([
                    'appointment_id' => $appointment->id,
                    'status' => 'cancelled'
                ], 'Appointment cancelled successfully');
            }

            throw new \Exception('Failed to cancel appointment');

        } catch (\Exception $e) {
            Log::error('Cancellation failed', [
                'appointment_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'Cancellation failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get or create customer
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
}