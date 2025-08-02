<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class InjectCsrfToken
{
    public function handle(Request $request, Closure $next)
    {
        // Only regenerate token if it doesn't exist
        if (($request->is('business/*') || $request->is('business')) && !$request->session()->has('_token')) {
            $request->session()->regenerateToken();
        }

        $response = $next($request);

        // Add CSRF token to response headers
        if ($response instanceof \Illuminate\Http\Response) {
            $response->headers->set('X-CSRF-TOKEN', csrf_token());
        }

        return $response;
    }
}
