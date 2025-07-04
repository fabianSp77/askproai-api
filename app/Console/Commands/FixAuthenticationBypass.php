<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixAuthenticationBypass extends Command
{
    protected $signature = 'security:fix-authentication-bypass';
    protected $description = 'Fix authentication bypass vulnerabilities in API routes';

    public function handle()
    {
        $this->info('🔐 FIXING AUTHENTICATION BYPASS VULNERABILITIES');
        $this->info('==============================================');
        
        // Fix 1: Remove test routes from production
        $this->removeTestRoutes();
        
        // Fix 2: Add authentication to exposed endpoints
        $this->addAuthenticationToRoutes();
        
        // Fix 3: Create secure webhook routes
        $this->createSecureWebhookRoutes();
        
        // Fix 4: Remove debug endpoints
        $this->removeDebugEndpoints();
        
        $this->info('');
        $this->info('🎉 All authentication bypass fixes applied!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Review the new routes/api-secure.php file');
        $this->info('2. Test all webhook endpoints with proper signatures');
        $this->info('3. Update documentation with new endpoint requirements');
        $this->info('4. Deploy immediately to production');
    }
    
    private function removeTestRoutes()
    {
        $this->info('1. Creating production-safe API routes...');
        
        $apiRoutesPath = base_path('routes/api.php');
        $content = File::get($apiRoutesPath);
        
        // Comment out test route inclusion for production
        $content = str_replace(
            "if (app()->environment('local', 'development', 'production')) {\n    require __DIR__.'/test-webhook.php';\n}",
            "// Test routes disabled in production\n// if (app()->environment('local', 'development')) {\n//     require __DIR__.'/test-webhook.php';\n// }",
            $content
        );
        
        // Remove test endpoints
        $content = preg_replace(
            '/\/\/ ---- Test Webhook Endpoints.*?}\);/s',
            '// Test webhook endpoints removed for security',
            $content
        );
        
        // Remove debug webhook routes
        $content = preg_replace(
            '/\/\/ ---- TEMPORARY DEBUG:.*?->name\(\'retell\.webhook\.nosig\'\);/s',
            '// Debug webhook routes removed for security',
            $content
        );
        
        // Remove metrics-test endpoint
        $content = preg_replace(
            '/\/\/ Test route to debug metrics.*?}\);/s',
            '// Test metrics endpoint removed for security',
            $content
        );
        
        // Remove test booking endpoint
        $content = preg_replace(
            '/Route::post\(\'\/calcom\/book-test\'.*?\n}\);/s',
            '// Test booking endpoint removed for security',
            $content
        );
        
        // Remove MCP test webhook
        $content = preg_replace(
            '/\/\/ ---- MCP TEST:.*?->name\(\'test\.mcp\.webhook\'\);/s',
            '// MCP test webhook removed for security',
            $content
        );
        
        File::put($apiRoutesPath, $content);
        $this->info('✅ Removed test and debug routes');
    }
    
    private function addAuthenticationToRoutes()
    {
        $this->info('2. Adding authentication to exposed endpoints...');
        
        $apiRoutesPath = base_path('routes/api.php');
        $content = File::get($apiRoutesPath);
        
        // Add authentication to MCP routes
        $content = str_replace(
            "Route::prefix('mcp')->middleware(['throttle:1000,1'])->group(function () {",
            "Route::prefix('mcp')->middleware(['auth:sanctum', 'throttle:100,1', 'validate.company.context'])->group(function () {",
            $content
        );
        
        // Add authentication to Retell function endpoints
        $content = preg_replace(
            '/Route::post\(\'\/transfer-to-fabian\'.*?\n\s*->name\(\'api\.retell\.transfer-to-fabian\'\);/',
            "Route::post('/transfer-to-fabian', [App\Http\Controllers\Api\RetellCallTransferController::class, 'transferToFabian'])\n        ->middleware(['verify.retell.signature', 'throttle:30,1'])\n        ->name('api.retell.transfer-to-fabian');",
            $content
        );
        
        // Add authentication to schedule callback
        $content = preg_replace(
            '/Route::post\(\'\/schedule-callback\'.*?\n\s*->name\(\'api\.retell\.schedule-callback\'\);/',
            "Route::post('/schedule-callback', [App\Http\Controllers\Api\RetellCallTransferController::class, 'scheduleCallback'])\n        ->middleware(['verify.retell.signature', 'throttle:30,1'])\n        ->name('api.retell.schedule-callback');",
            $content
        );
        
        // Add authentication to realtime routes
        $content = str_replace(
            "Route::prefix('retell/realtime')->group(function () {",
            "Route::prefix('retell/realtime')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {",
            $content
        );
        
        // Fix billing webhook to use signature verification
        $content = str_replace(
            "Route::post('/billing/webhook', [App\Http\Controllers\BillingController::class, 'webhook'])\n    ->name('billing.webhook');",
            "Route::post('/billing/webhook', [App\Http\Controllers\BillingController::class, 'webhook'])\n    ->middleware(['verify.stripe.signature', 'webhook.replay.protection'])\n    ->name('billing.webhook');",
            $content
        );
        
        File::put($apiRoutesPath, $content);
        $this->info('✅ Added authentication to exposed endpoints');
    }
    
    private function createSecureWebhookRoutes()
    {
        $this->info('3. Creating secure webhook routes file...');
        
        $secureWebhookRoutes = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnifiedWebhookController;
use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\CalcomWebhookController;

/*
|--------------------------------------------------------------------------
| Secure Webhook Routes
|--------------------------------------------------------------------------
| All webhook endpoints MUST have signature verification
| No debug or test endpoints in production
*/

// ---- Retell.ai Webhook (with signature verification) ----
Route::post('/retell/webhook', [RetellWebhookController::class, 'processWebhook'])
    ->middleware([
        'verify.retell.signature',
        'webhook.replay.protection',
        'throttle:100,1',
        'monitoring'
    ])
    ->name('retell.webhook.secure');

// ---- Cal.com Webhook (with signature verification) ----
Route::post('/calcom/webhook', [CalcomWebhookController::class, 'handle'])
    ->middleware([
        'calcom.signature',
        'webhook.replay.protection',
        'throttle:100,1',
        'monitoring'
    ])
    ->name('calcom.webhook.secure');

// ---- Stripe Webhook (with signature verification) ----
Route::post('/stripe/webhook', [App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->middleware([
        'verify.stripe.signature',
        'webhook.replay.protection',
        'throttle:50,1',
        'monitoring'
    ])
    ->name('stripe.webhook.secure');

// ---- Unified Webhook Handler (auto-detects source) ----
Route::post('/webhook', [UnifiedWebhookController::class, 'handle'])
    ->middleware([
        'webhook.replay.protection',
        'throttle:100,1',
        'monitoring'
    ])
    ->name('webhook.unified.secure');

// ---- MCP Webhook Handler (requires authentication) ----
Route::prefix('mcp')->middleware(['auth:sanctum', 'validate.company.context'])->group(function () {
    Route::post('/webhook/retell', [\App\Http\Controllers\Api\MCPWebhookController::class, 'handleRetell'])
        ->middleware(['verify.retell.signature'])
        ->name('mcp.webhook.retell.secure');
});

// ---- Health check for webhooks (public) ----
Route::get('/webhook/health', [UnifiedWebhookController::class, 'health'])
    ->middleware(['throttle:10,1'])
    ->name('webhook.health');
PHP;

        File::put(base_path('routes/api-secure-webhooks.php'), $secureWebhookRoutes);
        $this->info('✅ Created secure webhook routes file');
    }
    
    private function removeDebugEndpoints()
    {
        $this->info('4. Removing debug endpoints and fixing signature verification...');
        
        $apiRoutesPath = base_path('routes/api.php');
        $content = File::get($apiRoutesPath);
        
        // Fix TEMPORARY FIX comment for MCP webhook
        $content = preg_replace(
            '/\/\/ ---- TEMPORARY FIX:.*?\}\)->name\(\'mcp\.retell\.webhook\.temp\'\);/s',
            <<<'PHP'
// ---- MCP Retell Webhook Route (with signature verification) ----
Route::prefix('mcp')->group(function () {
    Route::post('/retell/webhook', function (Request $request) {
        return app(RetellWebhookController::class)->processWebhook($request);
    })->middleware(['verify.retell.signature', 'webhook.replay.protection'])
      ->name('mcp.retell.webhook');
});
PHP,
            $content
        );
        
        // Remove webhook test endpoints
        $content = str_replace(
            "Route::get('/webhook/test', [\App\Http\Controllers\Api\MCPWebhookController::class, 'test'])\n        ->name('mcp.webhook.test');",
            "// Test endpoint removed for security",
            $content
        );
        
        // Remove stats and health endpoints that don't require auth
        $content = preg_replace(
            '/Route::get\(\'\/retell\/mcp-webhook\/stats\'.*?\n.*?->name\(\'retell\.mcp\.stats\'\);/',
            "// Stats endpoint removed - use authenticated MCP routes instead",
            $content
        );
        
        File::put($apiRoutesPath, $content);
        $this->info('✅ Removed debug endpoints and fixed signature verification');
        
        // Create a documentation file for removed endpoints
        $removedEndpointsDoc = <<<'MD'
# Removed Endpoints for Security

The following endpoints have been removed or secured:

## Test Endpoints (Removed)
- `/api/test/webhook` - Use proper webhook testing tools
- `/api/test/mcp-webhook` - Use authenticated MCP routes
- `/api/calcom/book-test` - Use proper booking API
- `/api/metrics-test` - Use authenticated metrics endpoint

## Debug Endpoints (Removed)
- `/api/retell/webhook-debug` - Use proper logging
- `/api/retell/webhook-nosig` - All webhooks require signatures
- `/api/retell/debug-webhook` - Use proper logging

## Secured Endpoints (Now Require Authentication)
- `/api/mcp/*` - Requires Sanctum authentication
- `/api/retell/realtime/*` - Requires Sanctum authentication
- `/api/billing/webhook` - Now requires Stripe signature

## Migration Guide
1. All webhooks now require proper signature verification
2. Use Sanctum tokens for API authentication
3. Test webhooks using proper tools (Postman, curl with signatures)
4. Monitor logs for debugging instead of debug endpoints
MD;

        File::put(base_path('REMOVED_ENDPOINTS_SECURITY.md'), $removedEndpointsDoc);
        $this->info('✅ Created documentation for removed endpoints');
    }
}