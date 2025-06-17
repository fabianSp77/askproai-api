<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WebhookProcessor;
use App\Models\WebhookEvent;
use App\Models\Company;
use App\Models\Call;
use App\Services\RetellService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetellInboundWebhookController extends Controller
{
    protected WebhookProcessor $webhookProcessor;
    
    public function __construct(WebhookProcessor $webhookProcessor)
    {
        $this->webhookProcessor = $webhookProcessor;
    }
    
    public function __invoke(Request $request)
    {
        $correlationId = $request->input('correlation_id') ?? app('correlation_id');
        $payload = $request->json()->all();
        $headers = $request->headers->all();
        
        try {
            // Verify signature before processing
            $this->webhookProcessor->verifySignature(
                WebhookEvent::PROVIDER_RETELL,
                $request->getContent(),
                $headers
            );
            
            // For inbound calls, we need to respond synchronously
            // So we process immediately instead of queuing
            $callData = [
                'retell_call_id' => $payload['call_id'] ?? Str::uuid(),
                'from_number'    => data_get($payload, 'call_inbound.from_number'),
                'to_number'      => data_get($payload, 'call_inbound.to_number'),
                'raw'            => $payload,
                'company_id'     => $this->resolveCompanyId($payload)
            ];
            
            // Create call record
            $call = Call::create($callData);
            
            // Log the webhook for tracking
            $this->webhookProcessor->logWebhookEvent(
                WebhookEvent::PROVIDER_RETELL,
                'call_inbound',
                $payload,
                $headers,
                $correlationId
            );
            
            // Get appropriate agent ID
            $company = Company::find($call->company_id);
            $agentId = $company->retell_agent_id ?? config('services.retell.default_agent_id', 'agent_9a8202a740cd3120d96fcfda1e');
            
            // Return synchronous response for Retell
            return response()->json(
                RetellService::buildInboundResponse(
                    $agentId,
                    $call->from_number
                )
            );
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('Retell inbound webhook signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            // Return basic response even on signature failure to not drop the call
            return response()->json(
                RetellService::buildInboundResponse(
                    config('services.retell.default_agent_id', 'agent_9a8202a740cd3120d96fcfda1e'),
                    data_get($payload, 'call_inbound.from_number', 'unknown')
                )
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell inbound webhook', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return basic response to handle the call
            return response()->json(
                RetellService::buildInboundResponse(
                    config('services.retell.default_agent_id', 'agent_9a8202a740cd3120d96fcfda1e'),
                    data_get($payload, 'call_inbound.from_number', 'unknown')
                )
            );
        }
    }
    
    /**
     * Resolve company ID from the webhook payload
     */
    private function resolveCompanyId(array $payload): ?int
    {
        // Try to find company by to_number
        $toNumber = data_get($payload, 'call_inbound.to_number');
        if ($toNumber) {
            $company = Company::where('phone_number', $toNumber)->first();
            if ($company) {
                return $company->id;
            }
        }
        
        // Fallback to first company
        $firstCompany = Company::first();
        return $firstCompany ? $firstCompany->id : null;
    }
}
