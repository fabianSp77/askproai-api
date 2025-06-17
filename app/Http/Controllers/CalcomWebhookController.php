<?php

namespace App\Http\Controllers;

use App\Services\WebhookProcessor;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalcomWebhookController extends Controller
{
    protected WebhookProcessor $webhookProcessor;
    
    public function __construct(WebhookProcessor $webhookProcessor)
    {
        $this->webhookProcessor = $webhookProcessor;
    }
    
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
     * Handle incoming webhook from Cal.com using WebhookProcessor
     */
    public function handle(Request $request)
    {
        $correlationId = $request->input('correlation_id') ?? app('correlation_id');
        $payload = $request->all();
        $headers = $request->headers->all();
        
        try {
            // Validate required fields
            $triggerEvent = $payload['triggerEvent'] ?? null;
            
            if (!$triggerEvent) {
                Log::warning('Cal.com webhook missing triggerEvent', [
                    'payload' => $payload,
                    'correlation_id' => $correlationId
                ]);
                return response()->json(['error' => 'Missing triggerEvent'], 400);
            }
            
            // Process webhook through the WebhookProcessor service
            $result = $this->webhookProcessor->process(
                WebhookEvent::PROVIDER_CALCOM,
                $payload,
                $headers,
                $correlationId
            );
            
            // Return appropriate response
            if ($result['duplicate']) {
                return response()->json([
                    'status' => 'duplicate',
                    'message' => 'Webhook already processed'
                ], 200);
            }
            
            return response()->json([
                'status' => 'accepted',
                'correlation_id' => $correlationId
            ], 200);
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('Cal.com webhook signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return response()->json([
                'error' => 'Invalid signature',
                'message' => $e->getMessage()
            ], 401);
            
        } catch (\Exception $e) {
            Log::error('Cal.com webhook error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}
