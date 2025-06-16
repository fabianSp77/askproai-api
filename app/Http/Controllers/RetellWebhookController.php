<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRetellWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RetellWebhookController extends Controller
{
    /**
     * Process Retell webhook asynchronously
     */
    public function processWebhook(Request $request)
    {
        // Log incoming webhook for debugging
        Log::info('Retell webhook received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        try {
            $eventType = $request->input('event');
            
            // Route to appropriate job based on event type
            switch ($eventType) {
                case 'call_ended':
                    // Use new comprehensive job for call_ended events
                    \App\Jobs\ProcessRetellCallEndedJob::dispatch($request->all())
                        ->onQueue('webhooks')
                        ->delay(now()->addSeconds(1));
                    
                    Log::info('Dispatched ProcessRetellCallEndedJob for call_ended event');
                    break;
                    
                case 'call_started':
                case 'call_analyzed':
                case 'call_inbound':
                case 'call_outbound':
                default:
                    // Use legacy job for other events or backward compatibility
                    ProcessRetellWebhookJob::dispatch($request->all())
                        ->onQueue('webhooks')
                        ->delay(now()->addSeconds(1));
                    
                    Log::info('Dispatched ProcessRetellWebhookJob for event: ' . ($eventType ?? 'unknown'));
                    break;
            }
            
            // Return immediate response
            return response()->json([
                'success' => true,
                'message' => 'Webhook received and queued for processing'
            ], 202); // 202 Accepted
            
        } catch (\Exception $e) {
            Log::error('Failed to queue Retell webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Still return success to avoid webhook retries
            return response()->json([
                'success' => true,
                'message' => 'Webhook received'
            ], 200);
        }
    }
}