<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feature Flag Middleware
 *
 * Protects routes behind feature flags defined in config/features.php
 *
 * Usage:
 *   Route::middleware('feature:customer_portal')->group(function () { ... });
 *
 * Security:
 *   - Returns 404 when feature is disabled (pretends route doesn't exist)
 *   - Prevents enumeration attacks
 *   - Safe for production deployment (default: disabled)
 *
 * @see config/features.php
 */
class CheckFeatureFlag
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature flag name from config/features.php
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // Check if feature flag exists and is enabled
        if (!config("features.{$feature}")) {
            // Return 404 instead of 403 to prevent feature enumeration
            abort(404);
        }

        return $next($request);
    }
}
