<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableTenantScopeForAdminApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Set a flag to indicate we're in admin API context
        app()->instance('disable_tenant_scope', true);
        
        return $next($request);
    }
}