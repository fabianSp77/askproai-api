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
use App\Http\Controllers\Api\CircuitBreakerHealthController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\TwilioWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Command Intelligence API Routes (ohne CSRF)
Route::middleware('api-no-csrf')->group(function () {
    Route::post('/login', [App\Http\Controllers\Auth\ApiLoginController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Auth\ApiLoginController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::get('/user', [App\Http\Controllers\Auth\ApiLoginController::class, 'user'])
        ->middleware('auth:sanctum');
    Route::post('/user/tokens', [App\Http\Controllers\Auth\ApiTokenController::class, 'store'])
        ->middleware('auth:sanctum');
});

// Load MCP Routes
require __DIR__.'/api-mcp.php';

// Retell Agent Editor API Routes
Route::prefix('mcp/retell')->middleware(['web', 'auth'])->group(function () {
    Route::patch('/update-agent/{agentId}', [App\Http\Controllers\Api\RetellAgentUpdateController::class, 'updateAgent'])
        ->name('api.retell.update-agent');
    Route::post('/publish-agent/{agentId}', [App\Http\Controllers\Api\RetellAgentUpdateController::class, 'publishAgent'])
        ->name('api.retell.publish-agent');
});

// Test webhook routes removed for security
// Never include test routes in production

// Load Retell test routes - temporarily enabled for testing
// TODO: Restrict to development environment after testing is complete
if (file_exists(__DIR__.'/retell-test.php')) {
    require __DIR__.'/retell-test.php';
}

// Retell Monitor API Routes
Route::prefix('retell/monitor')->group(function () {
    Route::get('/stats', [App\Http\Controllers\RetellMonitorController::class, 'stats']);
    Route::get('/calcom-status', [App\Http\Controllers\RetellMonitorController::class, 'calcomStatus']);
    Route::get('/activity', [App\Http\Controllers\RetellMonitorController::class, 'activity']);
});

// ---- Metrics Endpoint ----------------------------
Route::get('/metrics', [App\Http\Controllers\Api\MetricsController::class, 'metrics'])
    ->middleware(['api.metrics.auth'])
    ->name('api.metrics');

// ---- Zeitinfo Endpoint for Retell.ai ----------------------------
// Protected with auth:sanctum for security
Route::get('/zeitinfo', [App\Http\Controllers\ZeitinfoController::class, 'jetzt'])
    ->middleware(['auth:sanctum'])
    ->name('api.zeitinfo');

// Test webhook endpoints removed for security

// ---- Retell.ai Custom Function Endpoints ----------------------------
Route::prefix('retell')->group(function () {
    // Appointment data collection endpoint for Retell custom function
    Route::post('/collect-appointment', [App\Http\Controllers\Api\RetellAppointmentCollectorController::class, 'collect'])
        ->middleware(['verify.retell.signature', 'throttle:60,1'])
        ->name('api.retell.collect-appointment');
    // Test endpoint removed for security
    // Route::get('/collect-appointment/test', [App\Http\Controllers\Api\RetellAppointmentCollectorController::class, 'test'])
    //     ->name('api.retell.collect-appointment.test');
    
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
        ->middleware(['verify.retell.signature', 'throttle:30,1'])
        ->name('api.retell.transfer-to-fabian');
    Route::post('/check-transfer-availability', [App\Http\Controllers\Api\RetellCallTransferController::class, 'checkAvailabilityForTransfer'])
        ->middleware(['verify.retell.signature', 'throttle:30,1'])
        ->name('api.retell.check-transfer-availability');
    Route::post('/schedule-callback', [App\Http\Controllers\Api\RetellCallTransferController::class, 'scheduleCallback'])
        ->middleware(['verify.retell.signature', 'throttle:30,1'])
        ->name('api.retell.schedule-callback');
    Route::post('/handle-urgent-transfer', [App\Http\Controllers\Api\RetellCallTransferController::class, 'handleUrgentTransfer'])
        ->middleware(['verify.retell.signature', 'throttle:30,1'])
        ->name('api.retell.handle-urgent-transfer');
});

// ---- Documentation Data API ----------------------------
// Protected with auth:sanctum to prevent data exposure
Route::prefix('docs-data')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/metrics', [DocumentationDataController::class, 'metrics'])
        ->name('api.docs.metrics');
    Route::get('/performance', [DocumentationDataController::class, 'performance'])
        ->name('api.docs.performance');
    Route::get('/workflows', [DocumentationDataController::class, 'workflows'])
        ->name('api.docs.workflows');
    Route::get('/health', [DocumentationDataController::class, 'health'])
        ->name('api.docs.health');
});

// ---- Billing & Usage API ----------------------------
// Protected with auth:sanctum for customer access
Route::prefix('billing')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/usage/current', [App\Http\Controllers\Api\BillingUsageController::class, 'currentUsage'])
        ->name('api.billing.usage.current');
    Route::get('/usage/projection', [App\Http\Controllers\Api\BillingUsageController::class, 'projection'])
        ->name('api.billing.usage.projection');
    Route::get('/history', [App\Http\Controllers\Api\BillingUsageController::class, 'history'])
        ->name('api.billing.history');
    Route::get('/period/{periodId}', [App\Http\Controllers\Api\BillingUsageController::class, 'periodDetails'])
        ->name('api.billing.period.details');
    Route::get('/period/{periodId}/download', [App\Http\Controllers\Api\BillingUsageController::class, 'downloadReport'])
        ->name('api.billing.period.download');
});

// ---- MCP Retell Webhook Route (with signature verification) ----
Route::prefix('mcp')->group(function () {
    Route::post('/retell/webhook', function (Request $request) {
        return app(RetellWebhookController::class)->processWebhook($request);
    })->middleware(['verify.retell.signature', 'webhook.replay.protection'])
      ->name('mcp.retell.webhook');
});

// Debug webhook routes removed for security

// ---- Health Check Endpoints ----------------------------
// Simple ping endpoint for load balancers (public for monitoring tools)
Route::get('/health', [HealthController::class, 'health'])
    ->name('api.health');

// Comprehensive health check (contains sensitive data - requires auth)
Route::get('/health/comprehensive', [HealthController::class, 'comprehensive'])
    ->middleware(['auth:sanctum'])
    ->name('api.health.comprehensive');

// Individual service health checks (contains sensitive data - requires auth)
Route::get('/health/service/{service}', [HealthController::class, 'service'])
    ->middleware(['auth:sanctum'])
    ->name('api.health.service');
    
// Legacy Cal.com specific health check (contains sensitive data - requires auth)
Route::get('/health/calcom', [HealthController::class, 'calcomHealth'])
    ->middleware(['auth:sanctum'])
    ->name('api.health.calcom');

// ---- MCP Orchestrator Routes ----------------------------
Route::prefix('mcp')->middleware(['auth:sanctum', 'throttle:100,1', 'validate.company.context'])->group(function () {
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
    // Test endpoint removed for security
    
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
        // Test endpoint removed - use health check or specific diagnostic endpoints
    });
    
    Route::prefix('retell')->group(function () {
        Route::get('/agent/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellAgent']);
        Route::get('/agents/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellAgents']);
        Route::post('/call-stats', [\App\Http\Controllers\Api\MCPController::class, 'retellCallStats']);
        Route::post('/recent-calls', [\App\Http\Controllers\Api\MCPController::class, 'retellRecentCalls']);
        Route::get('/call/{callId}', [\App\Http\Controllers\Api\MCPController::class, 'retellCallDetails']);
        Route::post('/search-calls', [\App\Http\Controllers\Api\MCPController::class, 'retellSearchCalls']);
        Route::get('/phone-numbers/{companyId}', [\App\Http\Controllers\Api\MCPController::class, 'retellPhoneNumbers']);
        // Test endpoint removed - use health check or specific diagnostic endpoints
        
        // Agent version management
        Route::get('/agent-version/{agentId}/{version}', [\App\Http\Controllers\Api\RetellAgentVersionController::class, 'getVersion'])
            ->name('mcp.retell.agent-version');
        Route::post('/agent-compare/{agentId}', [\App\Http\Controllers\Api\RetellAgentVersionController::class, 'compareVersions'])
            ->name('mcp.retell.agent-compare');
            
        // Test call functionality
        Route::post('/test-call', [\App\Http\Controllers\Api\RetellTestCallController::class, 'initiateTestCall'])
            ->name('mcp.retell.test-call');
        Route::get('/test-call/{callId}/status', [\App\Http\Controllers\Api\RetellTestCallController::class, 'getTestCallStatus'])
            ->name('mcp.retell.test-call-status');
        Route::get('/test-scenarios/{agentId}', [\App\Http\Controllers\Api\RetellTestCallController::class, 'getTestScenarios'])
            ->name('mcp.retell.test-scenarios');
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

// ---- Twilio Webhooks ----------------------------
Route::prefix('twilio')->group(function () {
    // Status callback for message delivery updates
    Route::post('/status-callback', [TwilioWebhookController::class, 'statusCallback'])
        ->name('twilio.status-callback');
    
    // Incoming message webhook
    Route::post('/incoming-message', [TwilioWebhookController::class, 'incomingMessage'])
        ->name('twilio.incoming-message');
    
    // MCP integration point for Twilio
    Route::post('/mcp/status-callback', [TwilioWebhookController::class, 'statusCallback'])
        ->name('twilio.mcp.status-callback');
});

// ---- Stripe Webhooks ----------------------------
Route::prefix('stripe')->group(function () {
    // Webhook for payment events
    Route::post('/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
        ->name('stripe.webhook');
});

// ---- Metrics Endpoint for Prometheus ----------------------------
// Duplicate of line 33-35, removing this one to avoid conflicts

// Test metrics endpoint removed for security

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

// Debug webhook removed for security - use standard webhook with logging if needed

// Test webhook endpoint removed for security

// MCP test webhook removed for security

// ---- ENHANCED: NOW USING MCP UnifiedWebhookController ----
Route::post('/retell/enhanced-webhook', function (Request $request) {
    Log::info('[MCP Migration] Legacy /retell/enhanced-webhook route used, redirecting to UnifiedWebhookController');
    return app(UnifiedWebhookController::class)->handle($request);
})->middleware(['verify.retell.signature'])
  ->name('retell.enhanced.webhook');

// ---- [DUPLICATE REMOVED] This route was defined twice, now removed ----

// ---- MCP-BASED: Retell Webhook using MCP services ----
Route::post('/retell/mcp-webhook', [App\Http\Controllers\MCPWebhookController::class, 'handleRetellWebhook'])
    ->middleware(['verify.retell.signature', 'webhook.replay.protection'])
    ->name('retell.mcp.webhook');
// Stats endpoint removed - use authenticated MCP routes instead
Route::get('/retell/mcp-webhook/health', [App\Http\Controllers\MCPWebhookController::class, 'health'])
    ->middleware(['auth:sanctum'])
    ->name('retell.mcp.health');

// ---- Retell Function Call Handler (for real-time during calls) ----
// With signature verification middleware for security
Route::post('/retell/function-call', [App\Http\Controllers\RetellRealtimeController::class, 'handleFunctionCall'])
    ->middleware('verify.retell.signature');

// ---- NEW: Retell Real-time Routes for Live Updates ----
Route::prefix('retell/realtime')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\Api\RetellRealtimeController::class, 'handleWebhook'])
        ->name('retell.realtime.webhook');
    Route::get('/active-calls', [\App\Http\Controllers\Api\RetellRealtimeController::class, 'getActiveCalls'])
        ->name('retell.realtime.active-calls');
    Route::get('/stream', [\App\Http\Controllers\Api\RetellRealtimeController::class, 'streamCallUpdates'])
        ->name('retell.realtime.stream');
    Route::post('/sync', [\App\Http\Controllers\Api\RetellRealtimeController::class, 'syncRecentCalls'])
        ->name('retell.realtime.sync');
});

// ---- Stripe Webhook (POST) --------------------------------------
Route::post('/stripe/webhook', [App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->middleware(['verify.stripe.signature', 'webhook.replay.protection'])
    ->name('stripe.webhook');

// ---- Billing Webhook (Stripe) - NO AUTH REQUIRED ----
Route::post('/billing/webhook', [App\Http\Controllers\BillingController::class, 'webhook'])
    ->middleware(['verify.stripe.signature', 'webhook.replay.protection'])
    ->name('billing.webhook');

// ---- UNIFIED WEBHOOK HANDLER (REMOVED FOR SECURITY) ------------------------------
// Generic webhook endpoint removed - use provider-specific endpoints with signature verification:
// - /api/retell/webhook (with verify.retell.signature middleware)
// - /api/calcom/webhook (with calcom.signature middleware)
// - /api/stripe/webhook (with verify.stripe.signature middleware)
// This ensures all webhooks are properly authenticated

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
// Test booking endpoint removed for security

// Calcom V2 test routes removed for security - use authenticated MCP endpoints instead

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
    
    // Legacy billing route
    Route::get('/billing/checkout', [App\Http\Controllers\BillingController::class, 'checkout']);
    
    // Stripe Billing API
    Route::prefix('billing')->group(function () {
        Route::get('/plans', [App\Http\Controllers\StripeBillingController::class, 'getPlans']);
        Route::post('/subscriptions', [App\Http\Controllers\StripeBillingController::class, 'createSubscription']);
        Route::put('/subscriptions/{subscription}', [App\Http\Controllers\StripeBillingController::class, 'updateSubscription']);
        Route::delete('/subscriptions/{subscription}', [App\Http\Controllers\StripeBillingController::class, 'cancelSubscription']);
        Route::post('/subscriptions/{subscription}/resume', [App\Http\Controllers\StripeBillingController::class, 'resumeSubscription']);
        Route::get('/subscriptions/{subscription}/usage', [App\Http\Controllers\StripeBillingController::class, 'getUsage']);
        Route::post('/portal-session', [App\Http\Controllers\StripeBillingController::class, 'createPortalSession']);
        Route::post('/checkout-session', [App\Http\Controllers\StripeBillingController::class, 'createCheckoutSession']);
    });
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
    // Basic health check (public for monitoring tools)
    Route::get('/', [MCPHealthCheckController::class, 'health']);
    Route::get('/ready', [MCPHealthCheckController::class, 'ready']);
    Route::get('/live', [MCPHealthCheckController::class, 'live']);
    
    // Detailed health checks (contain sensitive data - require auth)
    Route::get('/detailed', [MCPHealthCheckController::class, 'detailed'])
        ->middleware(['auth:sanctum']);
    Route::get('/service/{service}', [MCPHealthCheckController::class, 'service'])
        ->middleware(['auth:sanctum']);
    
    // Circuit Breaker health checks
    Route::get('/circuit-breakers', [CircuitBreakerHealthController::class, 'index'])
        ->middleware(['auth:sanctum']);
    Route::get('/circuit-breakers/{service}', [CircuitBreakerHealthController::class, 'show'])
        ->middleware(['auth:sanctum']);
});

// Duplicate metrics endpoint - removed to avoid conflicts with line 33-35

Route::prefix('monitoring')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('/alerts', [MCPHealthCheckController::class, 'alerts']);
    Route::get('/service/{service}/metrics', [MCPHealthCheckController::class, 'serviceMetrics']);
});

// Mobile App API Routes
Route::prefix('mobile')->group(function () {
    // Public routes
    Route::post('/device/register', [App\Http\Controllers\API\MobileAppController::class, 'registerDevice']);
    
    // Test endpoint removed for security - use health check endpoints instead
    
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
        // Test endpoint removed - use health check or specific diagnostic endpoints
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
        // Test endpoint removed - use health check or specific diagnostic endpoints
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

// Retell AI Custom Function Routes
Route::prefix('retell')->group(function () {
    // Appointment-related routes (protected by middleware)
    Route::middleware(['check.appointment.booking'])->group(function () {
        Route::post('/collect-appointment', [App\Http\Controllers\RetellCustomFunctionsController::class, 'collectAppointment']);
        Route::post('/check-availability', [App\Http\Controllers\RetellCustomFunctionsController::class, 'checkAvailability']);
        Route::post('/book-appointment', [App\Http\Controllers\RetellCustomFunctionsController::class, 'bookAppointment']);
        Route::post('/cancel-appointment', [App\Http\Controllers\RetellCustomFunctionsController::class, 'cancelAppointment']);
        Route::post('/reschedule-appointment', [App\Http\Controllers\RetellCustomFunctionsController::class, 'rescheduleAppointment']);
    });
    
    // General routes (no appointment booking required)
    Route::post('/check-customer', [App\Http\Controllers\RetellCustomFunctionsController::class, 'checkCustomer']);
    
    // Krückeberg Servicegruppe - Reine Datensammlung (ohne Terminbuchung)
    Route::post('/collect-data', [App\Http\Controllers\Api\RetellDataCollectionController::class, 'collectData'])
        ->name('api.retell.collect-data');
});

// User Token Management
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/tokens', [App\Http\Controllers\Api\UserTokenController::class, 'index']);
    Route::post('/user/tokens', [App\Http\Controllers\Api\UserTokenController::class, 'store']);
    Route::delete('/user/tokens/{tokenId}', [App\Http\Controllers\Api\UserTokenController::class, 'destroy']);
});

// Command Intelligence API Routes
Route::middleware(['auth:sanctum'])->prefix('v2')->group(function () {
    // Commands
    Route::prefix('commands')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V2\CommandController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\V2\CommandController::class, 'store']);
        Route::get('/categories', [App\Http\Controllers\Api\V2\CommandController::class, 'categories']);
        Route::post('/search', [App\Http\Controllers\Api\V2\CommandController::class, 'search']);
        Route::get('/{id}', [App\Http\Controllers\Api\V2\CommandController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\V2\CommandController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V2\CommandController::class, 'destroy']);
        Route::post('/{id}/execute', [App\Http\Controllers\Api\V2\CommandController::class, 'execute']);
        Route::post('/{id}/favorite', [App\Http\Controllers\Api\V2\CommandController::class, 'toggleFavorite']);
    });
    
    // Workflows
    Route::prefix('workflows')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V2\WorkflowController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\V2\WorkflowController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\V2\WorkflowController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\V2\WorkflowController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V2\WorkflowController::class, 'destroy']);
        Route::post('/{id}/execute', [App\Http\Controllers\Api\V2\WorkflowController::class, 'execute']);
        Route::post('/{id}/favorite', [App\Http\Controllers\Api\V2\WorkflowController::class, 'toggleFavorite']);
    });
    
    // Executions
    Route::prefix('executions')->group(function () {
        Route::get('/commands', [App\Http\Controllers\Api\V2\ExecutionController::class, 'commandExecutions']);
        Route::get('/workflows', [App\Http\Controllers\Api\V2\ExecutionController::class, 'workflowExecutions']);
        Route::get('/commands/{id}', [App\Http\Controllers\Api\V2\ExecutionController::class, 'commandExecutionDetails']);
        Route::get('/workflows/{id}', [App\Http\Controllers\Api\V2\ExecutionController::class, 'workflowExecutionDetails']);
        Route::get('/statistics', [App\Http\Controllers\Api\V2\ExecutionController::class, 'statistics']);
    });
});


// TEMPORARY DEBUG ROUTES - REMOVE AFTER FIXING!
Route::post('/retell/webhook-bypass', [\App\Http\Controllers\Api\RetellWebhookBypassController::class, 'handle'])
    ->name('retell.webhook.bypass');
    
Route::post('/retell/webhook-simple', [\App\Http\Controllers\Api\RetellWebhookWorkingController::class, 'handle'])
    ->name('retell.webhook.simple');
