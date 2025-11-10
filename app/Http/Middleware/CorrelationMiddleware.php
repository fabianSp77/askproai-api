<?php

namespace App\Http\Middleware;

use App\Services\Tracing\RequestCorrelationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Correlation Middleware
 *
 * Initializes correlation tracking for every request
 * Adds correlation ID to all log entries
 * Tracks request metadata and operations
 */
class CorrelationMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        // Initialize correlation service
        $correlation = app(RequestCorrelationService::class);

        // Set request metadata
        $correlation->setMetadata([
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Add correlation ID to all logs
        Log::shareContext([
            'correlation_id' => $correlation->getId(),
            'request_path' => $request->path(),
        ]);

        // Record incoming request
        $correlation->recordOperation('http_request', [
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        // Process request
        $response = $next($request);

        // Add correlation headers to response
        foreach ($correlation->getResponseHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        // Record response
        $correlation->recordOperation('http_response', [
            'status_code' => $response->getStatusCode(),
        ]);

        // Mark as successful if 2xx status
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $correlation->markSuccessful([
                'status_code' => $response->getStatusCode(),
            ]);
        } elseif ($response->getStatusCode() >= 400) {
            $correlation->markFailed("HTTP {$response->getStatusCode()}");
        }

        return $response;
    }
}
