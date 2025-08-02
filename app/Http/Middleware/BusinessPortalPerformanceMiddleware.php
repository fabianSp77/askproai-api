<?php

namespace App\Http\Middleware;

use App\Services\BusinessPortalPerformanceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Business Portal Performance Monitoring Middleware
 * 
 * Tracks performance metrics specifically for Business Portal endpoints
 * with SLA monitoring and alerting capabilities.
 */
class BusinessPortalPerformanceMiddleware
{
    protected BusinessPortalPerformanceService $performanceService;

    public function __construct(BusinessPortalPerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only monitor Business Portal routes
        if (!$this->isBusinessPortalRoute($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Add correlation ID for request tracking
        $correlationId = $this->generateCorrelationId();
        $request->headers->set('X-Correlation-ID', $correlationId);
        
        // Process the request
        $response = $next($request);
        
        // Calculate metrics
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = memory_get_usage(true) - $startMemory;
        $endpoint = $this->normalizeEndpoint($request->path());
        
        // Collect request metadata
        $metadata = [
            'correlation_id' => $correlationId,
            'memory_used' => $memoryUsed,
            'query_count' => $this->getQueryCount(),
            'user_id' => auth()->id(),
            'user_agent' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'route_name' => $request->route()?->getName(),
        ];

        // Record the performance metrics
        try {
            $this->performanceService->recordPortalRequest(
                $endpoint,
                $duration,
                $response->getStatusCode(),
                $metadata
            );
            
            // Add performance headers to response (in debug mode)
            if (config('app.debug')) {
                $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
                $response->headers->set('X-Memory-Used', $this->formatBytes($memoryUsed));
                $response->headers->set('X-Query-Count', $this->getQueryCount());
                $response->headers->set('X-Correlation-ID', $correlationId);
            }
            
        } catch (\Exception $e) {
            // Don't break the request if performance monitoring fails
            Log::warning('Performance monitoring failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'duration' => $duration,
            ]);
        }

        return $response;
    }

    /**
     * Check if the request is for a Business Portal route
     */
    protected function isBusinessPortalRoute(Request $request): bool
    {
        $path = $request->path();
        
        // Business Portal routes start with 'business'
        if (str_starts_with($path, 'business/')) {
            return true;
        }
        
        // API routes for Business Portal
        if (str_starts_with($path, 'api/business/') || str_starts_with($path, 'api/portal/')) {
            return true;
        }
        
        return false;
    }

    /**
     * Normalize endpoint path for consistent tracking
     */
    protected function normalizeEndpoint(string $path): string
    {
        // Convert dynamic segments to placeholders
        $normalizations = [
            '/business/calls/\d+' => '/business/calls/*',
            '/business/appointments/\d+' => '/business/appointments/*', 
            '/business/customers/\d+' => '/business/customers/*',
            '/business/team/\d+' => '/business/team/*',
            '/api/business/calls/\d+' => '/business/api/calls/*',
            '/api/business/appointments/\d+' => '/business/api/appointments/*',
            '/api/business/customers/\d+' => '/business/api/customers/*',
        ];
        
        foreach ($normalizations as $pattern => $replacement) {
            if (preg_match('#^' . $pattern . '$#', $path)) {
                return $replacement;
            }
        }
        
        // Ensure we have a leading slash
        return '/' . ltrim($path, '/');
    }

    /**
     * Generate a unique correlation ID for request tracking
     */
    protected function generateCorrelationId(): string
    {
        return 'req_' . uniqid() . '_' . substr(md5(microtime(true)), 0, 8);
    }

    /**
     * Get database query count (if available)
     */
    protected function getQueryCount(): int
    {
        // Try to get query count from Laravel's DB facade
        try {
            return count(\DB::getQueryLog());
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Format bytes into human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . 'B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . 'KB';
        } else {
            return round($bytes / 1048576, 1) . 'MB';
        }
    }
}