<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\MCPHealthCheckService;
use App\Services\MCP\MCPMetricsCollector;
use App\Services\MCP\MCPServiceRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MCPHealthCheckController extends Controller
{
    protected MCPServiceRegistry $registry;
    protected MCPMetricsCollector $metricsCollector;
    protected MCPHealthCheckService $healthService;

    public function __construct(
        MCPServiceRegistry $registry,
        MCPMetricsCollector $metricsCollector,
        MCPHealthCheckService $healthService
    ) {
        $this->registry = $registry;
        $this->metricsCollector = $metricsCollector;
        $this->healthService = $healthService;
    }

    /**
     * Overall system health check
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $health = $this->healthService->checkSystemHealth();
            
            $statusCode = match ($health['status']) {
                'healthy' => 200,
                'degraded' => 200,
                'unhealthy' => 503,
                default => 500,
            };

            return response()->json($health, $statusCode);
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Detailed health check with all services
     */
    public function detailed(Request $request): JsonResponse
    {
        try {
            $detailed = $this->healthService->getDetailedHealth();
            
            return response()->json($detailed);
        } catch (\Exception $e) {
            Log::error('Detailed health check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Detailed health check failed',
            ], 500);
        }
    }

    /**
     * Service-specific health check
     */
    public function service(Request $request, string $service): JsonResponse
    {
        try {
            if (!$this->registry->hasService($service)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not found',
                ], 404);
            }

            $health = $this->healthService->checkServiceHealth($service);
            
            $statusCode = $health['healthy'] ? 200 : 503;
            
            return response()->json($health, $statusCode);
        } catch (\Exception $e) {
            Log::error('Service health check failed', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Service health check failed',
                'service' => $service,
            ], 500);
        }
    }

    /**
     * Prometheus metrics endpoint
     */
    public function metrics(Request $request): string
    {
        try {
            $metrics = $this->metricsCollector->getPrometheusMetrics();
            
            return response($metrics)
                ->header('Content-Type', 'text/plain; version=0.0.4');
        } catch (\Exception $e) {
            Log::error('Metrics generation failed', [
                'error' => $e->getMessage(),
            ]);

            return response('# Metrics generation failed\n', 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Readiness probe for Kubernetes
     */
    public function ready(Request $request): JsonResponse
    {
        try {
            // Check if system is ready to serve requests
            $checks = [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'services' => $this->checkServices(),
            ];

            $ready = collect($checks)->every(fn ($check) => $check === true);

            if ($ready) {
                return response()->json([
                    'status' => 'ready',
                    'checks' => $checks,
                ]);
            }

            return response()->json([
                'status' => 'not_ready',
                'checks' => $checks,
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liveness probe for Kubernetes
     */
    public function live(Request $request): JsonResponse
    {
        // Simple check to see if the application is alive
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get current alerts
     */
    public function alerts(Request $request): JsonResponse
    {
        try {
            $alerts = $this->metricsCollector->getActiveAlerts();
            
            return response()->json([
                'alerts' => $alerts,
                'count' => count($alerts),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve alerts',
            ], 500);
        }
    }

    /**
     * Get service metrics
     */
    public function serviceMetrics(Request $request, string $service): JsonResponse
    {
        try {
            if (!$this->registry->hasService($service)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service not found',
                ], 404);
            }

            $timeRange = $request->get('range', '1h');
            $metrics = $this->metricsCollector->getServiceMetrics($service, $timeRange);
            
            return response()->json($metrics);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve service metrics',
            ], 500);
        }
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkCache(): bool
    {
        try {
            Cache::store()->put('health_check', true, 10);
            return Cache::store()->get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkServices(): bool
    {
        try {
            // Check if critical services are registered
            $criticalServices = ['database', 'cache', 'queue'];
            
            foreach ($criticalServices as $service) {
                if ($this->registry->hasService($service)) {
                    $health = $this->healthService->checkServiceHealth($service);
                    if (!$health['healthy']) {
                        return false;
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}