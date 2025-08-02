<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

/**
 * Smart CSRF Token Verification
 * 
 * Provides intelligent CSRF protection while allowing legitimate API requests
 * through proper authentication mechanisms.
 */
class SmartCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        // Webhook endpoints (verified by signature)
        'api/webhook/*',
        'api/retell/*',
        'api/stripe/*',
        'api/calcom/*',
        
        // API endpoints that use Sanctum/Bearer tokens
        'api/*',
        'business-api/*',
        'admin-api/*',
    ];
    
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // Skip CSRF for requests with valid API tokens
        if ($this->shouldSkipCsrfForApiRequest($request)) {
            return $next($request);
        }
        
        // For AJAX requests, check X-CSRF-TOKEN header
        if ($request->ajax() && !$request->hasHeader('X-CSRF-TOKEN')) {
            // Try to get token from meta tag pattern
            $token = $request->header('X-XSRF-TOKEN');
            if ($token) {
                $request->headers->set('X-CSRF-TOKEN', $token);
            }
        }
        
        return parent::handle($request, $next);
    }
    
    /**
     * Determine if CSRF should be skipped for API request
     */
    protected function shouldSkipCsrfForApiRequest($request): bool
    {
        // Skip if request has valid Bearer token
        if ($request->bearerToken()) {
            return true;
        }
        
        // Skip if request is from Sanctum SPA authentication
        if ($request->hasHeader('X-Sanctum-Request')) {
            return true;
        }
        
        // Skip for webhook signatures (handled by specific middleware)
        if ($request->hasHeader('X-Webhook-Signature') || 
            $request->hasHeader('X-Retell-Signature') ||
            $request->hasHeader('Stripe-Signature')) {
            return true;
        }
        
        return false;
    }
}