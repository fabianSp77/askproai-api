<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CircuitBreaker\CircuitBreaker;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * Check circuit breaker status for all services
     */
    public function circuitBreaker(): JsonResponse
    {
        $status = CircuitBreaker::getStatus();
        
        $healthy = true;
        foreach ($status as $service => $state) {
            if ($state['state'] === 'open') {
                $healthy = false;
                break;
            }
        }
        
        return response()->json([
            'healthy' => $healthy,
            'services' => $status,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}