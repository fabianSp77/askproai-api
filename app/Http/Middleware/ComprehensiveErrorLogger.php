<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ComprehensiveErrorLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
            
            // Log 500 errors with full details
            if ($response->getStatusCode() >= 500) {
                Log::channel('daily')->error('500 Error Detected', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user' => auth()->user()?->email ?? 'guest',
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'input' => $request->except(['password', 'password_confirmation']),
                    'headers' => $request->headers->all(),
                    'session' => session()->all(),
                    'response_status' => $response->getStatusCode(),
                    'response_content' => substr($response->getContent(), 0, 1000),
                    'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ]);
            }
            
            return $response;
        } catch (\Exception $e) {
            // Log the actual exception
            Log::channel('daily')->critical('Exception in Request', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user' => auth()->user()?->email ?? 'guest',
                'input' => $request->except(['password', 'password_confirmation']),
            ]);
            
            throw $e;
        }
    }
}