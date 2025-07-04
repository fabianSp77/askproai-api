<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CircuitBreaker\CircuitBreakerManager;
use Illuminate\Http\JsonResponse;

class CircuitBreakerHealthController extends Controller
{
    private CircuitBreakerManager $circuitBreakerManager;
    
    public function __construct(CircuitBreakerManager $circuitBreakerManager)
    {
        $this->circuitBreakerManager = $circuitBreakerManager;
    }
    
    /**
     * Get circuit breaker health status
     */
    public function index(): JsonResponse
    {
        $status = $this->circuitBreakerManager->getAllStatus();
        
        // Determine overall health
        $overallHealth = 'healthy';
        $unhealthyServices = [];
        
        foreach ($status as $service => $serviceStatus) {
            if (!$serviceStatus['available']) {
                $overallHealth = 'degraded';
                $unhealthyServices[] = $service;
            }
        }
        
        // Get recent metrics
        $metrics = \DB::table('circuit_breaker_metrics')
            ->select('service', 
                \DB::raw('COUNT(*) as total_calls'),
                \DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_calls'),
                \DB::raw('AVG(duration_ms) as avg_duration_ms')
            )
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('service')
            ->get()
            ->keyBy('service');
        
        // Build response
        $response = [
            'status' => $overallHealth,
            'timestamp' => now()->toIso8601String(),
            'services' => []
        ];
        
        foreach ($status as $service => $serviceStatus) {
            $metric = $metrics[$service] ?? null;
            
            $response['services'][$service] = [
                'name' => $this->getServiceDisplayName($service),
                'state' => $serviceStatus['state'],
                'available' => $serviceStatus['available'],
                'health_score' => $serviceStatus['health_score'],
                'metrics' => $metric ? [
                    'total_calls_5min' => (int)$metric->total_calls,
                    'success_rate' => $metric->total_calls > 0 
                        ? round(($metric->successful_calls / $metric->total_calls) * 100, 2) 
                        : 100,
                    'avg_response_time_ms' => round($metric->avg_duration_ms, 2)
                ] : null
            ];
        }
        
        if (!empty($unhealthyServices)) {
            $response['unhealthy_services'] = $unhealthyServices;
        }
        
        return response()->json($response);
    }
    
    /**
     * Get detailed status for a specific service
     */
    public function show(string $service): JsonResponse
    {
        $validServices = ['calcom', 'retell', 'stripe'];
        
        if (!in_array($service, $validServices)) {
            return response()->json([
                'error' => 'Invalid service',
                'valid_services' => $validServices
            ], 400);
        }
        
        $allStatus = $this->circuitBreakerManager->getAllStatus();
        $serviceStatus = $allStatus[$service] ?? null;
        
        if (!$serviceStatus) {
            return response()->json([
                'error' => 'Service not found'
            ], 404);
        }
        
        // Get detailed metrics
        $recentMetrics = \DB::table('circuit_breaker_metrics')
            ->where('service', $service)
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        // Calculate percentiles
        $durations = $recentMetrics->pluck('duration_ms')->filter()->sort()->values();
        $p50 = $this->calculatePercentile($durations, 50);
        $p95 = $this->calculatePercentile($durations, 95);
        $p99 = $this->calculatePercentile($durations, 99);
        
        return response()->json([
            'service' => $service,
            'name' => $this->getServiceDisplayName($service),
            'status' => $serviceStatus,
            'metrics' => [
                'last_hour' => [
                    'total_calls' => $recentMetrics->count(),
                    'successful_calls' => $recentMetrics->where('status', 'success')->count(),
                    'failed_calls' => $recentMetrics->where('status', 'failure')->count(),
                    'success_rate' => $recentMetrics->count() > 0 
                        ? round(($recentMetrics->where('status', 'success')->count() / $recentMetrics->count()) * 100, 2)
                        : 100,
                ],
                'response_times' => [
                    'p50' => $p50,
                    'p95' => $p95,
                    'p99' => $p99,
                    'avg' => round($durations->avg(), 2),
                    'min' => $durations->min(),
                    'max' => $durations->max(),
                ]
            ],
            'configuration' => config("circuit_breaker.services.{$service}", config('circuit_breaker'))
        ]);
    }
    
    private function getServiceDisplayName(string $service): string
    {
        return match($service) {
            'calcom' => 'Cal.com API',
            'retell' => 'Retell.ai API',
            'stripe' => 'Stripe Payment API',
            default => ucfirst($service)
        };
    }
    
    private function calculatePercentile($values, $percentile)
    {
        if ($values->isEmpty()) {
            return null;
        }
        
        $index = ($percentile / 100) * ($values->count() - 1);
        
        if (floor($index) == $index) {
            return $values[$index];
        }
        
        $lower = $values[floor($index)];
        $upper = $values[ceil($index)];
        $weight = $index - floor($index);
        
        return round($lower + ($upper - $lower) * $weight, 2);
    }
}