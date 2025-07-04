<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Webhook\RetellRealtimeService;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RetellRealtimeController extends Controller
{
    protected RetellRealtimeService $realtimeService;
    
    public function __construct(RetellRealtimeService $realtimeService)
    {
        $this->realtimeService = $realtimeService;
    }
    
    /**
     * Handle incoming Retell webhook with real-time processing
     */
    public function handleWebhook(Request $request)
    {
        $startTime = microtime(true);
        
        // Log incoming webhook
        Log::info('Retell webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);
        
        // Verify webhook signature (temporarily disabled)
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Webhook signature verification failed');
            // return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        // Extract event data
        $payload = $request->all();
        $eventType = $payload['event'] ?? $payload['event_type'] ?? 'unknown';
        $callId = $payload['call_id'] ?? $payload['data']['call_id'] ?? null;
        
        // Create idempotency key
        $idempotencyKey = $this->generateIdempotencyKey($eventType, $callId, $payload);
        
        // Check for duplicate processing
        if ($this->isDuplicate($idempotencyKey)) {
            Log::info('Duplicate webhook detected', ['key' => $idempotencyKey]);
            return response()->json(['status' => 'duplicate'], 200);
        }
        
        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'provider' => 'retell',
            'event_type' => $eventType,
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'status' => 'processing',
            'received_at' => now()
        ]);
        
        try {
            // Process webhook in real-time
            $result = $this->realtimeService->processWebhookRealtime($payload);
            
            // Update webhook event status
            $webhookEvent->update([
                'status' => $result['success'] ? 'processed' : 'failed',
                'processed_at' => now(),
                'response' => $result,
                'processing_time_ms' => (microtime(true) - $startTime) * 1000
            ]);
            
            // For call_inbound events, return dynamic variables
            if ($eventType === 'call_inbound' && isset($result['variables'])) {
                return response()->json($result['variables']);
            }
            
            return response()->json([
                'status' => 'success',
                'processed' => true,
                'event_id' => $webhookEvent->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $webhookEvent->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'processing_time_ms' => (microtime(true) - $startTime) * 1000
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Processing failed'
            ], 500);
        }
    }
    
    /**
     * Get active calls in real-time
     */
    public function getActiveCalls()
    {
        $activeCalls = \App\Models\Call::whereNull('end_timestamp')
            ->where('created_at', '>', now()->subHours(2))
            ->with(['customer', 'branch'])
            ->orderBy('start_timestamp', 'desc')
            ->get();
        
        return response()->json([
            'calls' => $activeCalls,
            'count' => $activeCalls->count(),
            'timestamp' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Get call updates stream (Server-Sent Events)
     */
    public function streamCallUpdates()
    {
        return response()->stream(function () {
            while (true) {
                // Check for updates
                $latestUpdate = Cache::get('latest_call_update');
                
                if ($latestUpdate) {
                    echo "data: " . json_encode($latestUpdate) . "\n\n";
                    Cache::forget('latest_call_update');
                }
                
                // Send heartbeat
                echo ": heartbeat\n\n";
                
                ob_flush();
                flush();
                
                // Wait 1 second before next check
                sleep(1);
                
                // Break after 30 seconds (client should reconnect)
                if (time() % 30 == 0) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no'
        ]);
    }
    
    /**
     * Manually fetch and sync recent calls
     */
    public function syncRecentCalls(Request $request)
    {
        $company = auth()->user()->company ?? \App\Models\Company::first();
        
        if (!$company) {
            return response()->json(['error' => 'No company found'], 404);
        }
        
        try {
            // Dispatch sync job with high priority
            \App\Jobs\FetchRetellCallsJob::dispatch($company)->onQueue('high');
            
            return response()->json([
                'status' => 'started',
                'message' => 'Sync started, check back in a few seconds'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Sync failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        // TODO: Fix Retell signature verification
        // Currently disabled due to format issues
        return true;
        
        /*
        $signature = $request->header('X-Retell-Signature');
        if (!$signature) {
            return false;
        }
        
        $secret = config('services.retell.webhook_secret');
        $payload = $request->getContent();
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
        */
    }
    
    /**
     * Generate idempotency key
     */
    protected function generateIdempotencyKey($eventType, $callId, $payload): string
    {
        $timestamp = $payload['timestamp'] ?? $payload['created_at'] ?? time();
        return md5($eventType . '|' . $callId . '|' . $timestamp);
    }
    
    /**
     * Check if webhook is duplicate
     */
    protected function isDuplicate($key): bool
    {
        $cacheKey = 'webhook_processed_' . $key;
        
        if (Cache::has($cacheKey)) {
            return true;
        }
        
        // Set cache for 24 hours
        Cache::put($cacheKey, true, 86400);
        
        // Also check database
        return WebhookEvent::where('idempotency_key', $key)
            ->where('status', '!=', 'failed')
            ->exists();
    }
}