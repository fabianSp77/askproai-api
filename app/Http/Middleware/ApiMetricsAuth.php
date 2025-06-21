<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiMetricsAuth
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
        $token = $request->bearerToken() ?? $request->header('X-Metrics-Token');
        $expectedToken = config('monitoring.metrics_token', env('METRICS_AUTH_TOKEN'));
        
        if (!$token || $token !== $expectedToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}