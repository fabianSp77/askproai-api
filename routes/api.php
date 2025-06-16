<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendErrorController;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\HybridBookingController;
use App\Http\Controllers\MetricsController;
use Illuminate\Http\Request;
use App\Services\CalcomV2Service;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---- Metrics Endpoint for Prometheus ----------------------------
Route::get('/metrics', [\App\Http\Controllers\SimpleMetricsController::class, 'index'])
    ->middleware(['throttle:100,1']); // Allow 100 requests per minute

// Test route to debug metrics
Route::get('/metrics-test', function() {
    try {
        return response()->json([
            'cache_default' => config('cache.default'),
            'queue_default' => config('queue.default'),
            'redis_test' => \Illuminate\Support\Facades\Redis::connection()->ping()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// ---- Cal.com Webhook -------------------------------------------
// 1) Ping-Route (GET)  ➜ ohne Signaturprüfung
Route::get('calcom/webhook', [CalcomWebhookController::class, 'ping']);

// 2) Produktiver Webhook (POST) ➜ mit Signaturprüfung
Route::post('calcom/webhook', [CalcomWebhookController::class, 'handle'])
     ->middleware('calcom.signature');

// ---- Retell Webhook (POST) --------------------------------------
Route::post('/retell/webhook', [RetellWebhookController::class, 'processWebhook'])
    ->middleware('verify.retell.signature');

// ---- Frontend Error Logging -------------------------------------
Route::post('/log-frontend-error', [\App\Http\Controllers\FrontendErrorController::class, 'log'])
    ->middleware(['throttle:10,1']); // Max 10 error reports per minute

// ---- Hybrid Booking Routes (V1 für Verfügbarkeit, V2 für Buchung) ----
Route::prefix('hybrid')->group(function () {
    Route::get('/slots', [HybridBookingController::class, 'getAvailableSlots']);
    Route::post('/book', [HybridBookingController::class, 'bookAppointment']);
    Route::post('/book-next', [HybridBookingController::class, 'bookNextAvailable']);
});
Route::post('/calcom/book-test', function() {
    try {
        $calcomService = new \App\Services\CalcomService();

        // Testdaten mit korrektem Zeitformat
        $eventTypeId = 2031093;
        $startTime = now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0)->toIso8601String();
        
        $customerData = [
            'name' => 'Test Kunde',
            'email' => 'test@example.com',
            'phone' => '+491604366218'
        ];

        \Log::info('Teste Cal.com Buchung', [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startTime,
            'customerData' => $customerData
        ]);

        $result = $calcomService->bookAppointment(
            $eventTypeId,
            $startTime,
            null, // endTime wird von CalcomService nicht mehr benötigt
            $customerData,
            'Dies ist eine Testbuchung'
        );

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Buchung erfolgreich',
                'data' => $result
            ]);
        } else {
            // Prüfe Logs für Details
            return response()->json([
                'success' => false,
                'message' => 'Buchung fehlgeschlagen - siehe Logs für Details'
            ], 400);
        }

    } catch (\Exception $e) {
        \Log::error('Cal.com Test-Buchung Fehler', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Fehler: ' . $e->getMessage(),
            'details' => $e->getTraceAsString()
        ], 500);
    }
});

// ---- Calcom V2 Test Routes ----
Route::prefix('test/calcom-v2')->group(function () {
    Route::get('/event-types', function(Request $request) {
        $calcom = new \App\Services\CalcomV2Service();
        $teamSlug = config('services.calcom.team_slug', 'askproai');
        return response()->json($calcom->getEventTypes($teamSlug));
    });

    Route::get('/slots', function(Request $request) {
        $calcom = new \App\Services\CalcomV2Service();
        $eventTypeId = $request->query('eventTypeId');
        $start = $request->query('start', now()->toDateString());
        $end = $request->query('end', now()->addWeek()->toDateString());
        $tz = $request->query('tz', 'Europe/Berlin');
        return response()->json($calcom->getSlots($eventTypeId, $start, $end, $tz));
    });

    Route::post('/book', function(Request $request) {
        $calcom = new \App\Services\CalcomV2Service();
        $eventTypeId = $request->input('eventTypeId');
        $start = $request->input('start');
        $attendee = [
            'name'     => $request->input('name', 'Max Mustermann'),
            'email'    => $request->input('email', 'test@example.com'),
            'timeZone' => $request->input('tz', 'Europe/Berlin'),
        ];
        return response()->json($calcom->bookAppointment($eventTypeId, $start, $attendee));
    });
});

// Frontend error logging
Route::post('/log-frontend-error', [FrontendErrorController::class, 'log'])->middleware('web');

// Session health check routes (protected)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/session/health', [App\Http\Controllers\SessionHealthController::class, 'check']);
    Route::post('/session/refresh', [App\Http\Controllers\SessionHealthController::class, 'refresh']);
});

// Event Management API Routes
Route::prefix('event-management')->group(function () {
    // Event-Type Sync
    Route::get('/sync/event-types/{company}', [App\Http\Controllers\API\EventManagementController::class, 'syncEventTypes']);
    Route::get('/sync/team/{company}', [App\Http\Controllers\API\EventManagementController::class, 'syncTeamMembers']);
    
    // Verfügbarkeitsprüfung
    Route::post('/check-availability', [App\Http\Controllers\API\EventManagementController::class, 'checkAvailability']);
    
    // Event-Type Management
    Route::get('/event-types/{company}/branch/{branch?}', [App\Http\Controllers\API\EventManagementController::class, 'getEventTypes']);
    
    // Staff-Event Assignments
    Route::post('/staff-event-assignments', [App\Http\Controllers\API\EventManagementController::class, 'manageStaffEventAssignments']);
    Route::get('/staff-event-matrix/{company}', [App\Http\Controllers\API\EventManagementController::class, 'getStaffEventMatrix']);
});

// Validation API Routes
Route::middleware(['auth:api'])->group(function () {
    Route::get('/validation/last-test/{entityId}', function ($entityId) {
        $result = \App\Models\ValidationResult::getLatestForEntity('branch', $entityId);
        return response()->json($result);
    });

    Route::post('/validation/run-test/{entityId}', function ($entityId) {
        $branch = \App\Models\Branch::findOrFail($entityId);
        $integration = new \App\Services\Integrations\CalcomEnhancedIntegration();
        
        return response()->stream(function () use ($branch, $integration) {
            $results = $integration->validateAndSyncConfiguration($branch, 'branch');
            
            foreach ($results['tests'] as $testName => $testResult) {
                echo "data: " . json_encode([
                    'test' => $testName,
                    'name' => ucfirst(str_replace('_', ' ', $testName)),
                    'status' => $testResult['status'],
                    'message' => $testResult['message'],
                    'details' => $testResult['details'] ?? null
                ]) . "\n\n";
                ob_flush();
                flush();
                sleep(1); // Simuliere Test-Dauer
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ]);
    });
});

// Mobile App API Routes
Route::prefix('mobile')->group(function () {
    // Public routes
    Route::post('/device/register', [App\Http\Controllers\API\MobileAppController::class, 'registerDevice']);
    
    // Test route without auth
    Route::get('/test', function() {
        return response()->json([
            'status' => 'ok',
            'message' => 'Mobile API is working',
            'timestamp' => now()
        ]);
    });
    
    // Protected routes
    Route::middleware(['auth:api'])->group(function () {
        // Event Types
        Route::get('/event-types', [App\Http\Controllers\API\MobileAppController::class, 'getEventTypes']);
        
        // Availability
        Route::post('/availability/check', [App\Http\Controllers\API\MobileAppController::class, 'checkAvailability']);
        
        // Bookings
        Route::post('/bookings', [App\Http\Controllers\API\MobileAppController::class, 'createBooking']);
        Route::get('/appointments', [App\Http\Controllers\API\MobileAppController::class, 'getAppointments']);
        Route::delete('/appointments/{id}', [App\Http\Controllers\API\MobileAppController::class, 'cancelAppointment']);
    });
});
