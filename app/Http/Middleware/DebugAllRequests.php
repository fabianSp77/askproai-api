<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugAllRequests
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = uniqid('req_');
        
        // Log incoming request
        Log::channel('daily')->info("[$requestId] INCOMING REQUEST", [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'livewire' => $request->header('X-Livewire') ? 'YES' : 'NO',
        ]);
        
        // Process request
        $response = $next($request);
        
        // Log response
        if ($response->isRedirect()) {
            Log::channel('daily')->warning("[$requestId] REDIRECT RESPONSE", [
                'status' => $response->getStatusCode(),
                'target' => $response->headers->get('Location'),
                'from' => $request->fullUrl(),
            ]);
        } else {
            Log::channel('daily')->info("[$requestId] NORMAL RESPONSE", [
                'status' => $response->getStatusCode(),
            ]);
        }
        
        return $response;
    }
}