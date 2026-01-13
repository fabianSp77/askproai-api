<?php

/**
 * Load Test Mock Routes
 *
 * These routes provide mock endpoints for load testing
 * without hitting external APIs (Cal.com, Retell).
 *
 * Only active when LOAD_TEST_MOCK_MODE=true in .env
 *
 * Usage:
 *   1. Set LOAD_TEST_MOCK_MODE=true in .env
 *   2. Run: php artisan config:clear
 *   3. Point tests to /api/mock/calcom/* endpoints
 */

use App\Http\Controllers\LoadTest\MockCalcomController;
use Illuminate\Support\Facades\Route;

// Routes are only loaded when LOAD_TEST_MOCK_MODE=true (checked in RouteServiceProvider)
Route::prefix('api/mock')->group(function () {

        // Mock Cal.com endpoints
        Route::prefix('calcom')->group(function () {
            Route::get('health', [MockCalcomController::class, 'health']);
            Route::post('availability', [MockCalcomController::class, 'availability']);
            Route::post('bookings', [MockCalcomController::class, 'createBooking']);
            Route::post('slots/reservations', [MockCalcomController::class, 'reserveSlot']);
            Route::get('event-types', [MockCalcomController::class, 'eventTypes']);
        });

        // Mock Retell endpoints (simplified)
        Route::prefix('retell')->group(function () {
            Route::post('check-availability', function () {
                usleep(rand(100000, 200000)); // 100-200ms
                return response()->json([
                    'available_slots' => [
                        ['date' => now()->addDay()->format('Y-m-d'), 'time' => '10:00'],
                        ['date' => now()->addDay()->format('Y-m-d'), 'time' => '14:00'],
                        ['date' => now()->addDays(2)->format('Y-m-d'), 'time' => '11:00'],
                    ],
                ]);
            });

            Route::post('check-customer', function () {
                usleep(rand(30000, 80000)); // 30-80ms
                return response()->json([
                    'customer_found' => (bool) rand(0, 1),
                    'customer' => [
                        'id' => rand(1, 1000),
                        'name' => 'Test Customer',
                        'phone' => '+4917612345678',
                    ],
                ]);
            });
        });
    });

