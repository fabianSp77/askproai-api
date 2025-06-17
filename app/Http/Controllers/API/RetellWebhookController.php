<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WebhookProcessor;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellWebhookController extends Controller
{
    protected WebhookProcessor $webhookProcessor;
    
    public function __construct(WebhookProcessor $webhookProcessor)
    {
        $this->webhookProcessor = $webhookProcessor;
    }
    
    public function __invoke(Request $request): Response
    {
        $correlationId = $request->input('correlation_id') ?? app('correlation_id');
        $payload = $request->all();
        $headers = $request->headers->all();
        
        try {
            // Process webhook through the WebhookProcessor service
            $result = $this->webhookProcessor->process(
                WebhookEvent::PROVIDER_RETELL,
                $payload,
                $headers,
                $correlationId
            );
            
            // Return appropriate response based on processing result
            if ($result['duplicate']) {
                Log::info('Retell webhook already processed', [
                    'correlation_id' => $correlationId
                ]);
            }
            
            // Retell expects a 204 No Content response
            return response()->noContent();
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('Retell webhook signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            // Still return 204 to avoid retries, but log the error
            return response()->noContent();
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell webhook', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return 204 to avoid webhook retries from Retell
            return response()->noContent();
        }
    }
}
