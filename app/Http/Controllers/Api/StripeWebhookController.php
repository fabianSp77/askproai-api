<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookProcessor;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    protected WebhookProcessor $webhookProcessor;

    public function __construct(WebhookProcessor $webhookProcessor)
    {
        $this->webhookProcessor = $webhookProcessor;
    }

    /**
     * Handle Stripe webhook using WebhookProcessor.
     */
    public function handle(Request $request)
    {
        $correlationId = $request->input('correlation_id') ?? app('correlation_id');
        
        // Parse the raw JSON payload since Stripe sends raw JSON
        $payload = json_decode($request->getContent(), true);
        
        if (!$payload) {
            Log::error('Invalid Stripe webhook payload', [
                'correlation_id' => $correlationId
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }
        
        $headers = $request->headers->all();
        
        try {
            // Process webhook through the WebhookProcessor service
            $result = $this->webhookProcessor->process(
                WebhookEvent::PROVIDER_STRIPE,
                $payload,
                $headers,
                $correlationId
            );
            
            // Return appropriate response
            if ($result['duplicate']) {
                return response()->json([
                    'status' => 'duplicate',
                    'message' => 'Webhook already processed'
                ]);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 400);
            
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return success to prevent Stripe from retrying
            return response()->json(['status' => 'error logged']);
        }
    }
}