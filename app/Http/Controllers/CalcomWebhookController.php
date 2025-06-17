<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessCalcomWebhookJob;

class CalcomWebhookController extends Controller
{
    /**
     * Handle ping request from Cal.com
     */
    public function ping(Request $request)
    {
        $secret = config('services.calcom.webhook_secret');
        
        if (!$secret) {
            return response()->json([
                'ok' => false,
                'status' => 500,
                'message' => 'Cal.com secret missing'
            ], 500);
        }
        
        return response()->json([
            'ok' => true,
            'status' => 200,
            'message' => 'Webhook is ready'
        ]);
    }
    
    /**
     * Handle incoming webhook from Cal.com
     */
    public function handle(Request $request)
    {
        // Log incoming webhook
        Log::info('Cal.com webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);
        
        try {
            $payload = $request->all();
            
            // Cal.com sendet verschiedene Event-Typen
            $triggerEvent = $payload['triggerEvent'] ?? null;
            
            if (!$triggerEvent) {
                Log::warning('Cal.com webhook missing triggerEvent', $payload);
                return response()->json(['error' => 'Missing triggerEvent'], 400);
            }
            
            // Dispatch job for async processing
            ProcessCalcomWebhookJob::dispatch($triggerEvent, $payload);
            
            // Cal.com erwartet eine schnelle Response
            return response()->json(['status' => 'accepted'], 200);
            
        } catch (\Exception $e) {
            Log::error('Cal.com webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
