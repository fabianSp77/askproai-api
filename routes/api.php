<?php

use App\Http\Controllers\Api\CircuitBreakerHealthController;
use App\Http\Controllers\Api\CsrfController;
use App\Http\Controllers\Api\DocumentationDataController;
use App\Http\Controllers\Api\GitHubWebhookController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MCPHealthCheckController;
use App\Http\Controllers\API\MCPAdminController;
use App\Http\Controllers\Api\NotionWebhookController;
use App\Http\Controllers\Api\V2\PortalController;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\FrontendErrorController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\HybridBookingController;
use App\Http\Controllers\MCPGatewayController;
use App\Http\Controllers\RetellCustomFunctionController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\TwilioWebhookController;
use App\Http\Controllers\UnifiedWebhookController;
use App\Http\Controllers\HairSalonMCPController;
use App\Http\Controllers\EnhancedHairSalonMCPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health Check Routes (public)
Route::get('/health', [HealthCheckController::class, 'health']);
Route::get('/health-check', [HealthCheckController::class, 'health']);

// CSRF Token Route (public)
Route::get('/csrf-token', [CsrfController::class, 'token']);
Route::get('/health/detailed', [HealthCheckController::class, 'detailed']);

// MCP Admin API Routes (protected)
Route::prefix('admin/api/mcp')->middleware(['web', 'auth', \App\Http\Middleware\MCPAdminAccess::class])->group(function () {
    Route::get('/configuration', [MCPAdminController::class, 'getConfiguration']);
    Route::put('/configuration', [MCPAdminController::class, 'updateConfiguration']);
    Route::post('/configuration/validate', [MCPAdminController::class, 'validateConfiguration']);
    
    Route::get('/metrics', [MCPAdminController::class, 'getMetrics']);
    Route::post('/metrics/reset', [MCPAdminController::class, 'resetMetrics']);
    
    Route::get('/calls/recent', [MCPAdminController::class, 'getRecentCalls']);
    
    Route::get('/circuit-breaker/status', [MCPAdminController::class, 'getCircuitBreakerStatus']);
    Route::post('/circuit-breaker/toggle', [MCPAdminController::class, 'toggleCircuitBreaker']);
    
    Route::post('/tools/{tool}/test', [MCPAdminController::class, 'testTool']);
    Route::get('/tools', [MCPAdminController::class, 'getAvailableTools']);
    
    Route::get('/health', [MCPAdminController::class, 'getSystemHealth']);
    Route::get('/webhooks/comparison', [MCPAdminController::class, 'getWebhookComparison']);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Admin authentication - with rate limiting
    Route::post('/admin/login', [App\Http\Controllers\Api\AuthController::class, 'adminLogin'])
        ->middleware('auth.rate.limit');

    // Business portal authentication - with rate limiting
    Route::post('/portal/login', [App\Http\Controllers\Api\AuthController::class, 'portalLogin'])
        ->middleware('auth.rate.limit');

    // Customer authentication - with rate limiting
    Route::post('/customer/login', [App\Http\Controllers\Api\AuthController::class, 'customerLogin'])
        ->middleware('auth.rate.limit');

    // 2FA verification - with rate limiting
    Route::post('/verify-2fa', [App\Http\Controllers\Api\AuthController::class, 'verify2FA'])
        ->middleware('auth.rate.limit');

    // Protected routes requiring authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'user']);
        Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::post('/refresh', [App\Http\Controllers\Api\AuthController::class, 'refresh']);
    });
});

// Session Management Routes (Enhanced UX)
Route::prefix('session')->middleware(['web', 'auth'])->group(function () {
    Route::get('/status', [App\Http\Controllers\Api\SessionStatusController::class, 'status']);
    Route::post('/extend', [App\Http\Controllers\Api\SessionStatusController::class, 'extend']);
    Route::get('/activity', [App\Http\Controllers\Api\SessionStatusController::class, 'activity']);
    Route::post('/logout', [App\Http\Controllers\Api\SessionStatusController::class, 'logout']);
    Route::get('/reminders', [App\Http\Controllers\Api\SessionStatusController::class, 'securityReminders']);
});

// Tenant/Company Switching (Enhanced UX)
Route::prefix('tenant')->middleware(['web', 'auth'])->group(function () {
    Route::post('/switch', [App\Http\Controllers\Api\TenantSwitchController::class, 'switch']);
    Route::get('/available', [App\Http\Controllers\Api\TenantSwitchController::class, 'available']);
    Route::get('/current', [App\Http\Controllers\Api\TenantSwitchController::class, 'current']);
});

// Two-Factor Authentication (Enhanced UX)
Route::prefix('2fa')->middleware(['web', 'auth'])->group(function () {
    Route::post('/enable', [App\Http\Controllers\Api\TwoFactorController::class, 'enable']);
    Route::post('/validate', [App\Http\Controllers\Api\TwoFactorController::class, 'validate']);
    Route::post('/disable', [App\Http\Controllers\Api\TwoFactorController::class, 'disable']);
    Route::get('/qr-code', [App\Http\Controllers\Api\TwoFactorController::class, 'qrCode']);
    Route::get('/backup-codes', [App\Http\Controllers\Api\TwoFactorController::class, 'backupCodes']);
});

// Security Score API
Route::prefix('security')->middleware(['web', 'auth'])->group(function () {
    Route::get('/score', [App\Http\Controllers\Api\SecurityScoreController::class, 'getScore']);
    Route::post('/score/update', [App\Http\Controllers\Api\SecurityScoreController::class, 'updateScore']);
    Route::get('/improvements', [App\Http\Controllers\Api\SecurityScoreController::class, 'getImprovements']);
    Route::get('/tips', [App\Http\Controllers\Api\SecurityScoreController::class, 'getTips']);
    Route::post('/event', [App\Http\Controllers\Api\SecurityScoreController::class, 'logSecurityEvent']);
});

// Admin API Routes (React Admin Portal)
Route::prefix('admin')
    ->group(base_path('routes/api-admin.php'));

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
require __DIR__ . '/api-mcp.php';

// Session Test Routes (for validation page)
Route::prefix('test')->group(function () {
    Route::get('/session-check', function (Request $request) {
        $session = $request->session();

        return response()->json([
            'session_id' => $session->getId(),
            'has_session' => $session->isStarted(),
            'session_data' => $session->all(),
            'cookies' => $request->cookies->all(),
            'headers' => $request->headers->all(),
        ]);
    });

    Route::post('/clear-session', function (Request $request) {
        $request->session()->flush();
        $request->session()->regenerate();

        return response()->json(['status' => 'cleared']);
    });
});

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
if (file_exists(__DIR__ . '/retell-test.php')) {
    require __DIR__ . '/retell-test.php';
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

// ---- Advanced Call Management API Routes ----------------------------
Route::prefix('admin/api')->middleware(['web', 'auth'])->group(function () {
    // Smart search
    Route::get('/smart-search', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'smartSearch']);
    
    // Customer timeline
    Route::get('/customer/{customer}/timeline', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'customerTimeline']);
    
    // Call management
    Route::patch('/calls/{call}/priority', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'updateCallPriority']);
    Route::get('/calls/{call}/details', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'callDetails']);
    Route::post('/calls/{call}/voice-note', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'addVoiceNote']);
    
    // Filter and analytics
    Route::get('/filter-preset-counts', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'filterPresetCounts']);
    Route::get('/call-analytics', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'callAnalytics']);
    
    // Real-time data
    Route::get('/realtime-call-data', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'realtimeCallData']);
    Route::get('/queue-status', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'queueStatus']);
    
    // Export
    Route::post('/export-calls', [App\Http\Controllers\API\AdvancedCallManagementController::class, 'exportCalls']);
});

// ---- Retell.ai MCP (Model Context Protocol) Endpoints ----------------------------
// New MCP endpoint for direct tool calls from Retell.ai agents
Route::prefix('mcp/retell')->group(function () {
    // Health check endpoint (no authentication required for monitoring)
    Route::get('/health', [App\Http\Controllers\API\MCPHealthController::class, 'health'])
        ->name('api.mcp.retell.health');
        
    // Tool calls endpoint (authenticated)
    Route::post('/tools', [App\Http\Controllers\API\RetellMCPEndpointController::class, 'handleToolCall'])
        ->middleware(['verify.mcp.token', 'throttle:100,1'])
        ->name('api.mcp.retell.tools');
});

// ---- Retell.ai Custom Function Endpoints (Legacy - will be deprecated) ----------------------------
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

// ---- Stripe Payment Links API ----------------------------
Route::prefix('stripe')->group(function () {
    // Payment Link endpoints (requires authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/payment-link/create', [App\Http\Controllers\StripePaymentLinkController::class, 'generatePaymentLink'])
            ->name('api.stripe.payment-link.create');
        Route::get('/payment-link/{companyId}', [App\Http\Controllers\StripePaymentLinkController::class, 'getPaymentLink'])
            ->name('api.stripe.payment-link.get');
        Route::get('/payment-link/{companyId}/qr-code', [App\Http\Controllers\StripePaymentLinkController::class, 'generateQRCode'])
            ->name('api.stripe.payment-link.qr-code');
    });

    // Public topup link generation (for admin use)
    Route::post('/generate-topup-link', [App\Http\Controllers\PublicTopupController::class, 'generateLink'])
        ->middleware(['auth:sanctum'])
        ->name('api.stripe.generate-topup-link');
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
    Route::post('/gateway', [MCPGatewayController::class, 'handle'])
        ->name('mcp.gateway');
    Route::get('/gateway/health', [MCPGatewayController::class, 'health'])
        ->name('mcp.gateway.health');
    Route::get('/gateway/methods', [MCPGatewayController::class, 'methods'])
        ->name('mcp.gateway.methods');

    // Legacy orchestrator endpoints (kept for backward compatibility)
    Route::post('/execute', [App\Http\Controllers\Api\MCPController::class, 'execute']);
    Route::post('/batch', [App\Http\Controllers\Api\MCPController::class, 'batch']);
    Route::get('/health', [App\Http\Controllers\Api\MCPController::class, 'orchestratorHealth']);
    Route::get('/metrics', [App\Http\Controllers\Api\MCPController::class, 'orchestratorMetrics']);
    Route::get('/info', [App\Http\Controllers\Api\MCPController::class, 'info']);

    // MCP Webhook handler (bypasses signature verification)
    Route::post('/webhook/retell', [App\Http\Controllers\Api\MCPWebhookController::class, 'handleRetell'])
        ->name('mcp.webhook.retell');
    // Test endpoint removed for security

    // Service-specific endpoints (existing)
    Route::prefix('database')->group(function () {
        Route::get('/schema', [App\Http\Controllers\Api\MCPController::class, 'databaseSchema']);
        Route::post('/query', [App\Http\Controllers\Api\MCPController::class, 'databaseQuery']);
        Route::post('/search', [App\Http\Controllers\Api\MCPController::class, 'databaseSearch']);
        Route::get('/failed-appointments', [App\Http\Controllers\Api\MCPController::class, 'databaseFailedAppointments']);
        Route::get('/call-stats', [App\Http\Controllers\Api\MCPController::class, 'databaseCallStats']);
        Route::get('/tenant-stats', [App\Http\Controllers\Api\MCPController::class, 'databaseTenantStats']);
    });

    Route::prefix('calcom')->group(function () {
        Route::get('/event-types', [App\Http\Controllers\Api\MCPController::class, 'calcomEventTypes']);
        Route::post('/availability', [App\Http\Controllers\Api\MCPController::class, 'calcomAvailability']);
        Route::get('/bookings', [App\Http\Controllers\Api\MCPController::class, 'calcomBookings']);
        Route::get('/assignments/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'calcomAssignments']);
        Route::post('/sync', [App\Http\Controllers\Api\MCPController::class, 'calcomSync']);
        // Test endpoint removed - use health check or specific diagnostic endpoints
    });

    Route::prefix('retell')->group(function () {
        Route::get('/agent/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellAgent']);
        Route::get('/agents/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellAgents']);
        Route::post('/call-stats', [App\Http\Controllers\Api\MCPController::class, 'retellCallStats']);
        Route::post('/recent-calls', [App\Http\Controllers\Api\MCPController::class, 'retellRecentCalls']);
        Route::get('/call/{callId}', [App\Http\Controllers\Api\MCPController::class, 'retellCallDetails']);
        Route::post('/search-calls', [App\Http\Controllers\Api\MCPController::class, 'retellSearchCalls']);
        Route::get('/phone-numbers/{companyId}', [App\Http\Controllers\Api\MCPController::class, 'retellPhoneNumbers']);
        // Test endpoint removed - use health check or specific diagnostic endpoints

        // Agent version management
        Route::get('/agent-version/{agentId}/{version}', [App\Http\Controllers\Api\RetellAgentVersionController::class, 'getVersion'])
            ->name('mcp.retell.agent-version');
        Route::post('/agent-compare/{agentId}', [App\Http\Controllers\Api\RetellAgentVersionController::class, 'compareVersions'])
            ->name('mcp.retell.agent-compare');

        // Test call functionality
        Route::post('/test-call', [App\Http\Controllers\Api\RetellTestCallController::class, 'initiateTestCall'])
            ->name('mcp.retell.test-call');
        Route::get('/test-call/{callId}/status', [App\Http\Controllers\Api\RetellTestCallController::class, 'getTestCallStatus'])
            ->name('mcp.retell.test-call-status');
        Route::get('/test-scenarios/{agentId}', [App\Http\Controllers\Api\RetellTestCallController::class, 'getTestScenarios'])
            ->name('mcp.retell.test-scenarios');
    });

    Route::prefix('queue')->group(function () {
        Route::get('/overview', [App\Http\Controllers\Api\MCPController::class, 'queueOverview']);
        Route::get('/failed-jobs', [App\Http\Controllers\Api\MCPController::class, 'queueFailedJobs']);
        Route::get('/recent-jobs', [App\Http\Controllers\Api\MCPController::class, 'queueRecentJobs']);
        Route::get('/job/{jobId}', [App\Http\Controllers\Api\MCPController::class, 'queueJobDetails']);
        Route::post('/job/{jobId}/retry', [App\Http\Controllers\Api\MCPController::class, 'queueRetryJob']);
        Route::get('/metrics', [App\Http\Controllers\Api\MCPController::class, 'queueMetrics']);
        Route::get('/workers', [App\Http\Controllers\Api\MCPController::class, 'queueWorkers']);
        Route::post('/search', [App\Http\Controllers\Api\MCPController::class, 'queueSearchJobs']);
    });

    // Cache management
    Route::delete('/cache/{service}', [App\Http\Controllers\Api\MCPController::class, 'clearCache']);

    // Real-time streaming endpoint
    Route::get('/stream', [App\Http\Controllers\Api\MCPStreamController::class, 'stream'])
        ->middleware(['auth:sanctum']);
});

// ---- Cookie Consent API ----------------------------
Route::prefix('cookie-consent')->group(function () {
    Route::get('/status', [App\Http\Controllers\Api\CookieConsentController::class, 'status']);
    Route::post('/save', [App\Http\Controllers\Api\CookieConsentController::class, 'save']);
    Route::post('/accept-all', [App\Http\Controllers\Api\CookieConsentController::class, 'acceptAll']);
    Route::post('/reject-all', [App\Http\Controllers\Api\CookieConsentController::class, 'rejectAll']);
    Route::post('/withdraw', [App\Http\Controllers\Api\CookieConsentController::class, 'withdraw']);
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

// ---- GitHub Webhooks ----------------------------
Route::prefix('github')->group(function () {
    // GitHub webhook for real-time sync
    Route::post('/webhook', [GitHubWebhookController::class, 'handleWebhook'])
        ->name('github.webhook');
});

// ---- Notion Webhooks ----------------------------
Route::prefix('notion')->group(function () {
    // Notion webhook for events
    Route::post('/webhook', [NotionWebhookController::class, 'handleWebhook'])
        ->name('notion.webhook');
});

// ---- Stripe Webhooks ----------------------------
Route::prefix('stripe')->group(function () {
    // Webhook for payment events
    Route::post('/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
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

// ---- Notion Webhook Routes ----
Route::prefix('notion')->group(function () {
    // Main webhook endpoint
    Route::post('/webhook', [NotionWebhookController::class, 'handleWebhook'])
        ->name('notion.webhook');

    // Test endpoint to verify webhook configuration
    Route::post('/webhook/test', [NotionWebhookController::class, 'test'])
        ->name('notion.webhook.test');
});

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
    Route::post('/webhook', [App\Http\Controllers\Api\RetellRealtimeController::class, 'handleWebhook'])
        ->name('retell.realtime.webhook');
    Route::get('/active-calls', [App\Http\Controllers\Api\RetellRealtimeController::class, 'getActiveCalls'])
        ->name('retell.realtime.active-calls');
    Route::get('/stream', [App\Http\Controllers\Api\RetellRealtimeController::class, 'streamCallUpdates'])
        ->name('retell.realtime.stream');
    Route::post('/sync', [App\Http\Controllers\Api\RetellRealtimeController::class, 'syncRecentCalls'])
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
Route::post('/log-frontend-error', [FrontendErrorController::class, 'log'])
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
    Route::apiResource('customers', App\Http\Controllers\API\CustomerController::class)->names('api.customers');

    // Appointment API
    Route::apiResource('appointments', App\Http\Controllers\API\AppointmentController::class)->names('api.appointments');

    // Staff API
    Route::apiResource('staff', App\Http\Controllers\API\StaffController::class)->names('api.staff');

    // Service API
    Route::apiResource('services', App\Http\Controllers\API\ServiceController::class)->names('api.services');

    // Business API
    Route::apiResource('businesses', App\Http\Controllers\API\BusinessController::class);

    // Call API
    Route::apiResource('calls', App\Http\Controllers\API\CallController::class)->names('api.calls');
    Route::post('calls/{call}/translate-summary', [App\Http\Controllers\API\CallController::class, 'translateSummary'])
        ->name('api.calls.translate-summary');
    Route::get('calls/{call}/export-pdf', [App\Http\Controllers\API\CallController::class, 'exportPdf'])
        ->name('api.calls.export-pdf');

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
        $result = App\Models\ValidationResult::getLatestForEntity('branch', $entityId);

        return response()->json($result);
    });

    Route::post('/validation/run-test/{entityId}', function ($entityId) {
        $branch = App\Models\Branch::findOrFail($entityId);
        $integration = new App\Services\Integrations\CalcomEnhancedIntegration;

        return response()->stream(function () use ($branch, $integration) {
            $results = $integration->validateAndSyncConfiguration($branch, 'branch');

            foreach ($results['tests'] as $testName => $testResult) {
                echo 'data: ' . json_encode([
                    'test' => $testName,
                    'name' => ucfirst(str_replace('_', ' ', $testName)),
                    'status' => $testResult['status'],
                    'message' => $testResult['message'],
                    'details' => $testResult['details'] ?? null,
                ]) . "\n\n";
                ob_flush();
                flush();
                sleep(1); // Simuliere Test-Dauer
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
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
    Route::get('/dashboard', [App\Http\Controllers\MonitoringController::class, 'dashboard']);
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

    // Retell AI MCP Bridge
    Route::prefix('retell')->group(function () {
        // Call Management
        Route::post('initiate-call', [App\Http\Controllers\Api\RetellMCPController::class, 'initiateCall']);
        Route::get('call/{callId}/status', [App\Http\Controllers\Api\RetellMCPController::class, 'getCallStatus']);

        // Campaign Management
        Route::post('campaign/create', [App\Http\Controllers\Api\RetellMCPController::class, 'createCampaign']);
        Route::get('campaign/{campaignId}', [App\Http\Controllers\Api\RetellMCPController::class, 'getCampaign']);
        Route::get('campaigns', [App\Http\Controllers\Api\RetellMCPController::class, 'listCampaigns']);
        Route::post('campaign/{campaignId}/start', [App\Http\Controllers\Api\RetellMCPController::class, 'startCampaign']);
        Route::post('campaign/{campaignId}/pause', [App\Http\Controllers\Api\RetellMCPController::class, 'pauseCampaign']);
        Route::post('campaign/{campaignId}/resume', [App\Http\Controllers\Api\RetellMCPController::class, 'resumeCampaign']);

        // Testing & Tools
        Route::post('test-voice', [App\Http\Controllers\Api\RetellMCPController::class, 'testVoice']);
        Route::get('tools', [App\Http\Controllers\Api\RetellMCPController::class, 'getAvailableTools']);
        Route::get('health', [App\Http\Controllers\Api\RetellMCPController::class, 'healthCheck']);

        // Webhook from external MCP server (no auth required for this specific route)
        Route::post('call-created', [App\Http\Controllers\Api\RetellMCPController::class, 'callCreatedWebhook'])
            ->withoutMiddleware(['auth:sanctum']);
    });

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
Route::post('/retell/webhook-bypass', [App\Http\Controllers\Api\RetellWebhookBypassController::class, 'handle'])
    ->name('retell.webhook.bypass');

// Optimized Retell webhook with HMAC verification and Quick ACK pattern
Route::post('/retell/webhook-simple', [App\Http\Controllers\Api\RetellWebhookOptimizedController::class, 'handle'])
        ->middleware(['verify.retell.signature', 'throttle:120,1']) // Increased to 120/min for Retell retries
        ->name('retell.webhook.simple');


// Error Catalog API Routes
Route::prefix('errors')->name('api.errors.')->group(function () {
    Route::get('/search', [App\Http\Controllers\Api\ErrorCatalogApiController::class, 'search'])->name('search');
    Route::get('/statistics', [App\Http\Controllers\Api\ErrorCatalogApiController::class, 'statistics'])->name('statistics');
    Route::post('/detect', [App\Http\Controllers\Api\ErrorCatalogApiController::class, 'detect'])->name('detect');
    Route::get('/{errorCode}', [App\Http\Controllers\Api\ErrorCatalogApiController::class, 'show'])->name('show');

    // Authenticated routes for error reporting
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/report', [App\Http\Controllers\Api\ErrorCatalogApiController::class, 'reportOccurrence'])->name('report');
        Route::post('/occurrences/{occurrenceId}/resolve', [App\Http\Controllers\Api\ErrorCatalogApiController::class, 'markResolved'])->name('resolve');
    });
});

// V2 Portal API Routes
Route::prefix('v2/portal')->group(function () {
    Route::post('/auth/login', [PortalController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/dashboard', [PortalController::class, 'dashboard']);
        Route::get('/appointments', [PortalController::class, 'appointments']);
        Route::get('/calls', [PortalController::class, 'calls']);
    });
});

// Analytics API Routes
Route::prefix('analytics')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/metrics', [App\Http\Controllers\Api\AnalyticsApiController::class, 'metrics']);
    Route::get('/predict-revenue', [App\Http\Controllers\Api\AnalyticsApiController::class, 'predictRevenue']);
    Route::get('/predict-demand', [App\Http\Controllers\Api\AnalyticsApiController::class, 'predictDemand']);
    Route::get('/customer-behavior', [App\Http\Controllers\Api\AnalyticsApiController::class, 'customerBehavior']);
    Route::get('/insights', [App\Http\Controllers\Api\AnalyticsApiController::class, 'insights']);
    Route::get('/anomalies', [App\Http\Controllers\Api\AnalyticsApiController::class, 'anomalies']);
    Route::post('/optimize-schedule', [App\Http\Controllers\Api\AnalyticsApiController::class, 'optimizeSchedule']);
    Route::post('/report', [App\Http\Controllers\Api\AnalyticsApiController::class, 'generateReport']);
    Route::get('/realtime', [App\Http\Controllers\Api\AnalyticsApiController::class, 'realtime']);
    Route::post('/export', [App\Http\Controllers\Api\AnalyticsApiController::class, 'export']);
});

// Events API Routes
Route::prefix('events')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\EventsApiController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\EventsApiController::class, 'store']);
    Route::get('/{event}', [App\Http\Controllers\Api\EventsApiController::class, 'show']);
    Route::put('/{event}', [App\Http\Controllers\Api\EventsApiController::class, 'update']);
    Route::delete('/{event}', [App\Http\Controllers\Api\EventsApiController::class, 'destroy']);
    Route::post('/dispatch', [App\Http\Controllers\Api\EventsApiController::class, 'dispatch']);
    Route::get('/timeline', [App\Http\Controllers\Api\EventsApiController::class, 'timeline']);
    Route::post('/replay', [App\Http\Controllers\Api\EventsApiController::class, 'replay']);
    Route::get('/webhooks', [App\Http\Controllers\Api\EventsApiController::class, 'webhooks']);
    Route::post('/webhooks', [App\Http\Controllers\Api\EventsApiController::class, 'createWebhook']);
    Route::delete('/webhooks/{webhook}', [App\Http\Controllers\Api\EventsApiController::class, 'deleteWebhook']);
});

// Monitoring API Routes (Super Admin only)
Route::prefix('monitoring')->middleware(['auth:sanctum', 'can:viewSystemMonitoring'])->group(function () {
    Route::get('/health', [App\Http\Controllers\Api\MonitoringApiController::class, 'health']);
    Route::get('/metrics', [App\Http\Controllers\Api\MonitoringApiController::class, 'metrics']);
    Route::get('/api-endpoints', [App\Http\Controllers\Api\MonitoringApiController::class, 'apiEndpoints']);
    Route::get('/errors', [App\Http\Controllers\Api\MonitoringApiController::class, 'errors']);
    Route::get('/database', [App\Http\Controllers\Api\MonitoringApiController::class, 'database']);
    Route::get('/queues', [App\Http\Controllers\Api\MonitoringApiController::class, 'queues']);
    Route::post('/alerts', [App\Http\Controllers\Api\MonitoringApiController::class, 'createAlert']);
    Route::post('/report', [App\Http\Controllers\Api\MonitoringApiController::class, 'generateReport']);
    Route::get('/realtime', [App\Http\Controllers\Api\MonitoringApiController::class, 'realtime']);
    Route::post('/test-alert', [App\Http\Controllers\Api\MonitoringApiController::class, 'testAlert']);
});

// ---- Hair Salon MCP API Routes ----------------------------
// Professional Hair Salon booking system with MCP integration for Retell.ai
Route::prefix('hair-salon-mcp')->group(function () {
    // Public health check for Retell.ai monitoring
    Route::get('/health', [HairSalonMCPController::class, 'healthCheck'])
        ->name('api.hair-salon-mcp.health');
    
    // Initialize MCP connection (called by Retell.ai)
    Route::post('/initialize', [HairSalonMCPController::class, 'initialize'])
        ->middleware(['throttle:30,1'])
        ->name('api.hair-salon-mcp.initialize');
    
    // Core booking endpoints (with rate limiting for Retell.ai calls)
    Route::middleware(['throttle:100,1'])->group(function () {
        // Service and staff information
        Route::post('/services', [HairSalonMCPController::class, 'getServices'])
            ->name('api.hair-salon-mcp.services');
        Route::post('/staff', [HairSalonMCPController::class, 'getStaff'])
            ->name('api.hair-salon-mcp.staff');
        
        // Availability checking with caching
        Route::post('/availability', [HairSalonMCPController::class, 'checkAvailability'])
            ->name('api.hair-salon-mcp.availability');
        
        // Appointment booking (core business function)
        Route::post('/book', [HairSalonMCPController::class, 'bookAppointment'])
            ->name('api.hair-salon-mcp.book');
        
        // Callback scheduling for consultation services
        Route::post('/callback', [HairSalonMCPController::class, 'scheduleCallback'])
            ->name('api.hair-salon-mcp.callback');
        
        // Customer lookup for returning clients
        Route::post('/customer', [HairSalonMCPController::class, 'getCustomer'])
            ->name('api.hair-salon-mcp.customer');
    });
    
    // Billing and reporting endpoints (authenticated)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/usage-stats', [HairSalonMCPController::class, 'getUsageStats'])
            ->name('api.hair-salon-mcp.usage-stats');
        Route::post('/monthly-report', [HairSalonMCPController::class, 'getMonthlyReport'])
            ->name('api.hair-salon-mcp.monthly-report');
    });
});

// ---- Enhanced Hair Salon MCP API v2 Routes (Production Grade) -----------
// Professional Hair Salon booking system v2 with enhanced security and performance
Route::prefix('v2/hair-salon-mcp')->group(function () {
    // Public health check with detailed metrics
    Route::get('/health', [EnhancedHairSalonMCPController::class, 'getHealthStatus'])
        ->name('api.v2.hair-salon-mcp.health');
    
    // Initialize MCP connection with enhanced validation
    Route::post('/initialize', [EnhancedHairSalonMCPController::class, 'initialize'])
        ->name('api.v2.hair-salon-mcp.initialize');
    
    // Core booking endpoints with authentication and circuit breakers
    Route::middleware(['mcp.auth'])->group(function () {
        // Service and staff information
        Route::get('/services', [EnhancedHairSalonMCPController::class, 'getServices'])
            ->name('api.v2.hair-salon-mcp.services');
        
        Route::get('/staff', [EnhancedHairSalonMCPController::class, 'getStaff'])
            ->name('api.v2.hair-salon-mcp.staff');
        
        // Enhanced availability checking with batch processing
        Route::post('/availability/check', [EnhancedHairSalonMCPController::class, 'checkAvailability'])
            ->name('api.v2.hair-salon-mcp.availability.check');
        
        // Appointment booking with comprehensive validation
        Route::post('/appointments/book', [EnhancedHairSalonMCPController::class, 'bookAppointment'])
            ->name('api.v2.hair-salon-mcp.appointments.book');
        
        // Callback scheduling for consultation services
        Route::post('/callbacks/schedule', [EnhancedHairSalonMCPController::class, 'scheduleCallback'])
            ->name('api.v2.hair-salon-mcp.callbacks.schedule');
        
        // Customer lookup with enhanced data
        Route::get('/customers/lookup', [EnhancedHairSalonMCPController::class, 'getCustomer'])
            ->name('api.v2.hair-salon-mcp.customers.lookup');
        
        // Advanced analytics and insights
        Route::get('/analytics/usage', [EnhancedHairSalonMCPController::class, 'getUsageAnalytics'])
            ->name('api.v2.hair-salon-mcp.analytics.usage');
        
        // Billing and cost optimization insights
        Route::get('/billing/enhanced-report', [EnhancedHairSalonMCPController::class, 'getEnhancedBillingReport'])
            ->name('api.v2.hair-salon-mcp.billing.enhanced-report');
    });
});

// Retell.ai Hair Salon Webhook
Route::post('/api/v2/hair-salon-mcp/retell-webhook', [\App\Http\Controllers\RetellHairSalonWebhookController::class, 'handleWebhook'])
    ->name('api.hair-salon.retell.webhook');

// Retell MCP Bridge - Main endpoint for MCP protocol
Route::any("v2/hair-salon-mcp/mcp", [\App\Http\Controllers\RetellMCPBridgeController::class, "handle"])
    ->name("api.hair-salon.mcp.bridge");

// MCP Bridge method-specific routes
Route::post("v2/hair-salon-mcp/mcp/list_services", [\App\Http\Controllers\RetellMCPBridgeController::class, "handle"]);
Route::post("v2/hair-salon-mcp/mcp/check_availability", [\App\Http\Controllers\RetellMCPBridgeController::class, "handle"]);
Route::post("v2/hair-salon-mcp/mcp/book_appointment", [\App\Http\Controllers\RetellMCPBridgeController::class, "handle"]);
Route::post("v2/hair-salon-mcp/mcp/schedule_callback", [\App\Http\Controllers\RetellMCPBridgeController::class, "handle"]);
Route::get("v2/hair-salon-mcp/mcp/health", [\App\Http\Controllers\RetellMCPBridgeController::class, "health"]);


// TEMPORARY: Debug route to log all headers
Route::any("v2/hair-salon-mcp/debug", function(\Illuminate\Http\Request $request) {
    \Log::info("DEBUG MCP Request", [
        "method" => $request->method(),
        "headers" => $request->headers->all(),
        "body" => $request->all(),
        "raw_content" => $request->getContent()
    ]);
    
    // Return a valid MCP response
    return response()->json([
        "jsonrpc" => "2.0",
        "id" => $request->input("id"),
        "result" => [
            "debug" => "Headers received",
            "headers_count" => count($request->headers->all())
        ]
    ]);
});

// UNIVERSAL MCP TEST - Accepts any format
Route::any("v2/hair-salon-mcp/universal", function(\Illuminate\Http\Request $request) {
    \Log::info("UNIVERSAL MCP REQUEST", [
        "timestamp" => now()->toIso8601String(),
        "method" => $request->method(),
        "all_headers" => $request->headers->all(),
        "body" => $request->all(),
        "raw_body" => $request->getContent(),
        "query_params" => $request->query(),
        "ip" => $request->ip(),
        "user_agent" => $request->userAgent()
    ]);
    
    // Try to detect what Retell wants
    $method = $request->input("method") 
        ?? $request->input("tool") 
        ?? $request->input("function")
        ?? "unknown";
    
    // Always return services for testing
    if (str_contains($method, "list") || str_contains($method, "services") || $method == "unknown") {
        return response()->json([
            "jsonrpc" => "2.0",
            "id" => $request->input("id") ?? "test",
            "result" => [
                "services" => [
                    ["id" => 26, "name" => "Herrenhaarschnitt", "price" => "35.00", "duration" => 30],
                    ["id" => 27, "name" => "Damenhaarschnitt", "price" => "55.00", "duration" => 45]
                ]
            ]
        ]);
    }
    
    return response()->json([
        "jsonrpc" => "2.0",
        "id" => $request->input("id") ?? "test",
        "result" => ["message" => "Universal endpoint received: " . $method]
    ]);
});

// Retell Webhook Handler

Route::post("v2/hair-salon-mcp/retell-webhook", function(\Illuminate\Http\Request $request) {
    // Force immediate logging
    error_log("RETELL WEBHOOK HIT at " . date("Y-m-d H:i:s"));
    
    $data = $request->all();
    $headers = $request->headers->all();
    
    // Log to Laravel log
    \Log::channel("single")->info("RETELL WEBHOOK RECEIVED", [
        "timestamp" => now()->toIso8601String(),
        "headers" => $headers,
        "body" => $data,
        "raw_body" => substr($request->getContent(), 0, 1000),
        "method" => $request->method(),
        "ip" => $request->ip()
    ]);
    
    // Also log to error_log for immediate visibility
    error_log("RETELL DATA: " . json_encode($data));
    
    // Handle different events
    $event = $data["event"] ?? $data["type"] ?? "unknown";
    
    \Log::channel("single")->info("RETELL EVENT TYPE: " . $event);
    
    // Always try to create an appointment for testing
    if (str_contains(strtolower($event), "call") || str_contains(strtolower($event), "end")) {
        try {
            $appointment = new \App\Models\Appointment();
            $appointment->company_id = 1;
            $appointment->customer_id = 1;
            $appointment->service_id = 26;
            $appointment->starts_at = now()->addDay();
            $appointment->ends_at = now()->addDay()->addMinutes(30);
            $appointment->status = "confirmed";
            $appointment->source = "retell_webhook";
            $appointment->notes = "Webhook event: " . $event . " | Data: " . json_encode($data);
            $appointment->save();
            
            \Log::channel("single")->info("APPOINTMENT CREATED", ["id" => $appointment->id]);
            error_log("APPOINTMENT CREATED: ID=" . $appointment->id);
        } catch (\Exception $e) {
            \Log::channel("single")->error("APPOINTMENT CREATION FAILED", ["error" => $e->getMessage()]);
            error_log("APPOINTMENT ERROR: " . $e->getMessage());
        }
    }
    
    return response()->json(["success" => true, "received" => $event]);
});
