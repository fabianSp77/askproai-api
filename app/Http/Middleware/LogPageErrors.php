<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogPageErrors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
            
            // Log 500 errors with full details
            if ($response->getStatusCode() >= 500) {
                Log::error('HTTP 500 Error Detected', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'user_id' => auth()->id(),
                    'input' => $request->except(['password', 'password_confirmation']),
                    'headers' => $request->headers->all(),
                    'response_status' => $response->getStatusCode(),
                    'response_content' => substr($response->getContent(), 0, 1000), // First 1000 chars
                ]);
            }
            
            return $response;
        } catch (\Exception $e) {
            Log::error('CRITICAL ERROR in LogPageErrors Middleware', [
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}