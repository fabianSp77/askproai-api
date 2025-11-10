<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Calcom\BranchCalcomConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cal.com Atoms API Controller
 *
 * Provides configuration and callbacks for Cal.com Atoms React components
 */
class CalcomAtomsController extends Controller
{
    public function __construct(
        private BranchCalcomConfigService $configService
    ) {}

    /**
     * Get Cal.com configuration for current user
     */
    public function config(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Eager load relationships to prevent N+1 and null issues
            $user->load(['company.branches', 'branch']);

            return response()->json([
                'default_branch' => $this->configService->getUserDefaultConfig($user),
                'branches' => $this->configService->getUserBranches($user),
                'team_id' => config('calcom.team_id'),
            ]);
        } catch (\Exception $e) {
            logger()->error('Cal.com Atoms config error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'error' => 'Failed to load configuration',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Cal.com configuration for specific branch
     */
    public function branchConfig(Request $request, Branch $branch): JsonResponse
    {
        $user = $request->user();

        // Authorization: User must have access to this branch
        // Case 1: User has specific branch assigned (company_manager)
        if ($user->branch_id && $user->branch_id !== $branch->id) {
            abort(403, 'Access denied to this branch');
        }

        // Case 2: Branch must belong to user's company
        if ($user->company_id !== $branch->company_id) {
            abort(403, 'Access denied to this branch');
        }

        return response()->json(
            $this->configService->getBranchConfig($branch)
        );
    }

    /**
     * Handle booking success callback from Cal.com Atoms
     */
    public function bookingCreated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_uid' => 'required|string',
            'event_type_id' => 'required|integer',
            'branch_id' => 'required|exists:branches,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'attendee' => 'required|array',
        ]);

        try {
            // Find service by Cal.com event type ID
            $service = \App\Models\Service::where('calcom_event_type_id', $validated['event_type_id'])->first();

            if (!$service) {
                logger()->error('Service not found for event_type_id', ['event_type_id' => $validated['event_type_id']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found',
                ], 404);
            }

            // Get branch to determine company_id
            $branch = \App\Models\Branch::find($validated['branch_id']);

            // Create appointment
            $appointment = \App\Models\Appointment::create([
                'cal_booking_uid' => $validated['booking_uid'],
                'company_id' => $branch->company_id,
                'branch_id' => $validated['branch_id'],
                'service_id' => $service->id,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'status' => 'confirmed',
                'customer_name' => $validated['attendee']['name'] ?? 'Unknown',
                'customer_email' => $validated['attendee']['email'] ?? null,
                'customer_phone' => $validated['attendee']['phoneNumber'] ?? null,
            ]);

            logger()->info('Appointment created from Cal.com booking', [
                'appointment_id' => $appointment->id,
                'booking_uid' => $validated['booking_uid'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment created successfully',
                'appointment_id' => $appointment->id,
            ]);

        } catch (\Exception $e) {
            logger()->error('Failed to create appointment from Cal.com booking', [
                'error' => $e->getMessage(),
                'booking_uid' => $validated['booking_uid'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create appointment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
