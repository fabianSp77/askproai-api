<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ErrorCatcher
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);

            // Log any 500 response
            if ($response->getStatusCode() === 500) {
                Log::error('🔴🔴🔴 500 ERROR DETECTED 🔴🔴🔴', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user' => auth()->user()?->email ?? 'guest',
                    'headers' => $request->headers->all(),
                    'session' => session()->all(),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('🔴🔴🔴 EXCEPTION IN MIDDLEWARE 🔴🔴🔴', [
                'url' => $request->fullUrl(),
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 3)
            ]);
            throw $e;
        }
    }
}