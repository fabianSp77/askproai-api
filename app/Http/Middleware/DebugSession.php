<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugSession
{
    public function handle(Request $request, Closure $next)
    {
        $sessionId = session()->getId();
        $user = auth()->user();
        
        Log::info('DebugSession BEFORE request', [
            'url' => $request->url(),
            'method' => $request->method(),
            'session_id' => $sessionId,
            'session_driver' => config('session.driver'),
            'authenticated' => auth()->check(),
            'user_id' => $user?->id,
            'session_data' => session()->all(),
            'cookies' => $request->cookies->all(),
        ]);
        
        $response = $next($request);
        
        Log::info('DebugSession AFTER request', [
            'url' => $request->url(),
            'status' => $response->getStatusCode(),
            'session_id' => session()->getId(),
            'authenticated' => auth()->check(),
            'user_id' => auth()->user()?->id,
            'session_data' => session()->all(),
        ]);
        
        return $response;
    }
}