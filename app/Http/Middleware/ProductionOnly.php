<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionOnly
{
    /**
     * Handle an incoming request.
     *
     * Blocks access to production-only endpoints in non-production environments.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app()->isProduction(),
            403,
            'This endpoint is only available in production'
        );

        return $next($request);
    }
}
