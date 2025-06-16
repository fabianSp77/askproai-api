<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class MetricsMiddleware
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        // Collect metrics
        $this->collectMetrics($request, $response, $duration);
        
        return $response;
    }

    /**
     * Collect HTTP metrics
     */
    private function collectMetrics(Request $request, Response $response, float $duration): void
    {
        try {
            $route = $request->route();
            $routeName = $route ? $route->getName() : 'unknown';
            $routePath = $route ? $route->uri() : $request->path();
            
            // Record request duration
            $histogram = $this->registry->getHistogram(
                'askproai',
                'http_request_duration_seconds'
            );
            
            $histogram->observe(
                $duration,
                [
                    'method' => $request->method(),
                    'route' => $routePath,
                    'status_code' => $response->getStatusCode()
                ]
            );
            
            // Record API response time
            if (str_starts_with($request->path(), 'api/')) {
                $apiHistogram = $this->registry->getHistogram(
                    'askproai',
                    'api_response_time_seconds'
                );
                
                $apiHistogram->observe(
                    $duration,
                    [
                        'endpoint' => $routePath,
                        'method' => $request->method()
                    ]
                );
            }
            
            // Increment request counter
            $counter = $this->registry->getOrRegisterCounter(
                'askproai',
                'http_requests_total',
                'Total number of HTTP requests',
                ['method', 'route', 'status']
            );
            
            $counter->inc([
                'method' => $request->method(),
                'route' => $routePath,
                'status' => $response->getStatusCode()
            ]);
            
        } catch (\Exception $e) {
            // Don't let metrics collection break the request
            \Log::error('Failed to collect metrics: ' . $e->getMessage());
        }
    }
}