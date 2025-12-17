<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\UnifiedWebhookController;
use App\Http\Controllers\Api\CalcomHealthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\Api\V2\AvailabilityController;
use App\Http\Controllers\Api\V2\BookingController;
use App\Http\Controllers\Api\V2\CalcomSyncController;
use App\Http\Controllers\Api\V2\CommunicationController;
use App\Http\Controllers\Api\CompositeBookingExampleController;
use App\Http\Controllers\Api\UserPreferenceController;
use App\Http\Controllers\TestChecklistController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---- Webhook Routes -------------------------------------------

// Legacy Retell webhook route (backward compatibility)
// SECURITY: Added retell.signature middleware to prevent webhook forgery (CVSS 9.3)
Route::post('/webhook', [UnifiedWebhookController::class, 'handleRetellLegacy'])
    ->name('webhook.retell.legacy')
    ->middleware(['retell.signature', 'throttle:60,1']);

// Cal.com webhook route (as configured in Cal.com dashboard)
Route::prefix('calcom')->group(function () {
    Route::get('/webhook', [CalcomWebhookController::class, 'ping'])
        ->name('calcom.webhook.ping')
        ->withoutMiddleware(['auth', 'auth:sanctum', 'verified']);
    Route::post('/webhook', [CalcomWebhookController::class, 'handle'])
        ->name('calcom.webhook')
        ->middleware(['calcom.signature', 'throttle:60,1'])
        ->withoutMiddleware(['auth', 'auth:sanctum', 'verified']);
});

// Modern webhook routes with proper namespacing
Route::prefix('webhooks')->group(function () {
    // Cal.com Webhook (alternative path for future migration)
    Route::get('/calcom', [CalcomWebhookController::class, 'ping'])
        ->withoutMiddleware(['auth', 'auth:sanctum', 'verified']);
    Route::post('/calcom', [CalcomWebhookController::class, 'handle'])
        ->name('webhooks.calcom')
        ->middleware(['calcom.signature', 'throttle:60,1'])
        ->withoutMiddleware(['auth', 'auth:sanctum', 'verified']);

    // Retell Webhook - Call completed events
    Route::post('/retell', [RetellWebhookController::class, '__invoke'])
        ->name('webhooks.retell')
        ->middleware(['retell.signature', 'throttle:60,1']);

    // Retell Function Call Handler (for real-time availability checking)
    // DO NOT use retell.call.ratelimit middleware - it blocks requests before handler can extract call_id!
    Route::post('/retell/function', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'handleFunctionCall'])
        ->name('webhooks.retell.function')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // Retell Function Call Handler alias (for E2E tests)
    Route::post('/retell/function-call', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'handleFunctionCall'])
        ->name('webhooks.retell.function-call')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // Retell specific function routes (used by Retell AI)
    Route::post('/retell/collect-appointment', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'collectAppointment'])
        ->name('webhooks.retell.collect-appointment')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // Retell availability check endpoint (for real-time slot checking)
    Route::post('/retell/check-availability', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'handleAvailabilityCheck'])
        ->name('webhooks.retell.check-availability')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // Retell datetime interpretation endpoint (for German time expressions)
    Route::post('/retell/datetime', [\App\Http\Controllers\Api\Retell\DateTimeInfoController::class, 'handle'])
        ->name('webhooks.retell.datetime')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // 🔧 FIX 2025-11-13: Alias for Agent V116 (uses wrong function names)
    // Agent V116 calls "get_current_context" → routes to /api/webhooks/retell/current-context
    // This is an alias to the correct initialize-call endpoint
    Route::post('/retell/current-context', [\App\Http\Controllers\Api\RetellApiController::class, 'initializeCall'])
        ->name('webhooks.retell.current-context')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // 🔧 FIX 2025-11-13: Alias for Agent V116 check-customer route
    // Agent V116 calls check_customer → routes to /api/webhooks/retell/check-customer
    // This is an alias to the correct /api/retell/check-customer endpoint
    Route::post('/retell/check-customer', [\App\Http\Controllers\Api\RetellApiController::class, 'checkCustomer'])
        ->name('webhooks.retell.check-customer')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // Retell Diagnostic Endpoint (requires authentication)
    Route::get('/retell/diagnostic', [RetellWebhookController::class, 'diagnostic'])
        ->name('webhooks.retell.diagnostic')
        ->middleware(['auth:sanctum', 'throttle:10,1']);

    // Stripe webhook for payment processing
    Route::post('/stripe', [StripePaymentController::class, 'handleWebhook'])
        ->name('webhooks.stripe')
        ->middleware(['stripe.webhook', 'throttle:60,1']);

    // Webhook monitoring endpoint
    // SECURITY: Added auth:sanctum to prevent unauthorized monitoring access
    Route::get('/monitor', [UnifiedWebhookController::class, 'monitor'])
        ->name('webhooks.monitor')
        ->middleware('auth:sanctum');
});

// ---- Health Check & Monitoring Routes -------------------------------------------
Route::prefix('health')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\HealthCheckController::class, 'basic'])
        ->name('health.basic');
    Route::get('/detailed', [\App\Http\Controllers\Api\HealthCheckController::class, 'detailed'])
        ->name('health.detailed');
    Route::get('/metrics', [\App\Http\Controllers\Api\HealthCheckController::class, 'metrics'])
        ->name('health.metrics')
        ->middleware('auth:sanctum');
});

// ---- Utility Routes -------------------------------------------
// Current time in German timezone (for Retell AI Agent)
Route::get('/zeitinfo', function () {
    $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));

    $germanWeekdays = [
        'Sonntag', 'Montag', 'Dienstag', 'Mittwoch',
        'Donnerstag', 'Freitag', 'Samstag'
    ];

    return response()->json([
        'date' => $now->format('d.m.Y'),
        'time' => $now->format('H:i'),
        'weekday' => $germanWeekdays[(int)$now->format('w')],
        'iso_date' => $now->format('Y-m-d'),
        'week_number' => $now->format('W')
    ]);
})
->name('api.zeitinfo')
->middleware(['throttle:100,1']);

// Monitoring Dashboard Routes (protected)
// Route::prefix('monitoring')->middleware(['auth:sanctum'])->group(function () {
//     Route::get('/dashboard', [MonitoringController::class, 'dashboard']);
//     Route::get('/health', [MonitoringController::class, 'health']);
//     Route::get('/metrics', [MonitoringController::class, 'metrics']);
// });

// Cal.com Health Check Routes
Route::prefix('health/calcom')->group(function () {
    Route::get('/', [CalcomHealthController::class, 'index'])
        ->name('health.calcom');
    Route::get('/detailed', [CalcomHealthController::class, 'detailed'])
        ->name('health.calcom.detailed')
        ->middleware('throttle:10,1');
    Route::get('/metrics', [CalcomHealthController::class, 'metrics'])
        ->name('health.calcom.metrics')
        ->middleware('throttle:30,1');
    Route::post('/check', [CalcomHealthController::class, 'runCheck'])
        ->name('health.calcom.check')
        ->middleware(['auth:sanctum', 'throttle:5,1']);
});

// ---- V1 API Routes -------------------------------------------
Route::prefix('v1')->group(function () {
    // Placeholder routes for API v1
    Route::get('/customers', function () {
        return response()->json(['message' => 'Customers API v1 - Coming Soon'], 501);
    });
    Route::get('/calls', function () {
        return response()->json(['message' => 'Calls API v1 - Coming Soon'], 501);
    });
    Route::get('/appointments', function () {
        return response()->json(['message' => 'Appointments API v1 - Coming Soon'], 501);
    });

    // Payment routes
    // Route::prefix('payments')->middleware(['auth:sanctum'])->group(function () {
    //     Route::post('/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent'])
    //         ->name('api.payments.create-intent');
    // });
});

// ---- V2 API Routes (Cal.com V2 Integration) -------------------------------------------
// Test route without middleware to see if it loads
Route::get('/v2/test', function() {
    return response()->json(['message' => 'V2 API is working']);
});

// V2 routes with security & monitoring middleware
Route::prefix('v2')
    ->middleware([
        'api.rate-limit',
        'api.performance',
        'api.logging'
    ])
    ->group(function () {
        // Availability endpoints
        Route::post('/availability/simple', [AvailabilityController::class, 'simple']);
        Route::post('/availability/composite', [AvailabilityController::class, 'composite']);

        // Booking endpoints with stricter rate limits
        Route::post('/bookings', [BookingController::class, 'create'])
            ->middleware('api.rate-limit:30,60'); // 30 requests per minute
        Route::patch('/bookings/{id}/reschedule', [BookingController::class, 'reschedule'])
            ->middleware('api.rate-limit:10,60'); // 10 reschedules per minute
        Route::delete('/bookings/{id}', [BookingController::class, 'cancel'])
            ->middleware('api.rate-limit:10,60'); // 10 cancellations per minute

        // Composite booking example endpoints (for hairdresser service)
        Route::prefix('composite-booking')->group(function () {
            Route::get('/availability', [CompositeBookingExampleController::class, 'checkAvailability']);
            Route::post('/book', [CompositeBookingExampleController::class, 'bookAppointment']);
            Route::get('/{appointment}', [CompositeBookingExampleController::class, 'getAppointmentDetails']);
            Route::delete('/{appointment}/cancel', [CompositeBookingExampleController::class, 'cancelAppointment']);
            Route::put('/{appointment}/reschedule', [CompositeBookingExampleController::class, 'rescheduleAppointment']);
        });

        // Communication endpoints
        Route::post('/communications/confirmation', [CommunicationController::class, 'sendConfirmation']);
        Route::post('/communications/reminder', [CommunicationController::class, 'sendReminder']);
        Route::post('/communications/cancellation', [CommunicationController::class, 'sendCancellation']);
        Route::post('/communications/ics', [CommunicationController::class, 'generateIcs']);

        // Cal.com sync endpoints with restricted rate limits
        Route::post('/calcom/push-event-types', [CalcomSyncController::class, 'pushEventTypes'])
            ->middleware('api.rate-limit:10,300'); // 10 pushes per 5 minutes
        Route::get('/calcom/drift-status', [CalcomSyncController::class, 'driftStatus']);
        Route::post('/calcom/detect-drift', [CalcomSyncController::class, 'detectDrift']);
        Route::post('/calcom/resolve-drift', [CalcomSyncController::class, 'resolveDrift']);
        Route::post('/calcom/auto-resolve', [CalcomSyncController::class, 'autoResolve']);
    });

// ---- Translation Routes -------------------------------------------
// Allow translation API without authentication for now (internal use)
Route::post('/translate', [\App\Http\Controllers\Api\TranslationController::class, 'translate'])
    ->name('api.translate');

// ---- V1 API Routes (Callback Requests) ----------------------------
// Requires Sanctum authentication + rate limiting
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Callback Requests API
    Route::apiResource('callbacks', \App\Http\Controllers\Api\V1\CallbackRequestController::class);

    // Additional callback actions
    Route::post('callbacks/{id}/assign', [\App\Http\Controllers\Api\V1\CallbackRequestController::class, 'assign'])
        ->name('api.v1.callbacks.assign');
    Route::post('callbacks/{id}/contact', [\App\Http\Controllers\Api\V1\CallbackRequestController::class, 'contact'])
        ->name('api.v1.callbacks.contact');
    Route::post('callbacks/{id}/complete', [\App\Http\Controllers\Api\V1\CallbackRequestController::class, 'complete'])
        ->name('api.v1.callbacks.complete');
});
Route::post('/detect-language', [\App\Http\Controllers\Api\TranslationController::class, 'detectLanguage'])
    ->name('api.detect-language');

// ---- Retell API Routes (Function Calls from Retell Agent) -------------------------------------------
// These routes match the function definitions in Retell Agent configuration
Route::prefix('retell')->group(function () {
    Route::post('/check-customer', [\App\Http\Controllers\Api\RetellApiController::class, 'checkCustomer'])
        ->name('api.retell.check-customer')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // 🚀 V16: Combined initialization endpoint (customer + time + policies)
    Route::post('/initialize-call', [\App\Http\Controllers\Api\RetellApiController::class, 'initializeCall'])
        ->name('api.retell.initialize-call')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/check-availability', [\App\Http\Controllers\Api\RetellApiController::class, 'checkAvailability'])
        ->name('api.retell.check-availability')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/collect-appointment', [\App\Http\Controllers\Api\RetellApiController::class, 'collectAppointment'])
        ->name('api.retell.collect-appointment')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/book-appointment', [\App\Http\Controllers\Api\RetellApiController::class, 'bookAppointment'])
        ->name('api.retell.book-appointment')
        ->middleware(['throttle:60,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/cancel-appointment', [\App\Http\Controllers\Api\RetellApiController::class, 'cancelAppointment'])
        ->name('api.retell.cancel-appointment')
        ->middleware(['throttle:30,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/reschedule-appointment', [\App\Http\Controllers\Api\RetellApiController::class, 'rescheduleAppointment'])
        ->name('api.retell.reschedule-appointment')
        ->middleware(['throttle:30,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/get-customer-appointments', [\App\Http\Controllers\Api\RetellGetAppointmentsController::class, 'getAppointments'])
        ->name('api.retell.get-customer-appointments')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // 🚀 V17: Explicit Function Node Endpoints
    Route::post('/v17/check-availability', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'checkAvailabilityV17'])
        ->name('api.retell.v17.check-availability')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/v17/book-appointment', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'bookAppointmentV17'])
        ->name('api.retell.v17.book-appointment')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // 🚀 V4: Conversation Flow V4 Endpoints (Complex Features)
    Route::post('/initialize-call-v4', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'initializeCallV4'])
        ->name('api.retell.v4.initialize-call')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/get-appointments-v4', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'getCustomerAppointmentsV4'])
        ->name('api.retell.v4.get-appointments')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/cancel-appointment-v4', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'cancelAppointmentV4'])
        ->name('api.retell.v4.cancel-appointment')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/reschedule-appointment-v4', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'rescheduleAppointmentV4'])
        ->name('api.retell.v4.reschedule-appointment')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/get-services-v4', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'getAvailableServicesV4'])
        ->name('api.retell.v4.get-services')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // 🎯 Get Available Services
    Route::post('/get-available-services', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'getAvailableServices'])
        ->name('api.retell.get-available-services')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    Route::post('/current-time-berlin', function() {
        return response()->json([
            'success' => true,
            'current_time' => \Carbon\Carbon::now('Europe/Berlin')->toIso8601String(),
            'date' => \Carbon\Carbon::now('Europe/Berlin')->format('Y-m-d'),
            'time' => \Carbon\Carbon::now('Europe/Berlin')->format('H:i'),
            'weekday' => \Carbon\Carbon::now('Europe/Berlin')->locale('de')->dayName,
            'timezone' => 'Europe/Berlin'
        ]);
    })
        ->name('api.retell.current-time-berlin')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');

    // Fallback route for query_appointment function (legacy Retell agent config)
    Route::post('/function-call', [\App\Http\Controllers\RetellFunctionCallHandler::class, 'handleFunctionCall'])
        ->name('api.retell.function-call.legacy')
        ->middleware(['throttle:100,1'])
        ->withoutMiddleware('retell.function.whitelist');
});

// ---- User Preferences Routes -------------------------------------------
Route::middleware(['auth:sanctum'])->prefix('user-preferences')->group(function () {
    Route::get('/columns/{resource}', [UserPreferenceController::class, 'getColumnPreferences'])
        ->name('api.preferences.columns.get');

    Route::post('/columns/save', [UserPreferenceController::class, 'saveColumnPreferences'])
        ->name('api.preferences.columns.save');

    Route::post('/columns/{resource}/reset', [UserPreferenceController::class, 'resetColumnPreferences'])
        ->name('api.preferences.columns.reset');
});

// ---- Customer Portal Routes -------------------------------------------
Route::prefix('customer-portal')->group(function () {
    // Public routes (no authentication required)
    Route::get('/invitations/{token}/validate', [\App\Http\Controllers\CustomerPortal\AuthController::class, 'validateToken'])
        ->name('api.customer-portal.invitations.validate')
        ->middleware(['throttle:60,1']);

    Route::post('/invitations/{token}/accept', [\App\Http\Controllers\CustomerPortal\AuthController::class, 'acceptInvitation'])
        ->name('api.customer-portal.invitations.accept')
        ->middleware(['throttle:10,1']);

    // Protected routes (Sanctum authentication required)
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Appointment list and detail
        Route::get('/appointments', [\App\Http\Controllers\CustomerPortal\AppointmentController::class, 'index'])
            ->name('api.customer-portal.appointments.index');

        Route::get('/appointments/{id}', [\App\Http\Controllers\CustomerPortal\AppointmentController::class, 'show'])
            ->name('api.customer-portal.appointments.show');

        // Alternative time slots for rescheduling
        Route::get('/appointments/{id}/alternatives', [\App\Http\Controllers\CustomerPortal\AppointmentController::class, 'alternatives'])
            ->name('api.customer-portal.appointments.alternatives');

        // Reschedule appointment (stricter rate limit)
        Route::put('/appointments/{id}/reschedule', [\App\Http\Controllers\CustomerPortal\AppointmentController::class, 'reschedule'])
            ->name('api.customer-portal.appointments.reschedule')
            ->middleware('throttle:10,1');

        // Cancel appointment (stricter rate limit)
        Route::delete('/appointments/{id}', [\App\Http\Controllers\CustomerPortal\AppointmentController::class, 'cancel'])
            ->name('api.customer-portal.appointments.cancel')
            ->middleware('throttle:10,1');
    });
});

// ---- Test Routes (Local/Testing Environment Only) -------------------------------------------
// IMPORTANT: Only available in local/testing environments (see controller)
Route::prefix('test')->group(function () {
    // E2E Test: Create Call record for testing
    // Used by backend-api-tester-v3-e2e.html to simulate real call context
    Route::post('/create-call-record', [TestChecklistController::class, 'createTestCallRecord'])
        ->name('api.test.create-call-record')
        ->middleware(['throttle:100,1']);

    // E2E Test: Delete Call record after test
    // Cleanup test data after E2E test completion
    Route::post('/delete-call-record', [TestChecklistController::class, 'deleteTestCallRecord'])
        ->name('api.test.delete-call-record')
        ->middleware(['throttle:100,1']);
});
