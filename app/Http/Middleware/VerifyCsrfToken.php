<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     */
    protected $except = [
        // Only exclude specific webhook endpoints that need to bypass CSRF
        'api/retell/webhook',
        'api/retell/webhook-simple',
        'api/calcom/webhook',
        'api/stripe/webhook',
        // Livewire file uploads need CSRF bypass
        'livewire/upload-file',
        'livewire/preview-file',
        // Temporary fix for admin CSRF issues
        'admin/*',
        'livewire/*',
        // Admin API endpoints (JWT auth doesn't need CSRF)
        'api/admin/*',
        'api/admin/auth/login',
        'api/admin/auth/logout',
        'api/admin/auth/user',
        'api/admin/auth/refresh',
        // Business Portal API endpoints
        'business/api/*',
        'business/api-optional/*',
        'business/login',
        'business/simple-login',
        'business/test-login-api',
    ];
    
    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // Skip CSRF check for all API routes
        if ($request->is('api/*')) {
            return true;
        }
        
        $token = $this->getTokenFromRequest($request);

        // Log token mismatch for debugging
        if (!is_string($token) || !hash_equals($request->session()->token(), $token)) {
            \Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_token' => substr($request->session()->token(), 0, 10) . '...',
                'request_token' => $token ? substr($token, 0, 10) . '...' : 'null',
                'user_agent' => $request->userAgent(),
            ]);
        }
        
        return parent::tokensMatch($request);
    }
}
