<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Monitoring\PerformanceMonitor;
use App\Services\Monitoring\SecurityMonitor;
use Symfony\Component\HttpFoundation\Response;

class MonitoringMiddleware
{
    protected PerformanceMonitor $performance;
    protected SecurityMonitor $security;

    public function __construct(PerformanceMonitor $performance, SecurityMonitor $security)
    {
        $this->performance = $performance;
        $this->security = $security;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if IP is blocked
        if ($this->security->isIpBlocked($request->ip())) {
            abort(403, 'Access denied');
        }

        // Start performance monitoring
        $transactionName = $this->getTransactionName($request);
        $this->performance->startTransaction($transactionName);

        // Process request
        $response = $next($request);

        // Check if response has the necessary methods
        if (method_exists($response, 'getStatusCode')) {
            // End performance monitoring
            $this->performance->endTransaction($transactionName, [
                'status_code' => $response->getStatusCode(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()?->company_id,
                'error' => $response->getStatusCode() >= 400,
            ]);
        } else {
            // Handle non-standard responses (like Livewire redirects)
            $this->performance->endTransaction($transactionName, [
                'status_code' => 'unknown',
                'user_id' => auth()->id(),
                'company_id' => auth()->user()?->company_id,
                'error' => false,
            ]);
        }

        // Add security headers only if response supports it
        if (method_exists($response, 'headers')) {
            $this->addSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Get transaction name for monitoring
     */
    private function getTransactionName(Request $request): string
    {
        // Special handling for known endpoints
        if ($request->is('api/stripe/webhook')) {
            return 'stripe_webhook';
        }
        
        if ($request->is('portal/*')) {
            return 'customer_portal';
        }
        
        if ($request->is('api/*')) {
            return 'api_endpoints';
        }

        return 'request';
    }

    /**
     * Add security headers
     */
    private function addSecurityHeaders($response): void
    {
        // Only add headers if response supports it
        if (property_exists($response, 'headers') && $response->headers) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            
            if (app()->environment('production')) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }
    }
}