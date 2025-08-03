<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Webhook endpoints that need CSRF disabled
        'api/webhook/*',
        'api/retell/*',
        'api/stripe/*',
        'api/calcom/*',
        // Livewire endpoints
        'livewire/*',
        // Auth API endpoints (using Sanctum tokens instead)
        'api/auth/*',
        // Only API auth endpoints that need CSRF exemption
        'business/api/auth/login',
        'business/api/auth/logout',
        'business/api/auth/refresh',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Only disable CSRF in demo mode if explicitly configured and NOT in production
        if (config('demo.enabled') && config('demo.disable_csrf', false) && ! app()->isProduction()) {
            return $next($request);
        }

        // REMOVED: Dangerous CSRF bypass for admin and business portals

        return parent::handle($request, $next);
    }
}
