<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnifiedWebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Unified webhook handling for all external services
|
*/

Route::prefix('webhooks')->group(function () {
    // Unified webhook endpoint - auto-detects source
    Route::post('/', [UnifiedWebhookController::class, 'handle'])
        ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('unified'))
        ->name('webhooks.unified');
    
    // Legacy routes for backward compatibility (with proper security)
    Route::post('/calcom', [UnifiedWebhookController::class, 'handle'])
        ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('calcom'))
        ->name('webhooks.calcom');
    
    Route::post('/retell', [UnifiedWebhookController::class, 'handle'])
        ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('retell'))
        ->name('webhooks.retell');
    
    Route::post('/stripe', [UnifiedWebhookController::class, 'handle'])
        ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('stripe'))
        ->name('webhooks.stripe');
    
    // Health check
    Route::get('/health', [UnifiedWebhookController::class, 'health'])
        ->middleware(\App\Services\WebhookSecurityService::getHealthCheckMiddleware())
        ->name('webhooks.health');
});