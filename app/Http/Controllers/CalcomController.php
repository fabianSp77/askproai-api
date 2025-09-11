<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\CalcomService;

class CalcomController extends Controller
{
    protected CalcomService $calcomService;

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    /**
     * Create a new Cal.com booking
     */
    public function createBooking(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'eventTypeId' => 'required|integer',
                'start' => 'required|string|date_format:Y-m-d\TH:i:s\Z',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
            ]);

            Log::info('[CalcomController] Creating booking for: ' . $validated['email']);

            // Delegate to service
            $booking = $this->calcomService->createBookingFromCall($validated);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => $booking
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CalcomController] Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CalcomController] Booking creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create booking',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get event type details
     */
    public function getEventType(Request $request, int $eventTypeId): JsonResponse
    {
        try {
            Log::info("[CalcomController] Fetching event type: {$eventTypeId}");

            $eventType = $this->calcomService->getEventType($eventTypeId);

            return response()->json([
                'success' => true,
                'data' => $eventType
            ]);

        } catch (\Exception $e) {
            Log::error('[CalcomController] Failed to fetch event type', [
                'event_type_id' => $eventTypeId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch event type',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check availability endpoint (from CalComController)
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'dateFrom' => 'required',
                'dateTo' => 'required',
                'eventTypeId' => 'required',
                'userId' => 'required'
            ]);

            $response = $this->calcomService->checkAvailability($validated);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('[CalcomController] Failed to check availability', [
                'message' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to check availability',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'Cal.com Integration',
            'status' => 'operational',
            'timestamp' => now()->toISOString()
        ]);
    }
}
