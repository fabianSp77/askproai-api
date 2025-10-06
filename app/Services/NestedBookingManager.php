<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\NestedBookingSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages nested bookings for services with interruption periods
 * Example: Hairdresser coloring with processing time where stylist can serve other customers
 */
class NestedBookingManager
{
    // Service types that support nested bookings
    const SUPPORTS_NESTING = [
        'coloring' => [
            'main_duration' => 120, // 2 hours total
            'active_work' => 45,    // 45 min application
            'break_start' => 45,    // Break starts after 45 min
            'break_duration' => 45, // 45 min processing time
            'finish_work' => 30     // 30 min washing/styling
        ],
        'perm' => [
            'main_duration' => 150,
            'active_work' => 60,
            'break_start' => 60,
            'break_duration' => 60,
            'finish_work' => 30
        ],
        'highlights' => [
            'main_duration' => 180,
            'active_work' => 90,
            'break_start' => 90,
            'break_duration' => 60,
            'finish_work' => 30
        ]
    ];

    // Services that can be done during break periods
    const BREAK_COMPATIBLE_SERVICES = [
        'haircut' => 30,
        'beard_trim' => 15,
        'quick_styling' => 20,
        'consultation' => 15,
        'wash_and_dry' => 25
    ];

    /**
     * Create a nested booking structure for a service with breaks
     */
    public function createNestedBooking(
        array $bookingData,
        string $serviceType,
        Carbon $startTime
    ): array {
        if (!$this->supportsNesting($serviceType)) {
            Log::info('Service does not support nested bookings', ['service' => $serviceType]);
            return ['main_booking' => $bookingData, 'nested_slots' => []];
        }

        $config = self::SUPPORTS_NESTING[$serviceType];

        DB::beginTransaction();
        try {
            // Create main booking
            $mainBooking = $this->createMainBooking($bookingData, $startTime, $config);

            // Calculate break periods
            $breakPeriods = $this->calculateBreakPeriods($startTime, $config);

            // Create nested slots for break periods
            $nestedSlots = $this->createNestedSlots($mainBooking['id'], $breakPeriods);

            DB::commit();

            Log::info('✅ Created nested booking structure', [
                'main_booking_id' => $mainBooking['id'],
                'nested_slots' => count($nestedSlots),
                'service' => $serviceType
            ]);

            return [
                'main_booking' => $mainBooking,
                'nested_slots' => $nestedSlots,
                'timeline' => $this->buildTimeline($startTime, $config, $nestedSlots)
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create nested booking', [
                'error' => $e->getMessage(),
                'service' => $serviceType
            ]);
            throw $e;
        }
    }

    /**
     * Check if a service type supports nested bookings
     */
    public function supportsNesting(string $serviceType): bool
    {
        return array_key_exists($serviceType, self::SUPPORTS_NESTING);
    }

    /**
     * Find available nested slots for a given time range
     */
    public function findAvailableNestedSlots(
        Carbon $startTime,
        Carbon $endTime,
        ?string $serviceType = null
    ): Collection {
        $query = NestedBookingSlot::where('is_available', true)
            ->where('available_from', '>=', $startTime)
            ->where('available_to', '<=', $endTime);

        if ($serviceType) {
            $duration = self::BREAK_COMPATIBLE_SERVICES[$serviceType] ?? 30;
            $query->whereRaw('TIMESTAMPDIFF(MINUTE, available_from, available_to) >= ?', [$duration]);
        }

        return $query->get()->map(function($slot) {
            return [
                'id' => $slot->id,
                'parent_booking_id' => $slot->parent_booking_id,
                'available_from' => Carbon::parse($slot->available_from),
                'available_to' => Carbon::parse($slot->available_to),
                'duration_minutes' => Carbon::parse($slot->available_from)
                    ->diffInMinutes(Carbon::parse($slot->available_to)),
                'compatible_services' => $this->getCompatibleServices($slot)
            ];
        });
    }

    /**
     * Book a service into a nested slot
     */
    public function bookNestedSlot(
        int $slotId,
        array $bookingData,
        string $serviceType
    ): ?array {
        $slot = NestedBookingSlot::find($slotId);

        if (!$slot || !$slot->is_available) {
            Log::warning('Nested slot not available', ['slot_id' => $slotId]);
            return null;
        }

        // Check if service fits in the slot
        $slotDuration = Carbon::parse($slot->available_from)
            ->diffInMinutes(Carbon::parse($slot->available_to));

        $serviceDuration = self::BREAK_COMPATIBLE_SERVICES[$serviceType] ?? 30;

        if ($serviceDuration > $slotDuration) {
            Log::warning('Service does not fit in nested slot', [
                'service_duration' => $serviceDuration,
                'slot_duration' => $slotDuration
            ]);
            return null;
        }

        DB::beginTransaction();
        try {
            // Create the nested booking
            $nestedBooking = array_merge($bookingData, [
                'starts_at' => $slot->available_from,
                'ends_at' => Carbon::parse($slot->available_from)->addMinutes($serviceDuration),
                'is_nested' => true,
                'parent_booking_id' => $slot->parent_booking_id,
                'service_type' => $serviceType
            ]);

            // Mark slot as used
            $slot->update([
                'is_available' => false,
                'child_booking_id' => $nestedBooking['id'] ?? null
            ]);

            // If service is shorter than slot, create a new slot for remaining time
            if ($serviceDuration < $slotDuration) {
                $this->splitSlot($slot, $serviceDuration);
            }

            DB::commit();

            Log::info('✅ Booked nested slot', [
                'slot_id' => $slotId,
                'service' => $serviceType,
                'duration' => $serviceDuration
            ]);

            return $nestedBooking;

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to book nested slot', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build a visual timeline for the booking
     */
    public function buildTimeline(Carbon $startTime, array $config, array $nestedSlots): array
    {
        $timeline = [];
        $currentTime = $startTime->copy();

        // Phase 1: Active work
        $timeline[] = [
            'phase' => 'active_work',
            'description' => 'Aktive Arbeit (Auftragen/Vorbereitung)',
            'start' => $currentTime->format('H:i'),
            'end' => $currentTime->copy()->addMinutes($config['active_work'])->format('H:i'),
            'duration' => $config['active_work'],
            'staff_required' => true
        ];

        $currentTime->addMinutes($config['active_work']);

        // Phase 2: Break/Processing time
        $timeline[] = [
            'phase' => 'processing',
            'description' => 'Einwirkzeit (Mitarbeiter verfügbar für andere Kunden)',
            'start' => $currentTime->format('H:i'),
            'end' => $currentTime->copy()->addMinutes($config['break_duration'])->format('H:i'),
            'duration' => $config['break_duration'],
            'staff_required' => false,
            'nested_slots_available' => true
        ];

        $currentTime->addMinutes($config['break_duration']);

        // Phase 3: Finishing work
        $timeline[] = [
            'phase' => 'finishing',
            'description' => 'Abschlussarbeiten (Auswaschen/Styling)',
            'start' => $currentTime->format('H:i'),
            'end' => $currentTime->copy()->addMinutes($config['finish_work'])->format('H:i'),
            'duration' => $config['finish_work'],
            'staff_required' => true
        ];

        return $timeline;
    }

    /**
     * Create the main booking record
     */
    private function createMainBooking(array $bookingData, Carbon $startTime, array $config): array
    {
        // Create the appointment in the database
        $appointment = \App\Models\Appointment::create([
            'customer_id' => $bookingData['customer_id'],
            'service_id' => $bookingData['service_id'],
            'company_id' => \App\Models\Service::find($bookingData['service_id'])->company_id ?? null,
            'branch_id' => \App\Models\Service::find($bookingData['service_id'])->branch_id ?? null,
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes($config['main_duration']),
            'status' => 'confirmed',
            'source' => 'phone_call',
            'booking_type' => 'single', // Using 'single' as nested is not an allowed enum value
            'metadata' => json_encode([
                'is_nested_booking' => true,
                'has_nested_slots' => true,
                'phases' => [
                    'active_work' => $config['active_work'],
                    'break_duration' => $config['break_duration'],
                    'finish_work' => $config['finish_work']
                ]
            ])
        ]);

        return [
            'id' => $appointment->id,
            'appointment' => $appointment,
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes($config['main_duration']),
            'total_duration' => $config['main_duration'],
            'has_nested_slots' => true,
            'phases' => [
                'active_work' => $config['active_work'],
                'break_duration' => $config['break_duration'],
                'finish_work' => $config['finish_work']
            ]
        ];
    }

    /**
     * Calculate break periods based on service configuration
     */
    private function calculateBreakPeriods(Carbon $startTime, array $config): array
    {
        $breakStart = $startTime->copy()->addMinutes($config['break_start']);
        $breakEnd = $breakStart->copy()->addMinutes($config['break_duration']);

        return [[
            'start' => $breakStart,
            'end' => $breakEnd,
            'duration' => $config['break_duration']
        ]];
    }

    /**
     * Create nested slot records in database
     */
    private function createNestedSlots(int $parentBookingId, array $breakPeriods): array
    {
        $slots = [];

        foreach ($breakPeriods as $period) {
            $slot = NestedBookingSlot::create([
                'parent_booking_id' => $parentBookingId,
                'available_from' => $period['start'],
                'available_to' => $period['end'],
                'max_duration_minutes' => $period['duration'],
                'allowed_services' => array_keys(self::BREAK_COMPATIBLE_SERVICES),
                'is_available' => true
            ]);

            $slots[] = $slot->toArray();
        }

        return $slots;
    }

    /**
     * Split a slot if a shorter service is booked
     */
    private function splitSlot(NestedBookingSlot $slot, int $usedMinutes): void
    {
        $remainingStart = Carbon::parse($slot->available_from)->addMinutes($usedMinutes);
        $remainingEnd = Carbon::parse($slot->available_to);

        if ($remainingStart < $remainingEnd) {
            NestedBookingSlot::create([
                'parent_booking_id' => $slot->parent_booking_id,
                'available_from' => $remainingStart,
                'available_to' => $remainingEnd,
                'max_duration_minutes' => $remainingStart->diffInMinutes($remainingEnd),
                'allowed_services' => $slot->allowed_services,
                'is_available' => true
            ]);

            Log::info('Created remaining slot after split', [
                'original_slot' => $slot->id,
                'remaining_minutes' => $remainingStart->diffInMinutes($remainingEnd)
            ]);
        }
    }

    /**
     * Get compatible services for a slot based on duration
     */
    private function getCompatibleServices(NestedBookingSlot $slot): array
    {
        $slotDuration = Carbon::parse($slot->available_from)
            ->diffInMinutes(Carbon::parse($slot->available_to));

        $compatible = [];
        foreach (self::BREAK_COMPATIBLE_SERVICES as $service => $duration) {
            if ($duration <= $slotDuration) {
                $compatible[$service] = $duration;
            }
        }

        return $compatible;
    }

    /**
     * Check for conflicts with existing nested bookings
     */
    public function hasConflicts(Carbon $startTime, Carbon $endTime, ?int $excludeBookingId = null): bool
    {
        $query = NestedBookingSlot::where('is_available', false)
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('available_from', [$startTime, $endTime])
                  ->orWhereBetween('available_to', [$startTime, $endTime])
                  ->orWhere(function($q2) use ($startTime, $endTime) {
                      $q2->where('available_from', '<=', $startTime)
                         ->where('available_to', '>=', $endTime);
                  });
            });

        if ($excludeBookingId) {
            $query->where('parent_booking_id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }
}