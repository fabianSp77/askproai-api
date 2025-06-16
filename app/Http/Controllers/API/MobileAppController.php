<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use App\Models\Customer;
use App\Models\Staff;
use App\Services\AvailabilityService;
use App\Services\ConflictDetectionService;
use App\Services\NotificationService;
use App\Jobs\SendNotificationJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;

class MobileAppController extends Controller
{
    protected AvailabilityService $availabilityService;
    protected ConflictDetectionService $conflictService;
    protected NotificationService $notificationService;
    
    public function __construct(
        AvailabilityService $availabilityService,
        ConflictDetectionService $conflictService,
        NotificationService $notificationService
    ) {
        $this->availabilityService = $availabilityService;
        $this->conflictService = $conflictService;
        $this->notificationService = $notificationService;
    }
    
    /**
     * Get available event types
     * 
     * @OA\Get(
     *     path="/api/mobile/event-types",
     *     summary="Get available event types",
     *     tags={"Mobile API"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="Filter by company",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         description="Filter by branch",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/EventType"))
     *         )
     *     )
     * )
     */
    public function getEventTypes(Request $request): JsonResponse
    {
        // Rate limiting
        $key = 'event-types:' . ($request->user()->id ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }
        RateLimiter::hit($key);
        
        $query = CalcomEventType::query()
            ->with(['company', 'branch', 'assignedStaff' => function ($query) {
                $query->where('active', true);
            }])
            ->where('is_active', true);
            
        if ($request->has('company_id')) {
            // Validate company access
            if (!Gate::allows('view-company', $request->company_id)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            $query->where('company_id', $request->company_id);
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Add pagination
        $perPage = min($request->input('per_page', 20), 100);
        $eventTypes = $query->paginate($perPage);
        
        $data = $eventTypes->map(function ($eventType) {
            return [
                'id' => $eventType->id,
                'name' => $eventType->name,
                'description' => $eventType->description,
                'duration_minutes' => $eventType->duration_minutes,
                'price' => $eventType->price,
                'company' => [
                    'id' => $eventType->company->id,
                    'name' => $eventType->company->name,
                ],
                'branch' => $eventType->branch ? [
                    'id' => $eventType->branch->id,
                    'name' => $eventType->branch->name,
                    'address' => $eventType->branch->address,
                ] : null,
                'available_staff_count' => $eventType->assignedStaff()->where('active', true)->count(),
            ];
        });
        
        return response()->json([
            'data' => $eventTypes,
            'meta' => [
                'total' => $eventTypes->count(),
            ],
        ]);
    }
    
    /**
     * Check availability for an event type
     * 
     * @OA\Post(
     *     path="/api/mobile/availability/check",
     *     summary="Check availability for event type",
     *     tags={"Mobile API"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_type_id", "date"},
     *             @OA\Property(property="event_type_id", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="staff_id", type="string", description="Optional specific staff member")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Availability information",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="available", type="boolean"),
     *             @OA\Property(property="slots", type="array", @OA\Items(
     *                 @OA\Property(property="start", type="string"),
     *                 @OA\Property(property="end", type="string"),
     *                 @OA\Property(property="staff_id", type="string"),
     *                 @OA\Property(property="staff_name", type="string")
     *             ))
     *         )
     *     )
     * )
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        // Rate limiting for availability checks
        $key = 'availability:' . ($request->user()->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }
        RateLimiter::hit($key, 60);
        
        $validator = Validator::make($request->all(), [
            'event_type_id' => 'required|exists:calcom_event_types,id',
            'date' => 'required|date|after_or_equal:today|before:' . now()->addMonths(3)->format('Y-m-d'),
            'staff_id' => 'nullable|exists:staff,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $eventType = CalcomEventType::find($request->event_type_id);
        $date = Carbon::parse($request->date);
        
        if ($request->staff_id) {
            // Check specific staff member
            $availability = $this->availabilityService->checkRealTimeAvailability(
                $request->staff_id,
                $eventType->id,
                $date
            );
            
            $slots = collect($availability['slots'])->map(function ($slot) use ($request) {
                $staff = Staff::find($request->staff_id);
                return array_merge($slot, [
                    'staff_id' => $request->staff_id,
                    'staff_name' => $staff->name,
                ]);
            });
        } else {
            // Check all assigned staff
            $staffIds = $eventType->assignedStaff()->where('active', true)->pluck('id');
            $allAvailability = $this->availabilityService->checkMultipleStaffAvailability(
                $staffIds->toArray(),
                $eventType->id,
                $date
            );
            
            $slots = collect();
            foreach ($allAvailability as $staffAvailability) {
                if ($staffAvailability['available']) {
                    $staff = Staff::find($staffAvailability['staff_id']);
                    foreach ($staffAvailability['slots'] as $slot) {
                        $slots->push(array_merge($slot, [
                            'staff_id' => $staffAvailability['staff_id'],
                            'staff_name' => $staff->name,
                        ]));
                    }
                }
            }
            
            // Group by time slot and show available staff for each
            $slots = $slots->groupBy('start')->map(function ($group) {
                return [
                    'start' => $group->first()['start'],
                    'end' => $group->first()['end'],
                    'available_staff' => $group->map(function ($slot) {
                        return [
                            'id' => $slot['staff_id'],
                            'name' => $slot['staff_name'],
                        ];
                    })->values(),
                ];
            })->values();
        }
        
        return response()->json([
            'available' => $slots->isNotEmpty(),
            'slots' => $slots,
            'event_type' => [
                'id' => $eventType->id,
                'name' => $eventType->name,
                'duration_minutes' => $eventType->duration_minutes,
            ],
        ]);
    }
    
    /**
     * Create a booking
     * 
     * @OA\Post(
     *     path="/api/mobile/bookings",
     *     summary="Create a new booking",
     *     tags={"Mobile API"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"event_type_id", "staff_id", "customer_data", "start_time"},
     *             @OA\Property(property="event_type_id", type="integer"),
     *             @OA\Property(property="staff_id", type="string"),
     *             @OA\Property(property="start_time", type="string", format="date-time"),
     *             @OA\Property(property="customer_data", type="object",
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="phone", type="string")
     *             ),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="send_notifications", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="booking", ref="#/components/schemas/Appointment")
     *         )
     *     )
     * )
     */
    public function createBooking(Request $request): JsonResponse
    {
        // Strict rate limiting for bookings
        $key = 'booking:' . ($request->user()->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Too many booking attempts'], 429);
        }
        RateLimiter::hit($key, 300); // 5 bookings per 5 minutes
        
        $validator = Validator::make($request->all(), [
            'event_type_id' => 'required|exists:calcom_event_types,id',
            'staff_id' => 'required|exists:staff,id',
            'start_time' => 'required|date|after:' . now()->addHours(2)->format('Y-m-d H:i:s'),
            'customer_data.name' => 'required|string|max:255|regex:/^[\pL\s\-]+$/u',
            'customer_data.email' => 'required|email:rfc,dns',
            'customer_data.phone' => 'required|string|regex:/^[+]?[0-9\s\-\(\)]+$/',
            'notes' => 'nullable|string|max:500',
            'send_notifications' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $eventType = CalcomEventType::find($request->event_type_id);
        $startTime = Carbon::parse($request->start_time);
        $endTime = $startTime->copy()->addMinutes($eventType->duration_minutes);
        
        // Check for conflicts
        $conflicts = $this->conflictService->detectConflicts(
            $request->staff_id,
            $startTime,
            $endTime
        );
        
        if ($conflicts['has_conflicts']) {
            return response()->json([
                'success' => false,
                'message' => 'Der gewählte Termin ist nicht verfügbar',
                'conflicts' => $conflicts['conflicts'],
            ], 409);
        }
        
        // Find or create customer with validation
        $customerData = $request->input('customer_data');
        
        // Sanitize input
        $customerData['name'] = strip_tags($customerData['name']);
        $customerData['phone'] = preg_replace('/[^0-9+\-\s\(\)]/', '', $customerData['phone']);
        
        $customer = Customer::firstOrCreate(
            ['email' => $customerData['email']],
            [
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'company_id' => $eventType->company_id,
                'created_via' => 'mobile_app',
            ]
        );
        
        // Create appointment
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'staff_id' => $request->staff_id,
            'calcom_event_type_id' => $eventType->id,
            'branch_id' => $eventType->branch_id,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'status' => 'confirmed',
            'notes' => $request->notes,
            'metadata' => [
                'source' => 'mobile_app',
                'app_version' => $request->header('X-App-Version'),
                'device' => $request->header('X-Device-Type'),
            ],
        ]);
        
        // Send notifications via queue
        if ($request->input('send_notifications', true)) {
            SendNotificationJob::dispatch($appointment, 'confirmation', ['email', 'sms'])
                ->onQueue('notifications');
        }
        
        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'ends_at' => $appointment->ends_at->toIso8601String(),
                'status' => $appointment->status,
                'staff' => [
                    'id' => $appointment->staff->id,
                    'name' => $appointment->staff->name,
                ],
                'event_type' => [
                    'id' => $eventType->id,
                    'name' => $eventType->name,
                ],
                'branch' => $appointment->branch ? [
                    'id' => $appointment->branch->id,
                    'name' => $appointment->branch->name,
                    'address' => $appointment->branch->address,
                ] : null,
            ],
        ], 201);
    }
    
    /**
     * Get customer appointments
     * 
     * @OA\Get(
     *     path="/api/mobile/appointments",
     *     summary="Get customer appointments",
     *     tags={"Mobile API"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"upcoming", "past", "cancelled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of appointments"
     *     )
     * )
     */
    public function getAppointments(Request $request)
    {
        $customerId = Auth::user()->customer_id; // Assuming user is linked to customer
        
        $query = Appointment::where('customer_id', $customerId)
            ->with(['staff', 'branch', 'service']);
            
        if ($request->status === 'upcoming') {
            $query->where('starts_at', '>=', now())
                  ->whereIn('status', ['confirmed', 'pending']);
        } elseif ($request->status === 'past') {
            $query->where('starts_at', '<', now())
                  ->orWhere('status', 'completed');
        } elseif ($request->status === 'cancelled') {
            $query->whereIn('status', ['cancelled', 'no_show']);
        }
        
        $appointments = $query->orderBy('starts_at', 'desc')->get();
        
        return response()->json([
            'data' => $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'starts_at' => $appointment->starts_at->toIso8601String(),
                    'ends_at' => $appointment->ends_at->toIso8601String(),
                    'status' => $appointment->status,
                    'can_cancel' => $appointment->starts_at->gt(now()->addHours(24)),
                    'can_reschedule' => $appointment->starts_at->gt(now()->addHours(48)),
                    'staff' => [
                        'id' => $appointment->staff->id,
                        'name' => $appointment->staff->name,
                    ],
                    'branch' => $appointment->branch ? [
                        'id' => $appointment->branch->id,
                        'name' => $appointment->branch->name,
                        'address' => $appointment->branch->address,
                    ] : null,
                ];
            }),
        ]);
    }
    
    /**
     * Cancel appointment
     * 
     * @OA\Delete(
     *     path="/api/mobile/appointments/{id}",
     *     summary="Cancel an appointment",
     *     tags={"Mobile API"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Appointment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appointment cancelled successfully"
     *     )
     * )
     */
    public function cancelAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        
        // Verify ownership
        if ($appointment->customer_id !== Auth::user()->customer_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Check if cancellation is allowed (24h notice)
        if ($appointment->starts_at->lt(now()->addHours(24))) {
            return response()->json([
                'error' => 'Termine können nur bis 24 Stunden vorher storniert werden',
            ], 422);
        }
        
        $appointment->update([
            'status' => 'cancelled',
            'metadata' => array_merge($appointment->metadata ?? [], [
                'cancelled_at' => now()->toIso8601String(),
                'cancelled_reason' => $request->reason,
                'cancelled_via' => 'mobile_app',
            ]),
        ]);
        
        // TODO: Send cancellation notifications
        
        return response()->json([
            'success' => true,
            'message' => 'Termin wurde erfolgreich storniert',
        ]);
    }
    
    /**
     * Update push notification token
     * 
     * @OA\Post(
     *     path="/api/mobile/device/register",
     *     summary="Register device for push notifications",
     *     tags={"Mobile API"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "platform"},
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="platform", type="string", enum={"ios", "android"}),
     *             @OA\Property(property="device_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device registered successfully"
     *     )
     * )
     */
    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'platform' => 'required|in:ios,android',
            'device_id' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $customer = Customer::find(Auth::user()->customer_id);
        
        $customer->update([
            'push_token' => $request->token,
            'device_platform' => $request->platform,
            'device_id' => $request->device_id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Device registered for push notifications',
        ]);
    }
}