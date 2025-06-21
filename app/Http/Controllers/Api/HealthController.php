<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use App\Services\Calcom\CalcomV2Client;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    protected HealthCheckService $healthCheckService;
    
    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }
    /**
     * Basic health check endpoint
     */
    public function health(): JsonResponse
    {
        // Simple ping endpoint for load balancers
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'askproai-api'
        ]);
    }
    
    /**
     * Comprehensive health check endpoint
     */
    public function comprehensive(Request $request): JsonResponse
    {
        // Get company context from request (for authenticated checks)
        $company = null;
        if ($request->user() && $request->user()->company_id) {
            $company = Company::find($request->user()->company_id);
        }
        
        // Use first company as fallback for system-level checks
        if (!$company) {
            $company = Company::first();
        }
        
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'No company context available for health checks'
            ], 500);
        }
        
        // Run comprehensive health checks
        $this->healthCheckService->setCompany($company);
        $report = $this->healthCheckService->runAll();
        
        // Format response
        $httpStatus = match($report->status) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 500
        };
        
        return response()->json([
            'status' => $report->status,
            'timestamp' => $report->timestamp->toIso8601String(),
            'execution_time_ms' => round($report->totalExecutionTime * 1000, 2),
            'critical_failures' => $report->criticalFailures,
            'checks' => collect($report->checks)->map(function($check) {
                return [
                    'status' => $check->status,
                    'message' => $check->message,
                    'response_time_ms' => round($check->responseTime, 2),
                    'details' => $check->details,
                    'metrics' => $check->metrics,
                    'issues' => $check->issues,
                    'suggestions' => $check->suggestions
                ];
            })->toArray()
        ], $httpStatus);
    }
    
    /**
     * Individual service health check
     */
    public function service(Request $request, string $service): JsonResponse
    {
        // Get company context
        $company = null;
        if ($request->user() && $request->user()->company_id) {
            $company = Company::find($request->user()->company_id);
        }
        
        if (!$company) {
            $company = Company::first();
        }
        
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'No company context available'
            ], 500);
        }
        
        // Run specific service check
        $this->healthCheckService->setCompany($company);
        $result = $this->healthCheckService->runCheckByName($service);
        
        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => "Health check '{$service}' not found",
                'available_checks' => collect($this->healthCheckService->checks)
                    ->pluck('name')
                    ->toArray()
            ], 404);
        }
        
        $httpStatus = match($result->status) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 500
        };
        
        return response()->json([
            'service' => $service,
            'status' => $result->status,
            'message' => $result->message,
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => round($result->responseTime, 2),
            'details' => $result->details,
            'metrics' => $result->metrics,
            'issues' => $result->issues,
            'suggestions' => $result->suggestions
        ], $httpStatus);
    }
    
    /**
     * Cal.com integration health check
     */
    public function calcomHealth(CalcomV2Client $calcomClient): JsonResponse
    {
        $status = 'healthy';
        $checks = [];
        
        // Circuit breaker status
        $circuitStatus = Cache::get('circuit_breaker:calcom_v2', ['state' => 'closed', 'failures' => 0]);
        $checks['circuit_breaker'] = [
            'status' => $circuitStatus['state'] === 'closed' ? 'healthy' : 'degraded',
            'state' => $circuitStatus['state'],
            'failures' => $circuitStatus['failures'] ?? 0
        ];
        
        // API connectivity test
        try {
            $result = $calcomClient->healthCheck();
            $checks['api_connectivity'] = [
                'status' => $result['status'],
                'message' => $result['status'] === 'healthy' ? 'Cal.com API accessible' : 'Cal.com API unreachable',
                'response_time_ms' => $result['response_time_ms'] ?? null,
                'circuit_state' => $result['circuit_state'] ?? null
            ];
            
            if ($result['status'] !== 'healthy') {
                $status = 'unhealthy';
                if (isset($result['error'])) {
                    $checks['api_connectivity']['error'] = config('app.debug') ? $result['error'] : 'Connection failed';
                }
            }
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['api_connectivity'] = [
                'status' => 'unhealthy',
                'message' => 'Cal.com API check failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Connection failed'
            ];
        }
        
        // Cache performance
        $cacheStats = [
            'hits' => Cache::get('calcom_cache_hits', 0),
            'misses' => Cache::get('calcom_cache_misses', 0),
            'hit_rate' => 0
        ];
        
        if ($cacheStats['hits'] + $cacheStats['misses'] > 0) {
            $cacheStats['hit_rate'] = round(
                ($cacheStats['hits'] / ($cacheStats['hits'] + $cacheStats['misses'])) * 100,
                2
            );
        }
        
        $checks['cache_performance'] = [
            'status' => $cacheStats['hit_rate'] > 50 ? 'healthy' : 'warning',
            'metrics' => $cacheStats
        ];
        
        // Recent API errors
        $recentErrors = Cache::get('calcom_recent_errors', []);
        $errorCount = count($recentErrors);
        
        $checks['error_rate'] = [
            'status' => $errorCount > 10 ? 'warning' : 'healthy',
            'errors_last_hour' => $errorCount,
            'recent_errors' => config('app.debug') ? array_slice($recentErrors, -5) : []
        ];
        
        return response()->json([
            'status' => $status,
            'service' => 'cal.com',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'configuration' => [
                'api_version' => 'v2',
                'base_url' => config('services.calcom.base_url'),
                'team_slug' => config('services.calcom.team_slug')
            ]
        ], $status === 'healthy' ? 200 : 503);
    }
}