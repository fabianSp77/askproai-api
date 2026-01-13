<?php

declare(strict_types=1);

namespace App\Http\Controllers\LoadTest;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mock Cal.com Controller for Load Testing
 *
 * Provides fast, predictable responses for stress tests
 * without hitting the real Cal.com API.
 *
 * Enable by setting LOAD_TEST_MOCK_MODE=true in .env
 */
class MockCalcomController extends Controller
{
    /**
     * Mock availability response
     * Simulates Cal.com /v2/slots/available endpoint
     */
    public function availability(Request $request): JsonResponse
    {
        // Simulate some processing delay (50-150ms)
        usleep(rand(50000, 150000));

        $startDate = Carbon::parse($request->input('startTime', now()));
        $endDate = Carbon::parse($request->input('endTime', now()->addDays(7)));

        // Generate mock slots
        $slots = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // Skip weekends
            if (!$currentDate->isWeekend()) {
                // Generate 8 slots per day (9:00-17:00)
                for ($hour = 9; $hour < 17; $hour++) {
                    $slotTime = $currentDate->copy()->setHour($hour)->setMinute(0)->setSecond(0);

                    // Randomly mark some slots as unavailable (30%)
                    if (rand(1, 100) > 30) {
                        $slots[] = [
                            'time' => $slotTime->toIso8601String(),
                            'available' => true,
                        ];
                    }
                }
            }
            $currentDate->addDay();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Mock booking creation
     * Simulates Cal.com /v2/bookings endpoint
     */
    public function createBooking(Request $request): JsonResponse
    {
        // Simulate booking processing (100-300ms)
        usleep(rand(100000, 300000));

        $bookingId = 'mock-booking-' . uniqid();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => rand(100000, 999999),
                'uid' => $bookingId,
                'title' => $request->input('title', 'Mock Appointment'),
                'startTime' => $request->input('start'),
                'endTime' => $request->input('end'),
                'status' => 'ACCEPTED',
                'attendees' => [
                    [
                        'email' => $request->input('attendee.email', 'test@example.com'),
                        'name' => $request->input('attendee.name', 'Test User'),
                    ],
                ],
            ],
        ], 201);
    }

    /**
     * Mock slot reservation
     */
    public function reserveSlot(Request $request): JsonResponse
    {
        usleep(rand(30000, 80000));

        return response()->json([
            'status' => 'success',
            'data' => [
                'reservationUid' => 'mock-reservation-' . uniqid(),
                'reservationUntil' => now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Mock event types
     */
    public function eventTypes(Request $request): JsonResponse
    {
        usleep(rand(20000, 50000));

        return response()->json([
            'status' => 'success',
            'data' => [
                [
                    'id' => 1,
                    'title' => 'Beratungstermin',
                    'slug' => 'beratungstermin',
                    'length' => 30,
                ],
                [
                    'id' => 2,
                    'title' => 'Servicetermin',
                    'slug' => 'servicetermin',
                    'length' => 60,
                ],
            ],
        ]);
    }

    /**
     * Health check for mock server
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'mode' => 'mock',
            'message' => 'Load test mock server active',
        ]);
    }
}
