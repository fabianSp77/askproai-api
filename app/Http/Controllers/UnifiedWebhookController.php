<?php

namespace App\Http\Controllers;

use App\Services\WebhookProcessor;
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
            // Detect provider from request path or headers
            $provider = $this->detectProvider($request);
            
            if (!$provider) {
                return response()->json([
                    'error' => 'Unable to detect webhook provider',
                    'message' => 'Please use provider-specific endpoints like /webhooks/retell'
                ], 400);
            }
            
            // Process webhook with detected provider
            $result = $this->processor->process(
                $provider,
                $request->all(),
                $request->headers->all(),
                null // Let processor generate correlation ID
            );
            
            return response()->json($result, 200);
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            return response()->json([
                'error' => 'Invalid webhook signature',
                'message' => $e->getMessage(),
                'provider' => $provider ?? 'unknown'
            ], 401);
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
     * Detect webhook provider from request
     */
    private function detectProvider(Request $request): ?string
    {
        // Check URL path
        if (str_contains($request->path(), 'retell')) {
            return 'retell';
        }
        if (str_contains($request->path(), 'calcom')) {
            return 'calcom';
        }
        if (str_contains($request->path(), 'stripe')) {
            return 'stripe';
        }
        
        // Check headers for provider signatures
        if ($request->hasHeader('x-retell-signature')) {
            return 'retell';
        }
        if ($request->hasHeader('x-cal-signature-256') || $request->hasHeader('cal-signature-256')) {
            return 'calcom';
        }
        if ($request->hasHeader('stripe-signature')) {
            return 'stripe';
        }
        
        // Check payload for provider-specific fields
        $payload = $request->all();
        if (isset($payload['event']) && isset($payload['call'])) {
            return 'retell';
        }
        if (isset($payload['triggerEvent'])) {
            return 'calcom';
        }
        if (isset($payload['type']) && str_starts_with($payload['id'] ?? '', 'evt_')) {
            return 'stripe';
        }
        
        return null;
    }
    
    /**
     * Health check endpoint for webhook processing
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'providers' => ['retell', 'calcom', 'stripe'],
            'features' => [
                'deduplication' => true,
                'retry_logic' => true,
                'database_logging' => true,
                'mcp_architecture' => true
            ],
            'timestamp' => now()->toIso8601String()
        ]);
    }
}