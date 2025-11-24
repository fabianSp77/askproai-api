<?php

namespace App\Services\Booking;

use App\Models\Service;
use App\Models\Appointment;
use App\Models\Staff;
use App\Services\CalcomV2Client;
use App\Services\Communication\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class CompositeBookingService
{
    public function __construct(
        private CalcomV2Client $calcom,
        private BookingLockService $locks,
        private NotificationService $notifier
    ) {}

    /**
     * Find available composite slots for a service
     */
    public function findCompositeSlots(Service $service, array $filters): Collection
    {
        if (!$service->isComposite()) {
            throw new Exception('Service is not composite');
        }

        $segments = $service->getSegments();
        if (count($segments) < 2) {
            throw new Exception('Composite service must have at least 2 segments');
        }

        // Get slots for segment A
        $segmentA = $segments[0];
        $staffA = $this->getCapableStaff($service, $segmentA['key']);

        if ($staffA->isEmpty()) {
            return collect();
        }

        $slotsA = $this->getStaffSlots(
            $staffA,
            Carbon::parse($filters['start']),
            Carbon::parse($filters['end']),
            $segmentA['durationMin'] ?? 60
        );

        // For each slot A, find matching slot B after pause
        return $slotsA->map(function($slotA) use ($service, $segments, $filters) {
            $segmentB = $segments[1];

            // Calculate pause window
            $pauseStart = Carbon::parse($slotA['end']);
            $pauseEnd = $pauseStart->copy()->addMinutes($segments[0]['gapAfterMax'] ?? 60);

            // Find staff for segment B
            $staffB = $this->getCapableStaff($service, $segmentB['key']);

            // Allow different staff if preferSameStaff is false
            if (!($segments[0]['preferSameStaff'] ?? false)) {
                // Get all capable staff for segment B
                $slotsB = $this->getStaffSlots(
                    $staffB,
                    $pauseStart->copy()->addMinutes($segments[0]['gapAfterMin'] ?? 30),
                    $pauseEnd,
                    $segmentB['durationMin'] ?? 45
                );
            } else {
                // Try to use same staff
                $slotsB = $this->getStaffSlots(
                    $staffB->where('id', $slotA['staff_id']),
                    $pauseStart->copy()->addMinutes($segments[0]['gapAfterMin'] ?? 30),
                    $pauseEnd,
                    $segmentB['durationMin'] ?? 45
                );
            }

            if ($slotsB->isEmpty()) {
                return null;
            }

            $slotB = $slotsB->first();

            return [
                'composite_slot_id' => Str::uuid(),
                'starts_at' => $slotA['start'],
                'ends_at' => Carbon::parse($slotB['end']),
                'total_duration' => Carbon::parse($slotA['start'])->diffInMinutes(Carbon::parse($slotB['end'])),
                'segments' => [
                    [
                        'key' => $segmentA['key'],
                        'name' => $segmentA['name'],
                        'staff_id' => $slotA['staff_id'],
                        'staff_name' => $slotA['staff_name'],
                        'starts_at' => $slotA['start'],
                        'ends_at' => $slotA['end'],
                        'duration' => $segmentA['durationMin']
                    ],
                    [
                        'key' => $segmentB['key'],
                        'name' => $segmentB['name'],
                        'staff_id' => $slotB['staff_id'],
                        'staff_name' => $slotB['staff_name'],
                        'starts_at' => $slotB['start'],
                        'ends_at' => $slotB['end'],
                        'duration' => $segmentB['durationMin']
                    ]
                ],
                'pause' => [
                    'starts_at' => $slotA['end'],
                    'ends_at' => $slotB['start'],
                    'duration' => Carbon::parse($slotA['end'])->diffInMinutes(Carbon::parse($slotB['start']))
                ]
            ];
        })->filter()->values();
    }

    /**
     * Book a composite appointment
     */
    public function bookComposite(array $data): Appointment
    {
        $compositeUid = Str::uuid();

        // Check for idempotency
        $existing = Appointment::where('composite_group_uid', $compositeUid)->first();
        if ($existing) {
            Log::info('Composite booking already exists', ['uid' => $compositeUid]);
            return $existing;
        }

        // Validate segments
        if (empty($data['segments']) || count($data['segments']) < 2) {
            throw new Exception('At least 2 segments required for composite booking');
        }

        // PHASE 2: Apply staff preference if specified
        if (isset($data['preferred_staff_id']) && !empty($data['preferred_staff_id'])) {
            Log::info('📌 Applying staff preference to all segments', [
                'staff_id' => $data['preferred_staff_id'],
                'segments' => count($data['segments'])
            ]);

            foreach ($data['segments'] as &$segment) {
                // Only set staff_id if not already set
                if (!isset($segment['staff_id']) || empty($segment['staff_id'])) {
                    $segment['staff_id'] = $data['preferred_staff_id'];
                }
            }
            unset($segment); // Break reference
        } else {
            // 🔧 FIX 2025-11-22: Auto-assign available staff if not specified
            // This prevents appointments being created without staff assignment
            Log::info('🔍 No staff preference specified, auto-assigning available staff', [
                'service_id' => $data['service_id'],
                'segments' => count($data['segments'])
            ]);

            $autoAssignedStaffId = $this->findAvailableStaffForService(
                $data['service_id'],
                $data['branch_id'],
                $data['segments'][0]['starts_at'],
                end($data['segments'])['ends_at']
            );

            if (!$autoAssignedStaffId) {
                Log::error('❌ No available staff found for composite service', [
                    'service_id' => $data['service_id'],
                    'branch_id' => $data['branch_id'],
                    'start' => $data['segments'][0]['starts_at'],
                    'end' => end($data['segments'])['ends_at']
                ]);
                throw new Exception('Kein verfügbarer Mitarbeiter für diesen Termin gefunden');
            }

            Log::info('✅ Auto-assigned staff to all segments', [
                'staff_id' => $autoAssignedStaffId,
                'segments' => count($data['segments'])
            ]);

            foreach ($data['segments'] as &$segment) {
                if (!isset($segment['staff_id']) || empty($segment['staff_id'])) {
                    $segment['staff_id'] = $autoAssignedStaffId;
                }
            }
            unset($segment); // Break reference
        }

        return DB::transaction(function() use ($data, $compositeUid) {
            $bookings = [];

            try {
                // 🔒 RACE CONDITION FIX (RC4): Use deadlock-safe acquireMultipleLocks()
                // This automatically sorts locks by key to prevent deadlocks
                // See: claudedocs/08_REFERENCE/CONCURRENCY_RACE_CONDITIONS_2025-10-17.md#rc4
                $lockRequests = collect($data['segments'])->map(function($segment) {
                    return [
                        'staff_id' => $segment['staff_id'],
                        'start' => $segment['starts_at'],
                        'end' => $segment['ends_at'],
                    ];
                })->toArray();

                $locks = $this->locks->acquireMultipleLocks($lockRequests);

                if (empty($locks)) {
                    throw new BookingConflictException('Unable to acquire locks for all staff members');
                }

                // Book in reverse order (B → A) for safer rollback
                foreach (array_reverse($data['segments']) as $index => $segment) {
                    try {
                        $eventMapping = $this->getEventTypeMapping(
                            $data['service_id'],
                            $segment['key'],
                            $segment['staff_id']
                        );

                        if (!$eventMapping) {
                            throw new Exception("No Cal.com event type mapping for segment {$segment['key']}");
                        }

                        $bookingResponse = $this->calcom->createBooking([
                            'eventTypeId' => $eventMapping->event_type_id,
                            'start' => Carbon::parse($segment['starts_at'])->toIso8601String(),
                            'end' => Carbon::parse($segment['ends_at'])->toIso8601String(),
                            'timeZone' => $data['timeZone'] ?? 'Europe/Berlin',
                            'name' => $data['customer']['name'],
                            'email' => $data['customer']['email'],
                            'metadata' => [
                                'composite_group_uid' => $compositeUid,
                                'segment_key' => $segment['key'],
                                'segment_index' => count($data['segments']) - $index - 1
                            ]
                        ]);

                        if (!$bookingResponse->successful()) {
                            throw new Exception('Cal.com booking failed: ' . $bookingResponse->body());
                        }

                        $bookingData = $bookingResponse->json();
                        $bookings[] = [
                            'booking_id' => $bookingData['data']['id'] ?? null,
                            'segment' => $segment,
                            'response' => $bookingData
                        ];

                    } catch (Exception $e) {
                        // Compensating Saga: Cancel all previously successful bookings
                        $this->compensateFailedBookings($bookings);
                        throw $e;
                    }
                }

                // Create appointment record
                $appointment = Appointment::create([
                    'company_id' => $data['company_id'],
                    'branch_id' => $data['branch_id'],
                    'service_id' => $data['service_id'],
                    'customer_id' => $data['customer_id'],
                    'staff_id' => $data['segments'][0]['staff_id'], // Primary staff
                    'is_composite' => true,
                    'composite_group_uid' => $compositeUid,
                    'starts_at' => $data['segments'][0]['starts_at'],
                    'ends_at' => end($data['segments'])['ends_at'],
                    'segments' => array_map(function($booking, $index) use ($data) {
                        return [
                            'index' => $index,
                            'key' => $data['segments'][$index]['key'],
                            'staff_id' => $data['segments'][$index]['staff_id'],
                            'booking_id' => $booking['booking_id'],
                            'starts_at' => $data['segments'][$index]['starts_at'],
                            'ends_at' => $data['segments'][$index]['ends_at'],
                            'status' => 'booked'
                        ];
                    }, array_reverse($bookings), array_keys(array_reverse($bookings))),
                    'status' => 'booked',
                    'source' => $data['source'] ?? 'api',
                    'metadata' => [
                        'composite' => true,
                        'segment_count' => count($data['segments']),
                        'pause_duration' => Carbon::parse($data['segments'][0]['ends_at'])
                            ->diffInMinutes(Carbon::parse($data['segments'][1]['starts_at']))
                    ]
                ]);

                // Send confirmation
                $this->notifier->sendCompositeConfirmation($appointment);

                return $appointment;

            } finally {
                // Always release locks - use releaseMultipleLocks() for proper cleanup
                if (!empty($locks)) {
                    $this->locks->releaseMultipleLocks(array_map(fn($lockData) => $lockData['lock'], $locks));
                }
            }
        });
    }

    /**
     * Cancel composite appointment
     */
    public function cancelComposite(Appointment $appointment): bool
    {
        if (!$appointment->isComposite()) {
            throw new Exception('Appointment is not composite');
        }

        return DB::transaction(function() use ($appointment) {
            $success = true;

            // Cancel all segment bookings
            foreach ($appointment->getSegments() as $segment) {
                if ($segment['booking_id']) {
                    $response = $this->calcom->cancelBooking(
                        $segment['booking_id'],
                        'Customer requested cancellation'
                    );

                    if (!$response->successful()) {
                        Log::error('Failed to cancel segment booking', [
                            'booking_id' => $segment['booking_id'],
                            'response' => $response->body()
                        ]);
                        $success = false;
                    }
                }
            }

            // Update appointment status
            $appointment->update([
                'status' => 'cancelled',
                'metadata' => array_merge($appointment->metadata ?? [], [
                    'cancelled_at' => now()->toIso8601String(),
                    'cancellation_reason' => 'Customer requested'
                ])
            ]);

            // Send cancellation notification
            $this->notifier->sendCancellationNotification($appointment);

            return $success;
        });
    }

    /**
     * Reschedule composite appointment
     */
    public function rescheduleComposite(Appointment $appointment, array $newData): Appointment
    {
        if (!$appointment->isComposite()) {
            throw new Exception('Appointment is not composite');
        }

        // Cancel existing appointment
        $this->cancelComposite($appointment);

        // Book new appointment
        return $this->bookComposite(array_merge($newData, [
            'metadata' => [
                'rescheduled_from' => $appointment->id,
                'original_start' => $appointment->starts_at,
                'original_end' => $appointment->ends_at
            ]
        ]));
    }

    /**
     * Get capable staff for a segment
     */
    private function getCapableStaff(Service $service, string $segmentKey): Collection
    {
        return $service->staff()
            ->where('is_active', true)
            ->where('can_book', true)
            ->whereJsonContains('allowed_segments', $segmentKey)
            ->orderBy('weight', 'desc')
            ->get();
    }

    /**
     * Get available slots for staff
     */
    private function getStaffSlots(Collection $staff, Carbon $start, Carbon $end, int $duration): Collection
    {
        $allSlots = collect();

        foreach ($staff as $member) {
            $eventMapping = $this->getStaffEventMapping($member);

            if (!$eventMapping) {
                continue;
            }

            $response = $this->calcom->getAvailableSlots(
                $eventMapping->event_type_id,
                $start,
                $end
            );

            if ($response->successful()) {
                $slots = collect($response->json('data.slots') ?? [])
                    ->map(function($slot) use ($member) {
                        return [
                            'staff_id' => $member->id,
                            'staff_name' => $member->name,
                            'start' => $slot['start'],
                            'end' => $slot['end']
                        ];
                    });

                $allSlots = $allSlots->merge($slots);
            }
        }

        return $allSlots->sortBy('start')->values();
    }

    /**
     * Get event type mapping for segment
     */
    private function getEventTypeMapping($serviceId, $segmentKey, $staffId)
    {
        return \App\Models\CalcomEventMap::where('service_id', $serviceId)
            ->where('segment_key', $segmentKey)
            ->where('staff_id', $staffId)
            ->first();
    }

    /**
     * Get staff event mapping
     */
    private function getStaffEventMapping($staff)
    {
        return \App\Models\CalcomEventMap::where('staff_id', $staff->id)
            ->where('sync_status', 'synced')
            ->first();
    }

    /**
     * Compensate failed bookings by cancelling successful ones
     */
    private function compensateFailedBookings(array $bookings): void
    {
        Log::info('Starting compensation saga', ['bookings' => count($bookings)]);

        foreach ($bookings as $booking) {
            if ($booking['booking_id']) {
                try {
                    $this->calcom->cancelBooking(
                        $booking['booking_id'],
                        'Composite booking failed - automatic compensation'
                    );

                    Log::info('Compensated booking', ['booking_id' => $booking['booking_id']]);
                } catch (Exception $e) {
                    Log::error('Failed to compensate booking', [
                        'booking_id' => $booking['booking_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Find available staff for a service at given time range
     *
     * 🔧 FIX 2025-11-22: Auto-assign staff when no preference specified
     *
     * @param int $serviceId
     * @param string $branchId
     * @param string $startTime ISO 8601 format
     * @param string $endTime ISO 8601 format
     * @return string|null Staff UUID
     */
    private function findAvailableStaffForService(
        int $serviceId,
        string $branchId,
        string $startTime,
        string $endTime
    ): ?string {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        Log::info('🔍 Searching for available staff', [
            'service_id' => $serviceId,
            'branch_id' => $branchId,
            'start' => $start->format('Y-m-d H:i'),
            'end' => $end->format('Y-m-d H:i')
        ]);

        // Get all staff assigned to this service at this branch
        $staff = Staff::where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereHas('services', function($q) use ($serviceId) {
                $q->where('service_id', $serviceId)
                  ->where('service_staff.is_active', true);
            })
            ->whereDoesntHave('appointments', function($q) use ($start, $end) {
                $q->where(function($query) use ($start, $end) {
                    $query->where('starts_at', '<', $end)
                          ->where('ends_at', '>', $start);
                })
                ->whereIn('status', ['scheduled', 'confirmed', 'booked']);
            })
            ->first();

        if ($staff) {
            Log::info('✅ Found available staff', [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'calcom_user_id' => $staff->calcom_user_id
            ]);
            return $staff->id;
        }

        Log::warning('⚠️ No available staff found', [
            'service_id' => $serviceId,
            'branch_id' => $branchId,
            'time_range' => $start->format('Y-m-d H:i') . ' - ' . $end->format('Y-m-d H:i')
        ]);

        return null;
    }
}

class BookingConflictException extends Exception {}