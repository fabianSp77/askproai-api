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
    ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('retell'))
    ->name('retell.webhook.secure');

// ---- Cal.com Webhook (with signature verification) ----
Route::post('/calcom/webhook', [CalcomWebhookController::class, 'handle'])
    ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('calcom'))
    ->name('calcom.webhook.secure');

// ---- Stripe Webhook (with signature verification) ----
Route::post('/stripe/webhook', [App\Http\Controllers\Api\StripeWebhookController::class, 'handle'])
    ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('stripe'))
    ->name('stripe.webhook.secure');

// ---- Unified Webhook Handler (auto-detects source) ----
Route::post('/webhook', [UnifiedWebhookController::class, 'handle'])
    ->middleware(\App\Services\WebhookSecurityService::getMiddlewareStack('unified'))
    ->name('webhook.unified.secure');

// ---- MCP Webhook Handler (requires authentication) ----
Route::prefix('mcp')->middleware(['auth:sanctum', 'validate.company.context'])->group(function () {
    Route::post('/webhook/retell', [\App\Http\Controllers\Api\MCPWebhookController::class, 'handleRetell'])
        ->middleware(['verify.retell.signature'])
        ->name('mcp.webhook.retell.secure');
});

// ---- Health check for webhooks (public) ----
Route::get('/webhook/health', [UnifiedWebhookController::class, 'health'])
    ->middleware(\App\Services\WebhookSecurityService::getHealthCheckMiddleware())
    ->name('webhook.health');