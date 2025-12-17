<?php

namespace App\Http\Controllers\Api\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\RescheduleAppointmentRequest;
use App\Http\Requests\CustomerPortal\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Services\CustomerPortal\AppointmentRescheduleService;
use App\Services\CustomerPortal\AppointmentCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer Portal Appointment Controller
 *
 * API VERSIONING: /api/v1/customer-portal/*
 *
 * AUTHENTICATION: Laravel Sanctum (token-based)
 *
 * AUTHORIZATION: Multi-layer policy enforcement
 * - Company isolation (CompanyScope middleware)
 * - Branch isolation (for company_manager role)
 * - Permission checks (AppointmentPolicy)
 *
 * RATE LIMITING: 60 requests/minute per user
 *
 * RESPONSE FORMAT:
 * Success: { success: true, data: {...}, message: "..." }
 * Error: { success: false, error: "...", code: "..." }
 */
class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentRescheduleService $rescheduleService,
        private AppointmentCancellationService $cancellationService,
    ) {
        $this->middleware(['auth:sanctum', 'company.scope', 'pilot.company']);
    }

    /**
     * List user's appointments
     *
     * GET /api/v1/customer-portal/appointments
     *
     * Query params:
     * - status: upcoming|past|cancelled
     * - from: YYYY-MM-DD
     * - to: YYYY-MM-DD
     * - per_page: 10|25|50
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Build query based on user role
        $query = Appointment::query()
            ->with(['service', 'staff', 'appointmentPhases'])
            ->where('company_id', $user->company_id);

        // Role-based filtering
        if ($user->hasRole('company_staff')) {
            // Staff can only see their own appointments
            $query->where('staff_id', $user->staff_id);
        } elseif ($user->hasRole('company_manager')) {
            // Manager can see appointments for their branch
            if ($user->staff && $user->staff->branch_id) {
                $query->whereHas('staff', function ($q) use ($user) {
                    $q->where('branch_id', $user->staff->branch_id);
                });
            }
        }
        // Owners and admins see all company appointments (no additional filter)

        // Status filter
        $status = $request->get('status', 'upcoming');
        match ($status) {
            'upcoming' => $query->where('start_time', '>=', now())
                               ->where('status', '!=', 'cancelled')
                               ->orderBy('start_time', 'asc'),
            'past' => $query->where('start_time', '<', now())
                           ->orderBy('start_time', 'desc'),
            'cancelled' => $query->where('status', 'cancelled')
                                ->orderBy('cancelled_at', 'desc'),
            default => $query->orderBy('start_time', 'desc'),
        };

        // Date range filter
        if ($request->has('from')) {
            $query->where('start_time', '>=', $request->get('from'));
        }
        if ($request->has('to')) {
            $query->where('start_time', '<=', $request->get('to'));
        }

        // Pagination
        $perPage = $request->get('per_page', 25);
        $appointments = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }

    /**
     * Get appointment details
     *
     * GET /api/v1/customer-portal/appointments/{id}
     */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        $appointment->load([
            'service',
            'staff',
            'appointmentPhases.service',
            'auditLogs' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'appointment' => $appointment,
                'can_reschedule' => $appointment->canBeRescheduled(),
                'can_cancel' => $appointment->canBeCancelled(),
                'reschedule_count' => $appointment->auditLogs()
                    ->where('action', 'rescheduled')
                    ->count(),
            ],
        ]);
    }

    /**
     * Reschedule appointment
     *
     * POST /api/v1/customer-portal/appointments/{id}/reschedule
     *
     * Body: {
     *   "new_start_time": "2025-11-25T10:00:00+01:00",
     *   "reason": "Conflict with another meeting"
     * }
     */
    public function reschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment
    ): JsonResponse {
        $this->authorize('reschedule', $appointment);

        try {
            $result = $this->rescheduleService->reschedule(
                appointment: $appointment,
                newStartTime: \Carbon\Carbon::parse($request->input('new_start_time')),
                user: $request->user(),
                reason: $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);

        } catch (\App\Exceptions\AppointmentRescheduleException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'RESCHEDULE_FAILED',
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Cancel appointment
     *
     * POST /api/v1/customer-portal/appointments/{id}/cancel
     *
     * Body: {
     *   "reason": "No longer needed"
     * }
     */
    public function cancel(
        CancelAppointmentRequest $request,
        Appointment $appointment
    ): JsonResponse {
        $this->authorize('cancel', $appointment);

        try {
            $result = $this->cancellationService->cancel(
                appointment: $appointment,
                user: $request->user(),
                reason: $request->input('reason'),
                cancellationType: 'customer_requested'
            );

            return response()->json([
                'success' => true,
                'data' => $result->toArray(),
            ]);

        } catch (\App\Exceptions\AppointmentCancellationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'CANCELLATION_FAILED',
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get available slots for rescheduling
     *
     * GET /api/v1/customer-portal/appointments/{id}/available-slots
     *
     * Query params:
     * - date: YYYY-MM-DD
     * - days: 7 (default)
     */
    public function availableSlots(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('reschedule', $appointment);

        $date = $request->get('date', now()->format('Y-m-d'));
        $days = $request->get('days', 7);

        // TODO: Implement availability service call
        // For now, return placeholder
        return response()->json([
            'success' => true,
            'data' => [
                'date_range' => [
                    'from' => $date,
                    'to' => \Carbon\Carbon::parse($date)->addDays($days)->format('Y-m-d'),
                ],
                'slots' => [], // TODO: Implement
            ],
        ]);
    }
}
