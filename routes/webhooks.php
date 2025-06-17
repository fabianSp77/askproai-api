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
        ->name('webhooks.unified');
    
    // Legacy routes for backward compatibility
    Route::post('/calcom', [UnifiedWebhookController::class, 'handle'])
        ->name('webhooks.calcom');
    
    Route::post('/retell', [UnifiedWebhookController::class, 'handle'])
        ->name('webhooks.retell');
    
    Route::post('/stripe', [UnifiedWebhookController::class, 'handle'])
        ->name('webhooks.stripe');
    
    // Health check
    Route::get('/health', [UnifiedWebhookController::class, 'health'])
        ->name('webhooks.health');
});