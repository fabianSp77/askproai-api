<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TEMPORARY: Bypass controller for debugging Retell webhooks
 * 
 * WARNING: This bypasses signature verification - ONLY USE FOR DEBUGGING!
 */
class RetellWebhookBypassController extends Controller
{
    public function handle(Request $request)
    {
        // Log full webhook details
        Log::warning('[RETELL WEBHOOK BYPASS] Incoming webhook', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'body' => $request->getContent(),
            'json' => $request->all(),
        ]);
        
        // Process the webhook
        try {
            $controller = app(RetellWebhookController::class);
            
            // Fake the webhook validation
            $request->merge(['webhook_validated' => true]);
            
            return $controller->processWebhook($request);
        } catch (\Exception $e) {
            Log::error('[RETELL WEBHOOK BYPASS] Processing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
}