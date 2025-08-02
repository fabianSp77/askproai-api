<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceOptimization
{
    protected $startTime;
    protected $startMemory;
    protected $queryCount = 0;
    protected $cacheHits = 0;
    protected $cacheMisses = 0;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        // Enable query logging for monitoring
        if (config('performance.monitoring.track_query_count')) {
            DB::listen(function ($query) {
                $this->queryCount++;
                
                // Log slow queries
                if ($query->time > config('performance.query_optimization.slow_query_threshold', 100)) {
                    Log::channel('slow_queries')->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'url' => request()->url(),
                    ]);
                }
            });
        }

        // Apply request optimizations
        $this->optimizeRequest($request);

        $response = $next($request);

        // Apply response optimizations
        $response = $this->optimizeResponse($request, $response);

        // Add performance headers
        $this->addPerformanceHeaders($response);

        // Log performance metrics
        $this->logPerformanceMetrics($request, $response);

        return $response;
    }

    /**
     * Optimize the incoming request.
     */
    protected function optimizeRequest(Request $request): void
    {
        // Set eager loading hints
        if ($request->has('include')) {
            $includes = explode(',', $request->get('include'));
            $request->attributes->set('eager_load', $includes);
        }

        // Apply field filtering
        if ($request->has('fields')) {
            $fields = $this->parseFieldsParameter($request->get('fields'));
            $request->attributes->set('sparse_fields', $fields);
        }

        // Set pagination limits
        if ($request->has('per_page')) {
            $perPage = min(
                $request->get('per_page', 20),
                config('performance.api.pagination.max_per_page', 100)
            );
            $request->merge(['per_page' => $perPage]);
        }
    }

    /**
     * Optimize the response.
     */
    protected function optimizeResponse(Request $request, Response $response): Response
    {
        // Skip optimization for non-success responses
        if (!$response->isSuccessful()) {
            return $response;
        }

        // Apply response caching
        if ($this->shouldCacheResponse($request, $response)) {
            $this->cacheResponse($request, $response);
        }

        // Apply compression
        if ($this->shouldCompressResponse($request, $response)) {
            $response = $this->compressResponse($response);
        }

        // Add cache headers
        $this->addCacheHeaders($request, $response);

        return $response;
    }

    /**
     * Determine if response should be cached.
     */
    protected function shouldCacheResponse(Request $request, Response $response): bool
    {
        if (!config('performance.cache.response.enabled', true)) {
            return false;
        }

        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return false;
        }

        // Check excluded routes
        $excludedRoutes = config('performance.cache.response.exclude_routes', []);
        foreach ($excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        // Check if response has cache-control headers
        if ($response->headers->has('Cache-Control')) {
            $cacheControl = $response->headers->get('Cache-Control');
            if (str_contains($cacheControl, 'no-cache') || str_contains($cacheControl, 'no-store')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cache the response.
     */
    protected function cacheResponse(Request $request, Response $response): void
    {
        $key = $this->generateCacheKey($request);
        $ttl = config('performance.cache.response.ttl', 300);

        Cache::put($key, [
            'content' => $response->getContent(),
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
        ], $ttl);
    }

    /**
     * Generate cache key for request.
     */
    protected function generateCacheKey(Request $request): string
    {
        $parts = [
            'response_cache',
            $request->method(),
            $request->path(),
            md5($request->getQueryString() ?: ''),
        ];

        // Include user context if needed
        if (config('performance.cache.response.vary_by_user', true) && $request->user()) {
            $parts[] = 'user_' . $request->user()->id;
        }

        // Include branch context if needed
        if (config('performance.cache.response.vary_by_branch', true) && $request->get('branch_id')) {
            $parts[] = 'branch_' . $request->get('branch_id');
        }

        return implode(':', $parts);
    }

    /**
     * Determine if response should be compressed.
     */
    protected function shouldCompressResponse(Request $request, Response $response): bool
    {
        if (!config('performance.compression.enabled', true)) {
            return false;
        }

        // Check if client accepts compression
        if (!$request->headers->has('Accept-Encoding') || 
            !str_contains($request->headers->get('Accept-Encoding'), 'gzip')) {
            return false;
        }

        // Check content type
        $contentType = $response->headers->get('Content-Type', '');
        $allowedTypes = config('performance.compression.types', []);
        
        $shouldCompress = false;
        foreach ($allowedTypes as $type) {
            if (str_contains($contentType, $type)) {
                $shouldCompress = true;
                break;
            }
        }

        if (!$shouldCompress) {
            return false;
        }

        // Check content size
        $content = $response->getContent();
        if (strlen($content) < config('performance.compression.min_size', 1024)) {
            return false;
        }

        return true;
    }

    /**
     * Compress the response.
     */
    protected function compressResponse(Response $response): Response
    {
        $content = $response->getContent();
        $compressed = gzencode($content, config('performance.compression.level', 6));

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));

        return $response;
    }

    /**
     * Add cache headers to response.
     */
    protected function addCacheHeaders(Request $request, Response $response): void
    {
        // Public resources
        if ($request->is('api/public/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            return;
        }

        // Private resources
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, max-age=300');
            $response->headers->set('Vary', 'Authorization');
        }

        // Add ETag
        $etag = md5($response->getContent());
        $response->headers->set('ETag', $etag);

        // Check If-None-Match
        if ($request->headers->get('If-None-Match') === $etag) {
            $response->setStatusCode(304);
            $response->setContent('');
        }
    }

    /**
     * Add performance headers.
     */
    protected function addPerformanceHeaders(Response $response): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);
        $memory = round((memory_get_usage(true) - $this->startMemory) / 1024 / 1024, 2);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $response->headers->set('X-Response-Time', $duration . 'ms');
        $response->headers->set('X-Memory-Usage', $memory . 'MB');
        $response->headers->set('X-Peak-Memory', $peakMemory . 'MB');
        $response->headers->set('X-Query-Count', $this->queryCount);
        
        if ($this->cacheHits > 0 || $this->cacheMisses > 0) {
            $hitRate = $this->cacheHits / ($this->cacheHits + $this->cacheMisses) * 100;
            $response->headers->set('X-Cache-Hit-Rate', round($hitRate, 2) . '%');
        }
    }

    /**
     * Log performance metrics.
     */
    protected function logPerformanceMetrics(Request $request, Response $response): void
    {
        if (!config('performance.monitoring.enabled', true)) {
            return;
        }

        $duration = round((microtime(true) - $this->startTime) * 1000, 2);
        $memory = round((memory_get_usage(true) - $this->startMemory) / 1024 / 1024, 2);

        // Check thresholds
        $thresholds = config('performance.monitoring.alert_thresholds', []);
        
        if ($duration > ($thresholds['response_time'] ?? 1000)) {
            Log::channel('performance')->warning('Slow response detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration' => $duration,
                'memory' => $memory,
                'queries' => $this->queryCount,
            ]);
        }

        if ($this->queryCount > ($thresholds['query_count'] ?? 100)) {
            Log::channel('performance')->warning('High query count detected', [
                'url' => $request->fullUrl(),
                'queries' => $this->queryCount,
                'duration' => $duration,
            ]);
        }

        // Sample detailed metrics
        if ($this->shouldSampleRequest()) {
            Log::channel('performance')->info('Performance metrics', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
                'duration' => $duration,
                'memory' => $memory,
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'queries' => $this->queryCount,
                'cache_hits' => $this->cacheHits,
                'cache_misses' => $this->cacheMisses,
                'user_id' => $request->user()?->id,
            ]);
        }
    }

    /**
     * Determine if request should be sampled.
     */
    protected function shouldSampleRequest(): bool
    {
        $sampleRate = config('performance.monitoring.sample_rate', 0.1);
        return mt_rand() / mt_getrandmax() <= $sampleRate;
    }

    /**
     * Parse fields parameter.
     */
    protected function parseFieldsParameter(string $fields): array
    {
        $parsed = [];
        $parts = explode(',', $fields);
        
        foreach ($parts as $part) {
            if (str_contains($part, '[')) {
                // Format: resource[field1,field2]
                preg_match('/(\w+)\[([\w,]+)\]/', $part, $matches);
                if (count($matches) === 3) {
                    $parsed[$matches[1]] = explode(',', $matches[2]);
                }
            } else {
                // Simple field
                $parsed['_default'][] = $part;
            }
        }

        return $parsed;
    }
}