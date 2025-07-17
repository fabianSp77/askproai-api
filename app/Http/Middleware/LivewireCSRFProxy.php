<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LivewireCSRFProxy
{
    /**
     * Intercept Livewire requests and handle 419 errors
     */
    public function handle(Request $request, Closure $next)
    {
        // For Livewire requests
        if ($request->header('X-Livewire')) {
            // Always regenerate token for Livewire
            if (!$request->session()->token()) {
                $request->session()->regenerateToken();
            }
            
            // Process request
            $response = $next($request);
            
            // If we get a 419, convert to 200 with empty response
            if ($response->status() === 419) {
                return response()->json([
                    'effects' => [],
                    'serverMemo' => [
                        'data' => [],
                        'htmlHash' => null,
                        'children' => [],
                        'errors' => [],
                        'checksum' => ''
                    ]
                ], 200);
            }
            
            return $response;
        }
        
        return $next($request);
    }
}