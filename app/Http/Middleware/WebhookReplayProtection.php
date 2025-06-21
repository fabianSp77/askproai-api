<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WebhookReplayProtection
{
    private const REPLAY_WINDOW = 300; // 5 minutes
    private const DEDUP_WINDOW = 3600; // 1 hour
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract webhook ID or generate from payload
        $webhookId = $this->getWebhookId($request);
        
        if (!$webhookId) {
            Log::warning('Webhook received without identifiable ID', [
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all()
            ]);
            // Continue processing but log the issue
            return $next($request);
        }
        
        // Check for replay attack (same webhook ID within short window)
        if ($this->isReplayAttack($webhookId, $request)) {
            Log::error('Potential webhook replay attack detected', [
                'webhook_id' => $webhookId,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'error' => 'Webhook replay detected',
                'message' => 'This webhook has already been processed recently'
            ], 409);
        }
        
        // Check for duplicate processing (idempotency)
        if ($this->isDuplicate($webhookId, $request)) {
            Log::info('Duplicate webhook detected, returning cached response', [
                'webhook_id' => $webhookId
            ]);
            
            $cachedResponse = $this->getCachedResponse($webhookId);
            if ($cachedResponse) {
                return response()->json($cachedResponse['data'], $cachedResponse['status']);
            }
            
            // If no cached response, continue but mark as duplicate
            $request->attributes->set('is_duplicate', true);
        }
        
        // Store webhook for deduplication
        $this->storeWebhook($webhookId, $request);
        
        // Process the webhook
        $response = $next($request);
        
        // Cache the response for idempotency
        $this->cacheResponse($webhookId, $response);
        
        return $response;
    }
    
    /**
     * Get webhook ID from request
     */
    private function getWebhookId(Request $request): ?string
    {
        // Try different common webhook ID fields
        $id = $request->input('id') 
            ?? $request->input('webhook_id') 
            ?? $request->input('event_id')
            ?? $request->input('call_id')
            ?? $request->header('X-Webhook-ID')
            ?? $request->header('X-Event-ID');
        
        if ($id) {
            return $id;
        }
        
        // Generate ID from payload hash for webhooks without explicit ID
        $payload = $request->all();
        if (!empty($payload)) {
            // Remove timestamp fields that might change
            unset($payload['timestamp'], $payload['created_at'], $payload['updated_at']);
            return 'generated_' . hash('sha256', json_encode($payload));
        }
        
        return null;
    }
    
    /**
     * Check if this is a replay attack
     */
    private function isReplayAttack(string $webhookId, Request $request): bool
    {
        $key = "webhook_replay:{$webhookId}";
        
        // Use Redis SETNX for atomic check-and-set
        if ($this->hasRedis()) {
            $result = Redis::set($key, time(), 'NX', 'EX', self::REPLAY_WINDOW);
            return !$result; // If set failed, it's a replay
        }
        
        // Fallback to cache
        if (Cache::has($key)) {
            $firstSeen = Cache::get($key);
            $timeDiff = time() - $firstSeen;
            
            // If seen within replay window, it's likely an attack
            return $timeDiff < self::REPLAY_WINDOW;
        }
        
        Cache::put($key, time(), self::REPLAY_WINDOW);
        return false;
    }
    
    /**
     * Check if webhook is a duplicate (for idempotency)
     */
    private function isDuplicate(string $webhookId, Request $request): bool
    {
        $key = "webhook_dedup:{$webhookId}";
        
        if ($this->hasRedis()) {
            return Redis::exists($key);
        }
        
        return Cache::has($key);
    }
    
    /**
     * Store webhook for deduplication
     */
    private function storeWebhook(string $webhookId, Request $request): void
    {
        $key = "webhook_dedup:{$webhookId}";
        $data = [
            'received_at' => time(),
            'ip' => $request->ip(),
            'signature' => $request->header('X-Webhook-Signature')
        ];
        
        if ($this->hasRedis()) {
            Redis::setex($key, self::DEDUP_WINDOW, json_encode($data));
        } else {
            Cache::put($key, $data, self::DEDUP_WINDOW);
        }
    }
    
    /**
     * Cache response for idempotency
     */
    private function cacheResponse(string $webhookId, Response $response): void
    {
        $key = "webhook_response:{$webhookId}";
        $data = [
            'data' => json_decode($response->getContent(), true),
            'status' => $response->getStatusCode(),
            'cached_at' => time()
        ];
        
        if ($this->hasRedis()) {
            Redis::setex($key, self::DEDUP_WINDOW, json_encode($data));
        } else {
            Cache::put($key, $data, self::DEDUP_WINDOW);
        }
    }
    
    /**
     * Get cached response
     */
    private function getCachedResponse(string $webhookId): ?array
    {
        $key = "webhook_response:{$webhookId}";
        
        if ($this->hasRedis()) {
            $data = Redis::get($key);
            return $data ? json_decode($data, true) : null;
        }
        
        return Cache::get($key);
    }
    
    /**
     * Check if Redis is available
     */
    private function hasRedis(): bool
    {
        try {
            return Redis::ping() !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}