<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugPortalEmail
{
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->path(), 'send-summary')) {
            $debug = [
                'timestamp' => now()->toIso8601String(),
                'method' => $request->method(),
                'path' => $request->path(),
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'session_id' => session()->getId(),
                'auth_user' => auth()->guard('portal')->user() ? auth()->guard('portal')->user()->email : 'not authenticated',
                'csrf_token' => $request->header('X-CSRF-TOKEN'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];
            
            \Log::channel('single')->info('[PORTAL EMAIL DEBUG]', $debug);
            file_put_contents(storage_path('logs/portal-email-debug.log'), "\n" . json_encode($debug, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        }
        
        return $next($request);
    }
}