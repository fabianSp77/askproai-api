<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Calcom\CalcomV2Client;
use Illuminate\Http\JsonResponse;

class CalcomHealthController extends Controller
{
    /**
     * Check Cal.com API health status
     */
    public function __invoke(): JsonResponse
    {
        try {
            $client = new CalcomV2Client();
            
            $health = $client->healthCheck();
            $metrics = $client->getMetrics();
            
            $status = $health['status'] === 'healthy' ? 200 : 503;
            
            return response()->json([
                'service' => 'cal.com',
                'status' => $health['status'],
                'timestamp' => now()->toIso8601String(),
                'details' => [
                    'response_time_ms' => $health['response_time_ms'] ?? null,
                    'circuit_breaker' => $metrics['circuit_breaker'],
                    'api_version' => $metrics['api_version'],
                    'cache_ttls' => $metrics['cache'],
                    'error' => $health['error'] ?? null,
                ],
            ], $status);
            
        } catch (\Exception $e) {
            return response()->json([
                'service' => 'cal.com',
                'status' => 'error',
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ], 503);
        }
    }
}