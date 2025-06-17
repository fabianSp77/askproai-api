<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        // Get or generate correlation ID
        $correlationId = $request->header('X-Correlation-ID') 
            ?? $request->header('X-Request-ID')
            ?? Str::uuid()->toString();

        // Set it in the request for downstream use
        $request->headers->set('X-Correlation-ID', $correlationId);

        // Set it in the logger
        $logger = app(StructuredLogger::class);
        $logger->setCorrelationId($correlationId);

        // Add to Laravel's log context
        \Log::shareContext([
            'correlation_id' => $correlationId
        ]);

        // Process the request
        $response = $next($request);

        // Add correlation ID to response headers
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}