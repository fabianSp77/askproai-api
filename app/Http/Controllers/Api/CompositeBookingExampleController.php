<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Staff;
use App\Services\Booking\CompositeBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CompositeBookingExampleController extends Controller
{
    public function __construct(
        private CompositeBookingService $compositeService
    ) {}

    /**
     * Example: Check availability for hairdresser composite service
     * GET /api/v2/composite-booking/availability
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        try {
            $service = Service::findOrFail($validated['service_id']);

            if (!$service->isComposite()) {
                return response()->json([
                    'error' => 'Service is not composite type'
                ], 400);
            }

            // Find available slots
            $slots = $this->compositeService->findCompositeSlots($service, [
                'start' => Carbon::parse($validated['date'])->startOfDay(),
                'end' => Carbon::parse($validated['date'])->endOfDay(),
                'staff_id' => $validated['staff_id'] ?? null
            ]);

            // Format response for hairdresser service
            $formattedSlots = $slots->map(function($slot) {
                return [
                    'slot_id' => $slot['composite_slot_id'],
                    'start_time' => Carbon::parse($slot['starts_at'])->format('H:i'),
                    'end_time' => Carbon::parse($slot['ends_at'])->format('H:i'),
                    'total_duration_minutes' => $slot['total_duration'],
                    'timeline' => $this->formatTimeline($slot['segments'], $slot['pause']),
                    'segments' => collect($slot['segments'])->map(function($segment) {
                        return [
                            'name' => $segment['name'],
                            'staff' => $segment['staff_name'],
                            'start' => Carbon::parse($segment['starts_at'])->format('H:i'),
                            'end' => Carbon::parse($segment['ends_at'])->format('H:i'),
                            'duration' => $segment['duration'] . ' min'
                        ];
                    }),
                    'pauses' => [
                        [
                            'after_segment' => 'A',
                            'duration' => $slot['segments'][0]['gap_after'] ?? 20 . ' min',
                            'purpose' => 'Einwirkzeit Produkt'
                        ],
                        [
                            'after_segment' => 'B',
                            'duration' => $slot['segments'][1]['gap_after'] ?? 20 . ' min',
                            'purpose' => 'Farbe einwirken lassen'
                        ]
                    ]
                ];
            });

            return response()->json([
                'service' => [
                    'name' => $service->name,
                    'total_duration' => $service->duration_minutes . ' minutes',
                    'segments_count' => count($service->getSegments()),
                    'requires_same_staff' => true
                ],
                'date' => $validated['date'],
                'available_slots' => $formattedSlots,
                'total_slots_found' => $formattedSlots->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Composite availability check failed', [
                'error' => $e->getMessage(),
                'service_id' => $validated['service_id']
            ]);

            return response()->json([
                'error' => 'Failed to check availability',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example: Book a hairdresser composite appointment
     * POST /api/v2/composite-booking/book
     */
    public function bookAppointment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'slot_id' => 'required|string',
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email',
            'customer.phone' => 'required|string',
            'segments' => 'required|array|min:2',
            'segments.*.key' => 'required|string',
            'segments.*.staff_id' => 'required|exists:staff,id',
            'segments.*.starts_at' => 'required|date_format:Y-m-d H:i:s',
            'segments.*.ends_at' => 'required|date_format:Y-m-d H:i:s',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $service = Service::findOrFail($validated['service_id']);

            // Create or find customer
            $customer = Customer::firstOrCreate(
                ['email' => $validated['customer']['email']],
                [
                    'name' => $validated['customer']['name'],
                    'phone' => $validated['customer']['phone'],
                    'company_id' => $service->company_id
                ]
            );

            // Prepare booking data for hairdresser service
            $bookingData = [
                'service_id' => $service->id,
                'company_id' => $service->company_id,
                'branch_id' => $service->branch_id,
                'customer_id' => $customer->id,
                'customer' => [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone
                ],
                'segments' => $validated['segments'],
                'source' => 'api',
                'notes' => $validated['notes'] ?? null,
                'timeZone' => 'Europe/Berlin'
            ];

            // Book the composite appointment
            $appointment = $this->compositeService->bookComposite($bookingData);

            // Format response with timeline visualization
            return response()->json([
                'success' => true,
                'appointment' => [
                    'id' => $appointment->id,
                    'reference' => $appointment->composite_group_uid,
                    'service' => $service->name,
                    'customer' => $customer->name,
                    'total_duration' => Carbon::parse($appointment->starts_at)
                        ->diffInMinutes(Carbon::parse($appointment->ends_at)) . ' minutes',
                    'timeline' => $this->generateBookingTimeline($appointment),
                    'segments' => collect($appointment->getSegments())->map(function($segment) {
                        $staff = Staff::find($segment['staff_id']);
                        return [
                            'segment' => $segment['key'],
                            'staff' => $staff->name,
                            'start' => Carbon::parse($segment['starts_at'])->format('H:i'),
                            'end' => Carbon::parse($segment['ends_at'])->format('H:i'),
                            'status' => $segment['status']
                        ];
                    }),
                    'confirmation' => [
                        'message' => 'Ihr Termin wurde erfolgreich gebucht',
                        'start' => Carbon::parse($appointment->starts_at)->format('d.m.Y H:i'),
                        'end' => Carbon::parse($appointment->ends_at)->format('H:i'),
                        'location' => $service->branch->name ?? 'Hauptfiliale'
                    ]
                ],
                'notifications' => [
                    'email_sent' => true,
                    'sms_sent' => false,
                    'calendar_invite' => true
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Composite booking failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Booking failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example: Cancel hairdresser composite appointment
     * DELETE /api/v2/composite-booking/{appointment}/cancel
     */
    public function cancelAppointment($appointmentId): JsonResponse
    {
        try {
            $appointment = \App\Models\Appointment::findOrFail($appointmentId);

            if (!$appointment->isComposite()) {
                return response()->json([
                    'error' => 'Not a composite appointment'
                ], 400);
            }

            $result = $this->compositeService->cancelComposite($appointment);

            return response()->json([
                'success' => $result,
                'message' => $result
                    ? 'Alle Segmente wurden erfolgreich storniert'
                    : 'Einige Segmente konnten nicht storniert werden',
                'appointment_id' => $appointment->id,
                'cancelled_segments' => collect($appointment->getSegments())->pluck('key'),
                'refund' => [
                    'eligible' => true,
                    'amount' => $appointment->price,
                    'processing_time' => '3-5 business days'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cancellation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example: Reschedule composite appointment
     * PUT /api/v2/composite-booking/{appointment}/reschedule
     */
    public function rescheduleAppointment(Request $request, $appointmentId): JsonResponse
    {
        $validated = $request->validate([
            'new_date' => 'required|date|after:now',
            'new_time' => 'required|date_format:H:i',
            'segments' => 'required|array|min:2',
            'reason' => 'nullable|string|max:255'
        ]);

        try {
            $appointment = \App\Models\Appointment::findOrFail($appointmentId);

            if (!$appointment->isComposite()) {
                return response()->json([
                    'error' => 'Not a composite appointment'
                ], 400);
            }

            // Prepare new booking data
            $newDate = Carbon::parse($validated['new_date'] . ' ' . $validated['new_time']);
            $newData = [
                'service_id' => $appointment->service_id,
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'customer_id' => $appointment->customer_id,
                'customer' => [
                    'name' => $appointment->customer->name,
                    'email' => $appointment->customer->email,
                    'phone' => $appointment->customer->phone
                ],
                'segments' => $validated['segments'],
                'source' => 'reschedule',
                'notes' => $validated['reason'] ?? 'Rescheduled by customer'
            ];

            $newAppointment = $this->compositeService->rescheduleComposite($appointment, $newData);

            return response()->json([
                'success' => true,
                'message' => 'Termin erfolgreich verschoben',
                'old_appointment' => [
                    'id' => $appointment->id,
                    'start' => $appointment->starts_at->format('d.m.Y H:i'),
                    'status' => 'cancelled'
                ],
                'new_appointment' => [
                    'id' => $newAppointment->id,
                    'reference' => $newAppointment->composite_group_uid,
                    'start' => $newAppointment->starts_at->format('d.m.Y H:i'),
                    'end' => $newAppointment->ends_at->format('H:i'),
                    'status' => 'confirmed'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Reschedule failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format timeline for display
     */
    private function formatTimeline(array $segments, array $pause): string
    {
        $timeline = "";
        foreach ($segments as $index => $segment) {
            $start = Carbon::parse($segment['starts_at'])->format('H:i');
            $end = Carbon::parse($segment['ends_at'])->format('H:i');
            $timeline .= "{$start}-{$end}: {$segment['name']} ({$segment['staff_name']})\n";

            if ($index < count($segments) - 1 && isset($pause['duration']) && $pause['duration'] > 0) {
                $pauseStart = $end;
                $pauseEnd = Carbon::parse($segments[$index + 1]['starts_at'])->format('H:i');
                $timeline .= "{$pauseStart}-{$pauseEnd}: [PAUSE {$pause['duration']} min]\n";
            }
        }
        return trim($timeline);
    }

    /**
     * Generate visual booking timeline
     */
    private function generateBookingTimeline($appointment): array
    {
        $timeline = [];
        $segments = $appointment->getSegments();

        foreach ($segments as $index => $segment) {
            $timeline[] = [
                'time' => Carbon::parse($segment['starts_at'])->format('H:i'),
                'type' => 'work',
                'title' => "Segment {$segment['key']}",
                'duration' => Carbon::parse($segment['starts_at'])
                    ->diffInMinutes(Carbon::parse($segment['ends_at'])) . ' min',
                'staff' => Staff::find($segment['staff_id'])->name ?? 'N/A'
            ];

            // Add pause after segment if not last
            if ($index < count($segments) - 1) {
                $pauseDuration = Carbon::parse($segment['ends_at'])
                    ->diffInMinutes(Carbon::parse($segments[$index + 1]['starts_at']));

                if ($pauseDuration > 0) {
                    $timeline[] = [
                        'time' => Carbon::parse($segment['ends_at'])->format('H:i'),
                        'type' => 'pause',
                        'title' => 'Einwirkzeit',
                        'duration' => $pauseDuration . ' min',
                        'staff' => null
                    ];
                }
            }
        }

        return $timeline;
    }

    /**
     * Example: Get composite appointment details with visual timeline
     * GET /api/v2/composite-booking/{appointment}
     */
    public function getAppointmentDetails($appointmentId): JsonResponse
    {
        try {
            $appointment = \App\Models\Appointment::with(['customer', 'service', 'staff'])
                ->findOrFail($appointmentId);

            if (!$appointment->isComposite()) {
                return response()->json([
                    'error' => 'Not a composite appointment'
                ], 400);
            }

            $segments = collect($appointment->getSegments());
            $totalWorkTime = 0;
            $totalPauseTime = 0;

            // Calculate work and pause times
            foreach ($segments as $index => $segment) {
                $segmentDuration = Carbon::parse($segment['starts_at'])
                    ->diffInMinutes(Carbon::parse($segment['ends_at']));
                $totalWorkTime += $segmentDuration;

                if ($index < $segments->count() - 1) {
                    $pauseDuration = Carbon::parse($segment['ends_at'])
                        ->diffInMinutes(Carbon::parse($segments[$index + 1]['starts_at']));
                    $totalPauseTime += $pauseDuration;
                }
            }

            return response()->json([
                'appointment' => [
                    'id' => $appointment->id,
                    'reference' => $appointment->composite_group_uid,
                    'status' => $appointment->status,
                    'service' => [
                        'name' => $appointment->service->name,
                        'category' => $appointment->service->category,
                        'price' => $appointment->price
                    ],
                    'customer' => [
                        'name' => $appointment->customer->name,
                        'email' => $appointment->customer->email,
                        'phone' => $appointment->customer->phone
                    ],
                    'schedule' => [
                        'date' => Carbon::parse($appointment->starts_at)->format('d.m.Y'),
                        'start_time' => Carbon::parse($appointment->starts_at)->format('H:i'),
                        'end_time' => Carbon::parse($appointment->ends_at)->format('H:i'),
                        'total_duration' => $totalWorkTime + $totalPauseTime . ' minutes',
                        'work_time' => $totalWorkTime . ' minutes',
                        'pause_time' => $totalPauseTime . ' minutes'
                    ],
                    'timeline' => $this->generateBookingTimeline($appointment),
                    'segments' => $segments->map(function($segment) {
                        return [
                            'key' => $segment['key'],
                            'booking_id' => $segment['booking_id'] ?? null,
                            'staff' => Staff::find($segment['staff_id'])->name ?? 'N/A',
                            'start' => Carbon::parse($segment['starts_at'])->format('H:i'),
                            'end' => Carbon::parse($segment['ends_at'])->format('H:i'),
                            'duration' => Carbon::parse($segment['starts_at'])
                                ->diffInMinutes(Carbon::parse($segment['ends_at'])) . ' min',
                            'status' => $segment['status'] ?? 'booked'
                        ];
                    }),
                    'created_at' => $appointment->created_at->format('d.m.Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get appointment details',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}