<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Exceptions\AppointmentCancellationException;
use App\Exceptions\AppointmentRescheduleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\CancelAppointmentRequest;
use App\Http\Requests\CustomerPortal\RescheduleAppointmentRequest;
use App\Http\Resources\CustomerPortal\AppointmentResource;
use App\Models\Appointment;
use App\Services\CustomerPortal\AppointmentCancellationService;
use App\Services\CustomerPortal\AppointmentRescheduleService;
use App\Services\ProcessingTimeAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Customer Portal Appointment Controller
 *
 * AUTHORIZATION:
 * - All routes require Sanctum authentication (auth:sanctum middleware)
 * - Policy checks ensure user owns the appointment
 * - Multi-tenant isolation via CompanyScope
 *
 * ENDPOINTS:
 * - GET    /appointments           - List user's appointments
 * - GET    /appointments/{id}      - Show single appointment
 * - GET    /appointments/{id}/alternatives - Get alternative time slots
 * - PUT    /appointments/{id}/reschedule   - Reschedule appointment
 * - DELETE /appointments/{id}      - Cancel appointment
 *
 * OPTIMISTIC LOCKING:
 * - All mutations use version field
 * - Returns 409 Conflict if version mismatch
 * - Client must refresh and retry
 */
class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentRescheduleService $rescheduleService,
        private AppointmentCancellationService $cancellationService,
        private ProcessingTimeAvailabilityService $availabilityService,
    ) {}

    /**
     * List all appointments for authenticated user
     *
     * FILTERS:
     * - status: upcoming, past, cancelled
     * - from_date: ISO8601 date
     * - to_date: ISO8601 date
     *
     * SORTING:
     * - Default: start_time descending
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->customer_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'User is not associated with a customer account.',
                ], 422);
            }

            // Build query
            $query = Appointment::where('customer_id', $user->customer_id)
                ->with(['service', 'staff', 'branch', 'company']);

            // Apply status filter
            $status = $request->input('status', 'upcoming');
            switch ($status) {
                case 'upcoming':
                    $query->where('start_time', '>=', now())
                          ->where('status', '!=', 'cancelled');
                    break;
                case 'past':
                    $query->where('start_time', '<', now())
                          ->where('status', '!=', 'cancelled');
                    break;
                case 'cancelled':
                    $query->where('status', 'cancelled');
                    break;
            }

            // Apply date range filter
            if ($request->has('from_date')) {
                $query->where('start_time', '>=', Carbon::parse($request->input('from_date')));
            }

            if ($request->has('to_date')) {
                $query->where('start_time', '<=', Carbon::parse($request->input('to_date')));
            }

            // Sort
            $sortDirection = $status === 'upcoming' ? 'asc' : 'desc';
            $appointments = $query->orderBy('start_time', $sortDirection)->get();

            return response()->json([
                'success' => true,
                'data' => AppointmentResource::collection($appointments),
                'meta' => [
                    'total' => $appointments->count(),
                    'status' => $status,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to list appointments', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to retrieve appointments. Please try again.',
            ], 500);
        }
    }

    /**
     * Show single appointment
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $appointment = Appointment::with(['service', 'staff', 'branch', 'company', 'phases'])
                ->findOrFail($id);

            // Authorization check (using Customer Portal policy)
            $policy = new \App\Policies\CustomerPortal\AppointmentPolicy();
            if (!$policy->view($request->user(), $appointment)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You are not authorized to view this appointment.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => new AppointmentResource($appointment),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Appointment not found.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve appointment', [
                'appointment_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to retrieve appointment. Please try again.',
            ], 500);
        }
    }

    /**
     * Get alternative time slots for rescheduling
     *
     * LOGIC:
     * - Same day alternatives (preferred)
     * - Next 7 days slots
     * - Same staff (if available)
     * - Same service duration
     */
    public function alternatives(Request $request, int $id): JsonResponse
    {
        try {
            $appointment = Appointment::with(['service', 'staff'])->findOrFail($id);

            // Authorization check (using Customer Portal policy)
            $policy = new \App\Policies\CustomerPortal\AppointmentPolicy();
            if (!$policy->view($request->user(), $appointment)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You are not authorized to view this appointment.',
                ], 403);
            }

            // Get alternatives from availability service
            $alternatives = $this->availabilityService->findAlternativeSlots(
                serviceId: $appointment->service_id,
                staffId: $appointment->staff_id,
                durationMinutes: $appointment->duration_minutes,
                originalStartTime: $appointment->start_time,
                daysToSearch: 7
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'original_appointment' => [
                        'id' => $appointment->id,
                        'start_time' => $appointment->start_time->toIso8601String(),
                        'service' => $appointment->service->name,
                        'staff' => $appointment->staff->name,
                    ],
                    'alternatives' => $alternatives,
                    'search_parameters' => [
                        'days_searched' => 7,
                        'service_duration' => $appointment->duration_minutes,
                        'same_staff_only' => true,
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Appointment not found.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to find alternatives', [
                'appointment_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to find alternative slots. Please try again.',
            ], 500);
        }
    }

    /**
     * Reschedule appointment to new time
     */
    public function reschedule(RescheduleAppointmentRequest $request, int $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);

            // Authorization check (using Customer Portal policy)
            $policy = new \App\Policies\CustomerPortal\AppointmentPolicy();
            if (!$policy->reschedule($request->user(), $appointment)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You are not authorized to reschedule this appointment.',
                ], 403);
            }

            // Parse new start time
            $newStartTime = Carbon::parse($request->input('new_start_time'));

            // Reschedule via service
            $result = $this->rescheduleService->reschedule(
                appointment: $appointment,
                newStartTime: $newStartTime,
                user: $request->user(),
                reason: $request->input('reason')
            );

            Log::info('Appointment rescheduled successfully', [
                'appointment_id' => $appointment->id,
                'user_id' => $request->user()->id,
                'old_start_time' => $result->oldStartTime->toIso8601String(),
                'new_start_time' => $result->newStartTime->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment rescheduled successfully.',
                'data' => new AppointmentResource($result->appointment->fresh(['service', 'staff', 'branch'])),
            ], 200);

        } catch (AppointmentRescheduleException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $this->getRescheduleErrorCode($e),
            ], $e->getCode());

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Appointment not found.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to reschedule appointment', [
                'appointment_id' => $id,
                'user_id' => $request->user()?->id,
                'new_start_time' => $request->input('new_start_time'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to reschedule appointment. Please try again.',
            ], 500);
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel(CancelAppointmentRequest $request, int $id): JsonResponse
    {
        try {
            $appointment = Appointment::findOrFail($id);

            // Authorization check (using Customer Portal policy)
            $policy = new \App\Policies\CustomerPortal\AppointmentPolicy();
            if (!$policy->cancel($request->user(), $appointment)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You are not authorized to cancel this appointment.',
                ], 403);
            }

            // Cancel via service
            $result = $this->cancellationService->cancel(
                appointment: $appointment,
                user: $request->user(),
                reason: $request->input('reason'),
                cancellationType: 'customer_requested'
            );

            Log::info('Appointment cancelled successfully', [
                'appointment_id' => $appointment->id,
                'user_id' => $request->user()->id,
                'reason' => $request->input('reason'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment cancelled successfully.',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'cancelled_at' => now()->toIso8601String(),
                    'cancellation_confirmed' => true,
                ],
            ], 200);

        } catch (AppointmentCancellationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $this->getCancellationErrorCode($e),
            ], $e->getCode());

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Appointment not found.',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to cancel appointment', [
                'appointment_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to cancel appointment. Please try again.',
            ], 500);
        }
    }

    /**
     * Get user-friendly error code for reschedule errors
     */
    private function getRescheduleErrorCode(\Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'modified by another user')) {
            return 'OPTIMISTIC_LOCK_CONFLICT';
        }

        if (str_contains($message, 'no longer available')) {
            return 'SLOT_NOT_AVAILABLE';
        }

        if (str_contains($message, 'hours in advance')) {
            return 'MINIMUM_NOTICE_VIOLATION';
        }

        if (str_contains($message, 'business hours')) {
            return 'OUTSIDE_BUSINESS_HOURS';
        }

        return 'RESCHEDULE_ERROR';
    }

    /**
     * Get user-friendly error code for cancellation errors
     */
    private function getCancellationErrorCode(\Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'modified by another user')) {
            return 'OPTIMISTIC_LOCK_CONFLICT';
        }

        if (str_contains($message, 'past appointment')) {
            return 'PAST_APPOINTMENT';
        }

        if (str_contains($message, 'already cancelled')) {
            return 'ALREADY_CANCELLED';
        }

        if (str_contains($message, 'hours in advance')) {
            return 'MINIMUM_NOTICE_VIOLATION';
        }

        return 'CANCELLATION_ERROR';
    }
}
