<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceStreamController extends Controller
{
    /**
     * Stream balance updates via Server-Sent Events
     * 
     * This solves the WebSocket limitation and provides real-time updates
     * without additional infrastructure
     */
    public function stream(Request $request): StreamedResponse
    {
        return response()->stream(function () use ($request) {
            $user = $request->user();
            $tenant = $user->tenant;
            $lastBalance = null;
            $heartbeatCounter = 0;
            
            // Set up Redis subscription for this tenant's channel
            $channel = "tenant.balance.{$tenant->id}";
            
            while (true) {
                // Check if connection is still alive
                if (connection_aborted()) {
                    break;
                }
                
                // Get current balance from cache or database
                $currentBalance = Cache::remember(
                    "balance.{$tenant->id}",
                    5,
                    fn() => $tenant->fresh()->balance_cents
                );
                
                // Send update if balance changed
                if ($currentBalance !== $lastBalance) {
                    $this->sendEvent([
                        'event' => 'balance-update',
                        'data' => [
                            'balance' => $currentBalance,
                            'formatted' => number_format($currentBalance / 100, 2, ',', '.') . ' €',
                            'timestamp' => now()->toIso8601String(),
                            'lowBalance' => $currentBalance < 500
                        ]
                    ]);
                    
                    $lastBalance = $currentBalance;
                }
                
                // Check for Redis pub/sub messages
                try {
                    $redis = Redis::connection('default');
                    $message = $redis->get($channel . ':latest');
                    
                    if ($message) {
                        $data = json_decode($message, true);
                        
                        // Send custom events from Redis
                        $this->sendEvent($data);
                        
                        // Clear the message
                        $redis->del($channel . ':latest');
                    }
                } catch (\Exception $e) {
                    // Log but don't break the stream
                    logger()->error('SSE Redis error: ' . $e->getMessage());
                }
                
                // Send heartbeat every 30 seconds to keep connection alive
                $heartbeatCounter++;
                if ($heartbeatCounter >= 15) { // 15 * 2 seconds = 30 seconds
                    $this->sendEvent([
                        'event' => 'heartbeat',
                        'data' => ['time' => now()->toIso8601String()]
                    ]);
                    $heartbeatCounter = 0;
                }
                
                // Sleep for 2 seconds before next check
                sleep(2);
                
                // Flush output to browser
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable Nginx buffering
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
    }
    
    /**
     * Send SSE event
     */
    private function sendEvent(array $data): void
    {
        $event = $data['event'] ?? 'message';
        $payload = $data['data'] ?? [];
        
        echo "event: {$event}\n";
        echo "data: " . json_encode($payload) . "\n\n";
    }
    
    /**
     * Trigger balance update via API (for testing)
     */
    public function triggerUpdate(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'amount' => 'required|integer'
        ]);
        
        $channel = "tenant.balance.{$request->tenant_id}";
        
        // Publish update to Redis
        Redis::connection('default')->set(
            $channel . ':latest',
            json_encode([
                'event' => 'balance-changed',
                'data' => [
                    'amount' => $request->amount,
                    'type' => $request->amount > 0 ? 'credit' : 'debit',
                    'timestamp' => now()->toIso8601String()
                ]
            ]),
            'EX',
            10 // Expire after 10 seconds
        );
        
        return response()->json(['success' => true]);
    }
}