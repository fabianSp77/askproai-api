<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRetellWebhookJob;
use App\Services\Webhook\WebhookDeduplication;
use App\Services\RateLimiter\EnhancedRateLimiter;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Optimized Retell Webhook Controller
 * 
 * Performance improvements:
 * - Async processing for all non-critical events
 * - Redis-based deduplication with atomic operations
 * - Enhanced rate limiting
 * - Minimal processing in request cycle
 */
class OptimizedRetellWebhookController extends Controller
{
    private WebhookDeduplication $deduplication;
    private EnhancedRateLimiter $rateLimiter;
    
    public function __construct(
        WebhookDeduplication $deduplication,
        EnhancedRateLimiter $rateLimiter
    ) {
        $this->deduplication = $deduplication;
        $this->rateLimiter = $rateLimiter;
    }
    
    /**
     * Process webhook with optimized async handling
     * Target: < 50ms response time
     */
    public function processWebhook(Request $request)
    {
        $startTime = microtime(true);
        $correlationId = Str::uuid()->toString();
        
        try {
            // 1. Rate limiting check (< 1ms)
            $rateLimitKey = 'retell:' . $request->ip();
            if (!$this->rateLimiter->attempt($rateLimitKey, 100, 60)) {
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }
            
            // 2. Extract minimal data (< 1ms)
            $event = $request->input('event');
            $callId = $request->input('call.id') ?? $request->input('call_id');
            
            // 3. Deduplication check (< 5ms with Redis)
            $dedupeKey = "webhook:retell:{$event}:{$callId}";
            if (!$this->deduplication->checkAndSet($dedupeKey, 3600)) {
                Log::info('Duplicate webhook ignored', [
                    'event' => $event,
                    'call_id' => $callId,
                    'correlation_id' => $correlationId
                ]);
                
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'correlation_id' => $correlationId
                ], 200);
            }
            
            // 4. Handle critical events synchronously
            if ($event === 'call_inbound') {
                return $this->handleInboundCallOptimized($request, $correlationId);
            }
            
            // 5. Queue non-critical events (< 10ms)
            ProcessRetellWebhookJob::dispatch(
                $request->all(),
                $request->headers->all(),
                $correlationId
            )->onQueue('webhooks-high-priority');
            
            // 6. Log performance metric
            $duration = (microtime(true) - $startTime) * 1000;
            if ($duration > 50) {
                Log::warning('Webhook processing exceeded target', [
                    'duration_ms' => $duration,
                    'event' => $event
                ]);
            }
            
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'queued' => true
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            // Always return success to prevent retries
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId
            ], 200);
        }
    }
    
    /**
     * Optimized inbound call handler with caching
     */
    private function handleInboundCallOptimized(Request $request, string $correlationId)
    {
        $toNumber = $request->input('call_inbound.to_number');
        
        // Cache company lookup (< 2ms with Redis)
        $company = Redis::remember("company:phone:{$toNumber}", 3600, function() use ($toNumber) {
            return Company::where('phone_number', $toNumber)
                ->select('id', 'name', 'retell_agent_id', 'calcom_api_key')
                ->first();
        });
        
        if (!$company) {
            $company = $this->getDefaultCompany();
        }
        
        // Build minimal response
        $response = [
            'response' => [
                'agent_id' => $company->retell_agent_id ?? config('services.retell.default_agent_id'),
                'dynamic_variables' => [
                    'company_name' => $company->name,
                    'correlation_id' => $correlationId
                ]
            ]
        ];
        
        // Check if availability check is needed
        if ($request->input('dynamic_variables.check_availability')) {
            // Queue availability check to avoid blocking
            $this->queueAvailabilityCheck($request->all(), $company, $correlationId);
            
            $response['response']['dynamic_variables']['availability_check_queued'] = true;
        }
        
        return response()->json($response, 200);
    }
    
    /**
     * Queue availability check for async processing
     */
    private function queueAvailabilityCheck(array $data, $company, string $correlationId)
    {
        // Use Redis pub/sub for real-time availability updates
        Redis::publish('availability-check', json_encode([
            'company_id' => $company->id,
            'correlation_id' => $correlationId,
            'requested_date' => $data['dynamic_variables']['requested_date'] ?? null,
            'event_type_id' => $data['dynamic_variables']['event_type_id'] ?? null,
            'callback_channel' => "availability-result:{$correlationId}"
        ]));
    }
    
    /**
     * Get default company with caching
     */
    private function getDefaultCompany()
    {
        return Redis::remember('company:default', 3600, function() {
            return Company::select('id', 'name', 'retell_agent_id', 'calcom_api_key')
                ->first();
        });
    }
}