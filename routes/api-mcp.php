<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RetellWebhookMCPController;
use App\Http\Middleware\VerifyRetellSignature;

/*
|--------------------------------------------------------------------------
| MCP-based API Routes
|--------------------------------------------------------------------------
|
| These routes use the new MCP (Modular Component Pattern) architecture
| for better modularity, error handling, and tenant isolation.
|
*/

Route::prefix('mcp')->group(function () {
    
    // Retell Webhook Routes (MCP Version)
    Route::prefix('retell')->middleware([
        'throttle:webhook',
        VerifyRetellSignature::class
    ])->group(function () {
        
        // Main webhook endpoint
        Route::post('/webhook', [RetellWebhookMCPController::class, 'processWebhook'])
            ->name('mcp.retell.webhook');
            
        // Alternative endpoint for backward compatibility
        Route::post('/events', [RetellWebhookMCPController::class, 'processWebhook'])
            ->name('mcp.retell.events');
    });
    
});

/*
|--------------------------------------------------------------------------
| Migration Helper Routes
|--------------------------------------------------------------------------
|
| These routes help with gradual migration from old to MCP-based endpoints
|
*/

if (config('features.mcp_migration_mode', false)) {
    // Mirror old routes to new MCP controllers
    Route::post('/retell/webhook', function (\Illuminate\Http\Request $request) {
        // Log migration usage
        \Log::info('Legacy route used, redirecting to MCP', [
            'route' => '/retell/webhook',
            'ip' => $request->ip()
        ]);
        
        // Forward to MCP controller
        return app(RetellWebhookMCPController::class)->processWebhook(
            app(\App\Http\Requests\Webhook\RetellWebhookRequest::class)
        );
    })->middleware([
        'throttle:webhook',
        VerifyRetellSignature::class
    ]);
}