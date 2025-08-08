<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRetellWebhookJobV2;
use App\Models\Call;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RetellWebhookOptimizedController extends Controller
{
    /**
     * Handle incoming Retell webhook with Quick ACK pattern
     * 
     * This controller implements the following optimizations:
     * 1. Quick ACK pattern - respond in <250ms
     * 2. Proper idempotency using retell_call_id
     * 3. Async processing via queue
     * 4. Correlation IDs for tracing
     */
    public function handle(Request $request)
    {
        // Start timing for performance monitoring
        $startTime = microtime(true);
        
        // Generate correlation ID for request tracing
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        
        try {
            // Extract data and handle nested structure
            $data = $request->all();
            
            // Handle nested call structure from Retell
            if (isset($data['call']) && is_array($data['call'])) {
                $callData = $data['call'];
                $data = array_merge($callData, [
                    'event' => $data['event'] ?? $data['event_type'] ?? null,
                    'event_type' => $data['event'] ?? $data['event_type'] ?? null
                ]);
            }
            
            // Extract critical identifiers
            $event = $data['event'] ?? $data['event_type'] ?? null;
            $retellCallId = $data['retell_call_id'] ?? $data['call_id'] ?? null;
            
            // If we have nested structure, check there too
            if (!$retellCallId && isset($data['call'])) {
                $retellCallId = $data['call']['retell_call_id'] ?? $data['call']['call_id'] ?? null;
            }
            
            // Log minimal info for monitoring
            Log::info('[Retell Webhook] Received', [
                'correlation_id' => $correlationId,
                'event' => $event,
                'retell_call_id' => $retellCallId,
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            // QUICK ACK PATTERN - Critical for <250ms response
            
            // 1. Quick validation
            if (!$retellCallId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing call_id'
                ], 400);
            }
            
            // 2. ATOMIC Idempotency using database unique constraint
            $idempotencyKey = "{$retellCallId}_{$event}";
            
            // Use DB transaction for atomicity
            $webhookEvent = null;
            $isDuplicate = false;
            
            DB::beginTransaction();
            try {
                // Atomic insert with idempotency - will fail on duplicate
                $webhookEvent = WebhookEvent::firstOrCreate(
                    [
                        'idempotency_key' => $idempotencyKey
                    ],
                    [
                        'event_type' => $event,
                        'event_id' => $retellCallId,
                        'payload' => [
                            // Store minimal data for quick persist
                            'event' => $event,
                            'retell_call_id' => $retellCallId,
                            'correlation_id' => $correlationId,
                            'timestamp' => now()->toIso8601String()
                        ],
                        'provider' => 'retell',
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                        'raw_payload_hash' => md5(json_encode($data)), // Store hash for validation
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                
                // Check if it was already existing
                $isDuplicate = !$webhookEvent->wasRecentlyCreated;
                
                if ($isDuplicate) {
                    DB::commit();
                    Log::debug('[Retell Webhook] Duplicate detected (atomic)', [
                        'correlation_id' => $correlationId,
                        'idempotency_key' => $idempotencyKey
                    ]);
                    
                    return response()->json([
                        'status' => 'ok',
                        'duplicate' => true,
                        'message' => 'Already processed'
                    ], 200);
                }
                
                // Store full payload in separate job to keep ACK fast
                DB::table('webhook_raw_payloads')->insert([
                    'webhook_event_id' => $webhookEvent->id,
                    'payload' => json_encode($data),
                    'created_at' => now()
                ]);
                
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
            // 4. Dispatch async job for actual processing
            // afterCommit() is now correct since we're in a transaction
            ProcessRetellWebhookJobV2::dispatch($webhookEvent->id, $correlationId)
                ->onQueue('webhooks-high')
                ->afterCommit(); // Only dispatches after DB::commit() succeeds
            
            // 5. Return success immediately (Quick ACK)
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('[Retell Webhook] Quick ACK sent', [
                'correlation_id' => $correlationId,
                'response_time_ms' => $responseTime,
                'webhook_event_id' => $webhookEvent->id
            ]);
            
            return response()->json([
                'status' => 'ok',
                'message' => 'Webhook accepted',
                'correlation_id' => $correlationId
            ], 200);
            
        } catch (\Exception $e) {
            // Even errors should be quick
            Log::error('[Retell Webhook] Quick ACK error', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            // Return error but still quickly
            return response()->json([
                'status' => 'error',
                'message' => 'Internal error',
                'correlation_id' => $correlationId
            ], 500);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'version' => 'optimized-v2',
            'features' => [
                'quick_ack' => true,
                'idempotency' => 'retell_call_id',
                'async_processing' => true,
                'correlation_tracking' => true
            ],
            'timestamp' => now()->toIso8601String()
        ]);
    }
}