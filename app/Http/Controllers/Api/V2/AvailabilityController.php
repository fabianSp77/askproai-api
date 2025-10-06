<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Staff;
use App\Services\Booking\CompositeBookingService;
use App\Services\CalcomV2Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    private CalcomV2Client $calcom;
    private CompositeBookingService $compositeService;

    public function __construct(CalcomV2Client $calcom, CompositeBookingService $compositeService)
    {
        $this->calcom = $calcom;
        $this->compositeService = $compositeService;
    }

    /**
     * Get simple availability slots
     */
    public function simple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'staff_id' => 'nullable|exists:staff,id',
            'timeZone' => 'nullable|string|timezone'
        ]);

        $service = Service::with('staff')->findOrFail($validated['service_id']);

        if ($service->isComposite()) {
            return response()->json([
                'error' => 'Service is composite. Use /availability/composite endpoint'
            ], 400);
        }

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();
        $timezone = $validated['timeZone'] ?? 'Europe/Berlin';

        // Get capable staff
        $staff = $this->getCapableStaff($service, $validated['branch_id'], $validated['staff_id'] ?? null);

        if ($staff->isEmpty()) {
            return response()->json([
                'data' => [
                    'slots' => [],
                    'message' => 'No available staff for this service'
                ]
            ]);
        }

        // Collect slots from all staff
        $allSlots = collect();

        foreach ($staff as $member) {
            $eventMapping = $this->getEventMapping($service->id, null, $member->id);

            if (!$eventMapping) {
                continue;
            }

            $response = $this->calcom->getAvailableSlots(
                $eventMapping->event_type_id,
                $start,
                $end,
                $timezone
            );

            if ($response->successful()) {
                $slots = collect($response->json('data.slots') ?? [])
                    ->map(function($slot) use ($member, $service) {
                        return [
                            'id' => uniqid('slot_'),
                            'staff_id' => $member->id,
                            'staff_name' => $member->name,
                            'start' => $slot['start'],
                            'end' => $slot['end'],
                            'duration' => $service->duration_minutes,
                            'service_id' => $service->id,
                            'service_name' => $service->name
                        ];
                    });

                $allSlots = $allSlots->merge($slots);
            }
        }

        // Rank by best fit (weight, availability)
        $rankedSlots = $allSlots->sortBy([
            ['start', 'asc'],
            fn($a, $b) => $this->rankStaff($a['staff_id']) <=> $this->rankStaff($b['staff_id'])
        ])->values();

        return response()->json([
            'data' => [
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration' => $service->duration_minutes
                ],
                'slots' => $rankedSlots->take(50), // Limit to 50 slots
                'total' => $rankedSlots->count()
            ]
        ]);
    }

    /**
     * Get composite availability slots
     */
    public function composite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'branch_id' => 'required|exists:branches,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'preferred_staff_id' => 'nullable|exists:staff,id',
            'timeZone' => 'nullable|string|timezone'
        ]);

        $service = Service::findOrFail($validated['service_id']);

        if (!$service->isComposite()) {
            return response()->json([
                'error' => 'Service is not composite. Use /availability/simple endpoint'
            ], 400);
        }

        try {
            $slots = $this->compositeService->findCompositeSlots($service, [
                'start' => $validated['start_date'],
                'end' => $validated['end_date'],
                'branch_id' => $validated['branch_id'],
                'preferred_staff_id' => $validated['preferred_staff_id'] ?? null,
                'timeZone' => $validated['timeZone'] ?? 'Europe/Berlin'
            ]);

            return response()->json([
                'data' => [
                    'service' => [
                        'id' => $service->id,
                        'name' => $service->name,
                        'is_composite' => true,
                        'segments' => $service->segments
                    ],
                    'slots' => $slots->take(30), // Limit composite slots
                    'total' => $slots->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Composite availability error', [
                'service_id' => $validated['service_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get composite availability',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get capable staff for service
     */
    private function getCapableStaff(Service $service, string $branchId, ?int $staffId = null)
    {
        $query = $service->staff()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->wherePivot('can_book', true);

        if ($staffId) {
            $query->where('staff.id', $staffId);
        }

        return $query->orderByPivot('weight', 'desc')->get();
    }

    /**
     * Get event mapping
     */
    private function getEventMapping($serviceId, $segmentKey, $staffId)
    {
        return \App\Models\CalcomEventMap::where('service_id', $serviceId)
            ->where('staff_id', $staffId)
            ->when($segmentKey, fn($q) => $q->where('segment_key', $segmentKey))
            ->where('sync_status', 'synced')
            ->first();
    }

    /**
     * Rank staff for slot priority
     */
    private function rankStaff($staffId): int
    {
        static $rankings = [];

        if (!isset($rankings[$staffId])) {
            $staff = Staff::find($staffId);
            // Higher weight = better rank (lower number)
            $rankings[$staffId] = $staff ? (100 - ($staff->pivot->weight ?? 50)) : 99;
        }

        return $rankings[$staffId];
    }
}