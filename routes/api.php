<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendErrorController;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\HybridBookingController;
use App\Http\Controllers\MetricsController;
use Illuminate\Http\Request;
use App\Services\CalcomV2Service;
use App\Http\Controllers\UnifiedWebhookController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MCPHealthCheckController;
use App\Http\Controllers\Api\DocumentationDataController;
use App\Http\Controllers\MCPGatewayController;
use App\Http\Controllers\RetellCustomFunctionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Load MCP Routes
require __DIR__.'/api-mcp.php';

// Load Test Webhook Routes (REMOVE IN PRODUCTION!)
if (app()->environment('local', 'development', 'production')) {
    require __DIR__.'/test-webhook.php';
}

// ---- Metrics Endpoint ----------------------------
Route::get('/metrics', [App\Http\Controllers\Api\MetricsController::class, 'metrics'])
    ->middleware(['api.metrics.auth'])
    ->name('api.metrics');

// ---- Zeitinfo Endpoint for Retell.ai ----------------------------
Route::get('/zeitinfo', [App\Http\Controllers\ZeitinfoController::class, 'jetzt'])
    ->name('api.zeitinfo');

// ---- Test Webhook Endpoints ----------------------------
Route::prefix('test')->group(function () {
    Route::match(['GET', 'POST'], '/webhook', [App\Http\Controllers\Api\TestWebhookController::class, 'test'])
        ->name('api.test.webhook');
    Route::post('/webhook/simulate-retell', [App\Http\Controllers\Api\TestWebhookController::class, 'simulateRetellWebhook'])
        ->name('api.test.webhook.simulate-retell');
});

// ---- Retell.ai Custom Function Endpoints ----------------------------
Route::prefix('retell')->group(function () {
    // Appointment data collection endpoint for Retell custom function
    Route::post('/collect-appointment', [App\Http\Controllers\Api\RetellAppointmentCollectorController::class, 'collect'])
        ->name('api.retell.collect-appointment');
    Route::get('/collect-appointment/test', [App\Http\Controllers\Api\RetellAppointmentCollectorController::class, 'test'])
        ->name('api.retell.collect-appointment.test');
    
    // Customer recognition endpoints with security
    Route::post('/identify-customer', [App\Http\Controllers\Api\RetellCustomerRecognitionController::class, 'identifyCustomer'])
        ->middleware(['verify.retell.signature', 'validate.retell', 'throttle:60,1'])
        ->name('api.retell.identify-customer');
    Route::post('/save-preference', [App\Http\Controllers\Api\RetellCustomerRecognitionController::class, 'savePreference'])
        ->middleware(['verify.retell.signature', 'validate.retell', 'throttle:30,1'])
        ->name('api.retell.save-preference');
    Route::post('/apply-vip-benefits', [App\Http\Controllers\Api\RetellCustomerRecognitionController::class, 'applyVipBenefits'])
        ->middleware(['verify.retell.signature', 'validate.retell', 'throttle:30,1'])
        ->name('api.retell.apply-vip-benefits');
    
    // Call transfer and callback endpoints
    Route::post('/transfer-to-fabian', [App\Http\Controllers\Api\RetellCallTransferController::class, 'transferToFabian'])
        ->name('api.retell.transfer-to-fabian');
    Route::post('/check-transfer-availability', [App\Http\Controllers\Api\RetellCallTransferController::class, 'checkAvailabilityForTransfer'])
        ->name('api.retell.check-transfer-availability');
    Route::post('/schedule-callback', [App\Http\Controllers\Api\RetellCallTransferController::class, 'scheduleCallback'])
        ->name('api.retell.schedule-callback');
    Route::post('/handle-urgent-transfer', [App\Http\Controllers\Api\RetellCallTransferController::class, 'handleUrgentTransfer'])
        ->name('api.retell.handle-urgent-transfer');
});

// ---- Documentation Data API ----------------------------
Route::prefix('docs-data')->group(function () {
    Route::get('/metrics', [DocumentationDataController::class, 'metrics'])
        ->name('api.docs.metrics');
    Route::get('/performance', [DocumentationDataController::class, 'performance'])
        ->name('api.docs.performance');
    Route::get('/workflows', [DocumentationDataController::class, 'workflows'])
        ->name('api.docs.workflows');
    Route::get('/health', [DocumentationDataController::class, 'health'])
        ->name('api.docs.health');
});

// ---- TEMPORARY FIX: MCP Retell Webhook Route ----------------------------
Route::prefix('mcp')->group(function () {
    // Redirect MCP webhook to standard webhook handler (WITHOUT signature verification for now)
    Route::post('/retell/webhook', function (Request $request) {
        Log::warning('MCP Retell Webhook received - redirecting to standard handler', [
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all()
        ]);
        
        // Temporarily process without signature verification
        return app(RetellWebhookController::class)->processWebhook($request);
    })->name('mcp.retell.webhook.temp');
});

// ---- TEMPORARY DEBUG: Retell Webhook without signature verification ----------------------------
Route::post('/retell/webhook-debug', [App\Http\Controllers\Api\RetellWebhookDebugController::class, 'handle'])
    ->name('retell.webhook.debug');
    
// Also create debug route for standard path
Route::post('/retell/webhook-nosig', [App\Http\Controllers\Api\RetellWebhookDebugController::class, 'handle'])
    ->name('retell.webhook.nosig');

// ---- Health Check Endpoints ----------------------------
// Simple ping endpoint for load balancers
Route::get('/health', [HealthController::class, 'health'])
    ->name('api.health');

// Comprehensive health check
Route::get('/health/comprehensive', [HealthController::class, 'comprehensive'])
    ->name('api.health.comprehensive');

// Individual service health checks
Route::get('/health/service/{service}', [HealthController::class, 'service'])
    ->name('api.health.service');
    
// Legacy Cal.com specific health check
Route::get('/health/calcom', [HealthController::class, 'calcomHealth'])
    ->name('api.health.calcom');

// ---- MCP Orchestrator Routes ----------------------------
Route::prefix('mcp')->middleware(['throttle:1000,1'])->group(function () {
    // MCPGateway endpoints (proper JSON-RPC 2.0)
    Route::post('/gateway', [\App\Http\Controllers\MCPGatewayController::class, 'handle'])
        ->name('mcp.gateway');
    Route::get('/gateway/health', [\App\Http\Controllers\MCPGatewayController::class, 'health'])
        ->name('mcp.gateway.health');
    Route::get('/gateway/methods', [\App\Http\Controllers\MCPGatewayController::class, 'methods'])
        ->name('mcp.gateway.methods');
    
    // Legacy orchestrator endpoints (kept for backward compatibility)
    Route::post('/execute', [\App\Http\Controllers\Api\MCPController::class, 'execute']);
    Route::post('/batch', [\App\Http\Controllers\Api\MCPController::class, 'batch']);
    Route::get('/health', [\App\Http\Controllers\Api\MCPController::class, 'orchestratorHealth']);
    Route::get('/metrics', [\App\Http\Controllers\Api\MCPController::class, 'orchestratorMetrics']);
    Route::get('/info', [\App\Http\Controllers\Api\MCPController::class, 'info']);
    
    // MCP Webhook handler (bypasses signature verification)
    Route::post('/webhook/retell', [\App\Http\Controllers\Api\MCPWebhookController::class, 'handleRetell'])
        ->name('mcp.webhook.retell');
    Route::get('/webhook/test', [\App\Http\Controllers\Api\MCPWebhookController::class, 'test'])
        ->name('mcp.webhook.test');
    
    // Service-specific endpoints (existing)
    Route::prefix('database')->group(function () {
        Route::get('/schema', [\App\Http\Controllers\Api\MCPController::class, 'databaseSchema']);
        Route::post('/query', [\App\Http\Controllers\Api\MCPController::class, 'databaseQuery']);
        Route::post('/search', [\App\Http\Controllers\Api\MCPController::class, 'databaseSearch']);
        Route::get('/failed-appointments', [\App\Http\Controllers\Api\MCPController::class, 'databaseFailedAppointments']);
        Route::get('/call-stats', [\App\Http\Controllers\Api\MCPController::class, 'databaseCallStats']);
        Route::get('/tenant-stats', [\App\Http\Controllers\Api\MCPController::class, 'databaseTenantStats']);
    });
    
    Route::prefix('calcom')->group(function () {
        Route::get('/event-types', [\App\Http\Controllers\Api\MCPController::class, 'calcomEventTypes']);
        Route::post('/availability', [\App\Http\Controllers\Api\MCPController::class, 'calcomAvailability']);
        Route::get('/bookings', [\App\Http\Controllers\Api\MCPController::class, 'calcomBookings']);
        Route::get('/assignments/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'calcomAssignments']);
        Route::post('/sync', [\App\Http\Controllers\Api\MCPController::class, 'calcomSync']);
        Route::get('/test/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'calcomTest']);
    });
    
    Route::prefix('retell')->group(function () {
        Route::get('/agent/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellAgent']);
        Route::get('/agents/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellAgents']);
        Route::post('/call-stats', [\App\Http\Controllers\Api\MCPController::class, 'retellCallStats']);
        Route::post('/recent-calls', [\App\Http\Controllers\Api\MCPController::class, 'retellRecentCalls']);
        Route::get('/call/{callId}', [\App\Http\Controllers\Api\MCPController::class, 'retellCallDetails']);
        Route::post('/search-calls', [\App\Http\Controllers\Api\MCPController::class, 'retellSearchCalls']);
        Route::get('/phone-numbers/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellPhoneNumbers']);
        Route::get('/test/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellTest']);
    });
    
    Route::prefix('queue')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\MCPController::class, 'queueOverview']);
        Route::get('/failed-jobs', [\App\Http\Controllers\Api\MCPController::class, 'queueFailedJobs']);
        Route::get('/recent-jobs', [\App\Http\Controllers\Api\MCPController::class, 'queueRecentJobs']);
        Route::get('/job/{jobId}', [\App\Http\Controllers\Api\MCPController::class, 'queueJobDetails']);
        Route::post('/job/{jobId}/retry', [\App\Http\Controllers\Api\MCPController::class, 'queueRetryJob']);
        Route::get('/metrics', [\App\Http\Controllers\Api\MCPController::class, 'queueMetrics']);
        Route::get('/workers', [\App\Http\Controllers\Api\MCPController::class, 'queueWorkers']);
        Route::post('/search', [\App\Http\Controllers\Api\MCPController::class, 'queueSearchJobs']);
    });
    
    // Cache management
    Route::delete('/cache/{service}', [\App\Http\Controllers\Api\MCPController::class, 'clearCache']);
    
    // Real-time streaming endpoint
    Route::get('/stream', [\App\Http\Controllers\Api\MCPStreamController::class, 'stream'])
        ->middleware(['auth:sanctum']);
});

// ---- Cookie Consent API ----------------------------
Route::prefix('cookie-consent')->group(function () {
    Route::get('/status', [\App\Http\Controllers\Api\CookieConsentController::class, 'status']);
    Route::post('/save', [\App\Http\Controllers\Api\CookieConsentController::class, 'save']);
    Route::post('/accept-all', [\App\Http\Controllers\Api\CookieConsentController::class, 'acceptAll']);
    Route::post('/reject-all', [\App\Http\Controllers\Api\CookieConsentController::class, 'rejectAll']);
    Route::post('/withdraw', [\App\Http\Controllers\Api\CookieConsentController::class, 'withdraw']);
});

// ---- Metrics Endpoint for Prometheus ----------------------------
Route::get('/metrics', [\App\Http\Controllers\Api\MetricsController::class, 'index'])
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

// 2) Cal.com Webhook - NOW USING MCP UnifiedWebhookController
Route::post('calcom/webhook', function (Request $request) {
    Log::info('[MCP Migration] Legacy /calcom/webhook route used, redirecting to UnifiedWebhookController');
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['calcom.signature'])
  ->name('calcom.webhook');

// ---- Retell Webhook (POST) ➜ NOW USING MCP UnifiedWebhookController
Route::post('/retell/webhook', function (Request $request) {
    Log::info('[MCP Migration] Legacy /retell/webhook route used, redirecting to UnifiedWebhookController');
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['verify.retell.signature'])
  ->name('retell.webhook');

// ---- OPTIMIZED: NOW USING MCP UnifiedWebhookController ----
Route::post('/retell/optimized-webhook', function (Request $request) {
    Log::info('[MCP Migration] Legacy /retell/optimized-webhook route used, redirecting to UnifiedWebhookController');
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['verify.retell.signature'])
  ->name('retell.optimized.webhook');

// ---- DEBUG: NOW USING MCP UnifiedWebhookController with extra logging ----
Route::post('/retell/debug-webhook', function (Request $request) {
    Log::info('[MCP Migration] Debug webhook endpoint used', [
        'headers' => $request->headers->all(),
        'body' => $request->all(),
        'ip' => $request->ip()
    ]);
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['ip.whitelist'])
  ->name('retell.debug.webhook');

// ---- TEST: Simple webhook endpoint for testing ----
Route::post('/test/webhook', [App\Http\Controllers\TestWebhookController::class, 'test'])
    ->name('test.webhook');

// ---- MCP TEST: Direct MCP webhook for testing (NO SECURITY) ----
Route::post('/test/mcp-webhook', function (\Illuminate\Http\Request $request) {
    \Log::info('[MCP Test] Direct webhook test', [
        'body' => $request->all()
    ]);
    
    try {
        $controller = app(\App\Http\Controllers\RetellWebhookMCPController::class);
        return $controller->processWebhook($request);
    } catch (\Exception $e) {
        \Log::error('[MCP Test] Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
})
    ->name('test.mcp.webhook');

// ---- ENHANCED: NOW USING MCP UnifiedWebhookController ----
Route::post('/retell/enhanced-webhook', function (Request $request) {
    Log::info('[MCP Migration] Legacy /retell/enhanced-webhook route used, redirecting to UnifiedWebhookController');
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['verify.retell.signature'])
  ->name('retell.enhanced.webhook');

// ---- [DUPLICATE REMOVED] This route was defined twice, now removed ----

// ---- MCP-BASED: Retell Webhook using MCP services ----
Route::post('/retell/mcp-webhook', [App\Http\Controllers\MCPWebhookController::class, 'handleRetellWebhook'])
    ->name('retell.mcp.webhook');
Route::get('/retell/mcp-webhook/stats', [App\Http\Controllers\MCPWebhookController::class, 'getWebhookStats'])
    ->name('retell.mcp.stats');
Route::get('/retell/mcp-webhook/health', [App\Http\Controllers\MCPWebhookController::class, 'health'])
    ->name('retell.mcp.health');

// ---- Retell Function Call Handler (for real-time during calls) ----
// With signature verification middleware for security
Route::post('/retell/function-call', [App\Http\Controllers\RetellRealtimeController::class, 'handleFunctionCall'])
    ->middleware('verify.retell.signature');

// ---- Stripe Webhook (POST) --------------------------------------
Route::post('/stripe/webhook', [App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->middleware(['verify.stripe.signature', 'webhook.replay.protection'])
    ->name('stripe.webhook');

// ---- Billing Webhook (Stripe) - NO AUTH REQUIRED ----
Route::post('/billing/webhook', [App\Http\Controllers\BillingController::class, 'webhook'])
    ->name('billing.webhook');

// ---- UNIFIED WEBHOOK HANDLER (NEW) ------------------------------
// Automatically detects and routes webhooks from any source
Route::post('/webhook', [UnifiedWebhookController::class, 'handle'])
    ->name('webhook.unified');
Route::get('/webhook/health', [UnifiedWebhookController::class, 'health'])
    ->name('webhook.health');

// ---- Cal.com V2 Health Check ------------------------------------
// Removed duplicate route - using the one defined above at line 23-24

// ---- Frontend Error Logging -------------------------------------
Route::post('/log-frontend-error', [\App\Http\Controllers\FrontendErrorController::class, 'log'])
    ->middleware(['throttle:10,1']); // Max 10 error reports per minute

// ---- Hybrid Booking Routes (V1 für Verfügbarkeit, V2 für Buchung) ----
Route::prefix('hybrid')->middleware(['input.validation'])->group(function () {
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
        
        // Calculate end time (add 30 minutes by default)
        $startTime = new \DateTime($start);
        $endTime = clone $startTime;
        $endTime->add(new \DateInterval('PT30M'));
        
        $customerData = [
            'name'     => $request->input('name', 'Max Mustermann'),
            'email'    => $request->input('email', 'test@example.com'),
            'phone'    => $request->input('phone', '+49 123 456789'),
            'timeZone' => $request->input('tz', 'Europe/Berlin'),
        ];
        
        $result = $calcom->bookAppointment(
            $eventTypeId, 
            $startTime->format('c'), 
            $endTime->format('c'), 
            $customerData,
            $request->input('notes')
        );
        
        return response()->json($result ?: ['error' => 'Booking failed']);
    });
});

// Frontend error logging
Route::post('/log-frontend-error', [FrontendErrorController::class, 'log'])->middleware('web');

// Session health check routes (protected)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/session/health', [App\Http\Controllers\SessionHealthController::class, 'check']);
    Route::post('/session/refresh', [App\Http\Controllers\SessionHealthController::class, 'refresh']);
});

// ---- Protected API Routes (require authentication) ----
Route::middleware(['auth:sanctum', 'input.validation'])->group(function () {
    // Customer API
    Route::apiResource('customers', App\Http\Controllers\API\CustomerController::class);
    
    // Appointment API
    Route::apiResource('appointments', App\Http\Controllers\API\AppointmentController::class);
    
    // Staff API
    Route::apiResource('staff', App\Http\Controllers\API\StaffController::class);
    
    // Service API
    Route::apiResource('services', App\Http\Controllers\API\ServiceController::class);
    
    // Business API
    Route::apiResource('businesses', App\Http\Controllers\API\BusinessController::class);
    
    // Call API
    Route::apiResource('calls', App\Http\Controllers\API\CallController::class);
    
    // Billing routes
    Route::get('/billing/checkout', [App\Http\Controllers\BillingController::class, 'checkout']);
});

// Event Management API Routes (also protected)
Route::prefix('event-management')->middleware(['auth:sanctum'])->group(function () {
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
Route::middleware(['auth:sanctum'])->group(function () {
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

// Dashboard Metrics API Routes
Route::prefix('dashboard')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/metrics/operational', [App\Http\Controllers\Api\DashboardMetricsController::class, 'operational']);
    Route::get('/metrics/financial', [App\Http\Controllers\Api\DashboardMetricsController::class, 'financial']);
    Route::get('/metrics/branch-comparison', [App\Http\Controllers\Api\DashboardMetricsController::class, 'branchComparison']);
    Route::get('/metrics/anomalies', [App\Http\Controllers\Api\DashboardMetricsController::class, 'anomalies']);
    Route::get('/metrics/all', [App\Http\Controllers\Api\DashboardMetricsController::class, 'all']);
});

// Monitoring Dashboard API Routes
Route::prefix('monitoring')->middleware(['auth:sanctum', 'can:view-monitoring'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\MonitoringController::class, 'dashboard']);
});

// MCP Health Check & Monitoring Routes
Route::prefix('health')->middleware(['api'])->group(function () {
    Route::get('/', [MCPHealthCheckController::class, 'health']);
    Route::get('/detailed', [MCPHealthCheckController::class, 'detailed']);
    Route::get('/service/{service}', [MCPHealthCheckController::class, 'service']);
    Route::get('/ready', [MCPHealthCheckController::class, 'ready']);
    Route::get('/live', [MCPHealthCheckController::class, 'live']);
});

Route::get('/metrics', [MCPHealthCheckController::class, 'metrics'])
    ->middleware(['api']);

Route::prefix('monitoring')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('/alerts', [MCPHealthCheckController::class, 'alerts']);
    Route::get('/service/{service}/metrics', [MCPHealthCheckController::class, 'serviceMetrics']);
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
    Route::middleware(['auth:sanctum'])->group(function () {
        // Event Types
        Route::get('/event-types', [App\Http\Controllers\API\MobileAppController::class, 'getEventTypes']);
        
        // Availability
        Route::post('/availability/check', [App\Http\Controllers\API\MobileAppController::class, 'checkAvailability']);
        
        // Bookings
        Route::post('/bookings', [App\Http\Controllers\API\MobileAppController::class, 'createBooking']);
        Route::get('/appointments', [App\Http\Controllers\API\MobileAppController::class, 'getAppointments']);
        Route::delete('/appointments/{id}', [App\Http\Controllers\API\MobileAppController::class, 'cancelAppointment']);
        
        // Dashboard metrics for mobile
        Route::get('/dashboard', [App\Http\Controllers\Api\DashboardMetricsController::class, 'all']);
    });
});

// ---- MCP Gateway Routes (NEW) ----------------------------
Route::prefix('mcp/gateway')->group(function () {
    // Main gateway endpoint
    Route::post('/', [MCPGatewayController::class, 'handle'])
        ->name('mcp.gateway');
    
    // Gateway health and discovery
    Route::get('/health', [MCPGatewayController::class, 'health'])
        ->name('mcp.gateway.health');
    Route::get('/methods', [MCPGatewayController::class, 'methods'])
        ->name('mcp.gateway.methods');
    
    // Retell custom function gateway
    Route::post('/retell/functions/{function}', [RetellCustomFunctionController::class, 'handle'])
        ->name('mcp.gateway.retell.functions');
});

// MCP (Model Context Protocol) Routes
Route::prefix('mcp')->middleware(['auth:sanctum'])->group(function () {
    // Server Info
    Route::get('info', [App\Http\Controllers\Api\MCPController::class, 'info']);
    
    // Database MCP
    Route::prefix('database')->group(function () {
        Route::get('schema', [App\Http\Controllers\Api\MCPController::class, 'databaseSchema']);
        Route::post('query', [App\Http\Controllers\Api\MCPController::class, 'databaseQuery']);
        Route::post('search', [App\Http\Controllers\Api\MCPController::class, 'databaseSearch']);
        Route::get('failed-appointments', [App\Http\Controllers\Api\MCPController::class, 'databaseFailedAppointments']);
        Route::get('call-stats', [App\Http\Controllers\Api\MCPController::class, 'databaseCallStats']);
        Route::get('tenant-stats', [App\Http\Controllers\Api\MCPController::class, 'databaseTenantStats']);
    });
    
    // Cal.com MCP
    Route::prefix('calcom')->group(function () {
        Route::get('event-types', [App\Http\Controllers\Api\MCPController::class, 'calcomEventTypes']);
        Route::post('availability', [App\Http\Controllers\Api\MCPController::class, 'calcomAvailability']);
        Route::get('bookings', [App\Http\Controllers\Api\MCPController::class, 'calcomBookings']);
        Route::get('assignments/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'calcomAssignments']);
        Route::post('sync', [App\Http\Controllers\Api\MCPController::class, 'calcomSync']);
        Route::get('test/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'calcomTest']);
    });
    
    // Retell.ai MCP
    Route::prefix('retell')->group(function () {
        Route::get('agent/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellAgent']);
        Route::get('agents/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellAgents']);
        Route::get('call-stats', [App\Http\Controllers\Api\MCPController::class, 'retellCallStats']);
        Route::get('recent-calls', [App\Http\Controllers\Api\MCPController::class, 'retellRecentCalls']);
        Route::get('call/{callId}', [App\Http\Controllers\Api\MCPController::class, 'retellCallDetails']);
        Route::post('search-calls', [App\Http\Controllers\Api\MCPController::class, 'retellSearchCalls']);
        Route::get('phone-numbers/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellPhoneNumbers']);
        Route::get('test/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellTest']);
    });
    
    // Sentry MCP (existing)
    Route::prefix('sentry')->group(function () {
        Route::get('issues', [App\Http\Controllers\Api\MCPController::class, 'sentryIssues']);
        Route::get('issues/{issueId}', [App\Http\Controllers\Api\MCPController::class, 'sentryIssueDetails']);
        Route::get('issues/{issueId}/latest-event', [App\Http\Controllers\Api\MCPController::class, 'sentryLatestEvent']);
        Route::post('issues/search', [App\Http\Controllers\Api\MCPController::class, 'sentrySearchIssues']);
        Route::get('performance', [App\Http\Controllers\Api\MCPController::class, 'sentryPerformance']);
    });
    
    // Queue MCP
    Route::prefix('queue')->group(function () {
        Route::get('overview', [App\Http\Controllers\Api\MCPController::class, 'queueOverview']);
        Route::get('failed-jobs', [App\Http\Controllers\Api\MCPController::class, 'queueFailedJobs']);
        Route::get('recent-jobs', [App\Http\Controllers\Api\MCPController::class, 'queueRecentJobs']);
        Route::get('job/{jobId}', [App\Http\Controllers\Api\MCPController::class, 'queueJobDetails']);
        Route::post('job/{jobId}/retry', [App\Http\Controllers\Api\MCPController::class, 'queueRetryJob']);
        Route::get('metrics', [App\Http\Controllers\Api\MCPController::class, 'queueMetrics']);
        Route::get('workers', [App\Http\Controllers\Api\MCPController::class, 'queueWorkers']);
        Route::post('search', [App\Http\Controllers\Api\MCPController::class, 'queueSearchJobs']);
    });
    
    // Cache Management
    Route::post('{service}/cache/clear', [App\Http\Controllers\Api\MCPController::class, 'clearCache']);
});
