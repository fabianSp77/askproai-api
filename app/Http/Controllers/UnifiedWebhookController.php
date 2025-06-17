<?php

namespace App\Http\Controllers;

use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnifiedWebhookController extends Controller
{
    private WebhookProcessor $processor;
    
    public function __construct(WebhookProcessor $processor)
    {
        $this->processor = $processor;
    }
    
    /**
     * Handle incoming webhook from any source
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $result = $this->processor->process($request);
            
            return response()->json($result, 200);
            
        } catch (\App\Exceptions\WebhookException $e) {
            // WebhookException handles its own response
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            \Log::error('Unexpected webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Internal server error',
                'message' => app()->environment('production') 
                    ? 'An error occurred processing the webhook' 
                    : $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Health check endpoint for webhook processing
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'strategies' => $this->processor->getStrategies(),
            'timestamp' => now()->toIso8601String()
        ]);
    }
}