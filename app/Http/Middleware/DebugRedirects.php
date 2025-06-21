<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Check if response has the methods we need
        if (method_exists($response, 'getStatusCode') && ($response->getStatusCode() === 302 || $response->getStatusCode() === 301)) {
            $target = 'unknown';
            if (method_exists($response, 'headers')) {
                $target = $response->headers->get('Location');
            }
            
            $from = $request->fullUrl();
            $method = $request->method();
            $user = auth()->user();
            
            Log::warning('REDIRECT DETECTED', [
                'from' => $from,
                'to' => $target,
                'method' => $method,
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'request_data' => $request->all(),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                'session_errors' => session('errors')?->all(),
                'is_livewire' => $request->header('X-Livewire') !== null,
                'referer' => $request->header('referer'),
            ]);
        }
        
        return $response;
    }
}