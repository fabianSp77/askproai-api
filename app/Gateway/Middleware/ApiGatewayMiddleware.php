<?php

namespace App\Gateway\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Gateway\ApiGatewayManager;

class ApiGatewayMiddleware
{
    private ApiGatewayManager $gateway;

    public function __construct(ApiGatewayManager $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Handle an incoming request through the API Gateway
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if gateway is enabled
        if (!config('gateway.enabled', true)) {
            return $next($request);
        }

        // Only apply gateway to API routes
        if (!$this->shouldApplyGateway($request)) {
            return $next($request);
        }

        // Process through API Gateway
        return $this->gateway->handle($request);
    }

    /**
     * Determine if gateway should be applied to this request
     */
    private function shouldApplyGateway(Request $request): bool
    {
        $path = $request->path();
        
        // Apply to business portal API routes
        if (str_starts_with($path, 'business/api/')) {
            return true;
        }
        
        // Apply to versioned API routes
        if (preg_match('/^api\/v\d+\//', $path)) {
            return true;
        }
        
        // Check specific patterns from config
        $patterns = config('gateway.apply_to_patterns', []);
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }
}