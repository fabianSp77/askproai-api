<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RetellWebhookController;

/*
|--------------------------------------------------------------------------
| Test Webhook Routes (No Signature Verification)
|--------------------------------------------------------------------------
|
| These routes are for testing only and bypass signature verification.
| They should be removed in production!
|
*/

// Test webhook without signature verification
Route::post('/api/retell/webhook-test', [RetellWebhookController::class, 'handle'])
    ->name('retell.webhook.test')
    ->withoutMiddleware([\App\Http\Middleware\VerifyRetellSignature::class]);

// Debug endpoint to check recent webhooks
Route::get('/api/retell/webhook-debug', function () {
    if (!app()->environment('local', 'development')) {
        abort(403, 'Debug endpoint not available in production');
    }
    
    $recentWebhooks = \App\Models\WebhookEvent::withoutGlobalScopes()
        ->where('provider', 'retell')
        ->where('created_at', '>', now()->subHour())
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    return response()->json([
        'recent_webhooks' => $recentWebhooks->map(function ($webhook) {
            return [
                'id' => $webhook->id,
                'event_type' => $webhook->event_type,
                'status' => $webhook->status,
                'created_at' => $webhook->created_at->toIso8601String(),
                'payload' => json_decode($webhook->payload, true)
            ];
        }),
        'count' => $recentWebhooks->count(),
        'test_webhook_url' => url('/api/retell/webhook-test')
    ]);
});