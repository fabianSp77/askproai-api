<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request for multi-tenant operations.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get tenant from user, header, or subdomain
        $user = $request->user();

        if ($user && $user->company_id) {
            // Set tenant context for the request
            $request->merge(['company_id' => $user->company_id]);

            // Optional: Set tenant context globally for models
            config(['tenant.current_company_id' => $user->company_id]);
        } elseif ($request->header('X-Company-ID')) {
            // Allow tenant override via header (for API clients)
            $companyId = $request->header('X-Company-ID');
            $request->merge(['company_id' => $companyId]);
            config(['tenant.current_company_id' => $companyId]);
        }

        return $next($request);
    }
}