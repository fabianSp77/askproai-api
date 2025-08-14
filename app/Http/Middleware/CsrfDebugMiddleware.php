<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CsrfDebugMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::channel('debug_csrf')->debug('CSRF-Debugging', [
            'path' => $request->path(),
            'method' => $request->method(),
            'session_token' => session()->token(),
            'request_token' => $request->input('_token'),
            'header_token' => $request->header('X-CSRF-TOKEN'),
            'cookie_token' => $request->cookie('XSRF-TOKEN'),
        ]);

        return $next($request);
    }
}
