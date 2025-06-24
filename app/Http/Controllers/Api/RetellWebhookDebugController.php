<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Webhooks\RetellWebhookHandler;

class RetellWebhookDebugController extends Controller
{
    /**
     * Handle Retell webhook WITHOUT signature verification (TEMPORARY FOR DEBUGGING)
     * 
     * WARNING: This bypasses security! Only use for testing!
     */
    public function handle(Request $request)
    {
        Log::warning('RETELL DEBUG WEBHOOK - NO SIGNATURE VERIFICATION', [
            'url' => $request->fullUrl(),
            'event' => $request->input('event'),
            'call_id' => $request->input('call.call_id'),
            'headers' => $request->headers->all()
        ]);
        
        try {
            // Get webhook handler
            $handler = app(RetellWebhookHandler::class);
            
            // Create a mock webhook event
            $webhookEvent = new \App\Models\WebhookEvent();
            $webhookEvent->provider = 'retell';
            $webhookEvent->type = 'webhook';
            $webhookEvent->source = 'retell';
            $webhookEvent->event = $request->input('event', 'unknown');
            $webhookEvent->payload = $request->all();
            $webhookEvent->company_id = 1; // Default to company 1 for testing
            $webhookEvent->save();
            
            // Process based on event type
            $eventType = $request->input('event');
            $result = match($eventType) {
                'call_started' => $handler->handleCallStarted($webhookEvent, $webhookEvent->correlation_id),
                'call_ended' => $handler->handleCallEnded($webhookEvent, $webhookEvent->correlation_id),
                'call_analyzed' => $handler->handleCallAnalyzed($webhookEvent, $webhookEvent->correlation_id),
                'call_inbound' => $handler->handleCallInbound($webhookEvent, $webhookEvent->correlation_id),
                default => ['error' => 'Unknown event type: ' . $eventType]
            };
            
            Log::info('RETELL DEBUG WEBHOOK - Processed', [
                'event' => $eventType,
                'result' => $result
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed (DEBUG MODE)',
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('RETELL DEBUG WEBHOOK - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Still return success to prevent retries
            return response()->json([
                'success' => true,
                'message' => 'Webhook received (error occurred)',
                'error' => $e->getMessage()
            ]);
        }
    }
}