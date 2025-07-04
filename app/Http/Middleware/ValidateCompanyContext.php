<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateCompanyContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip for public routes
        if ($request->is('api/health') || $request->is('api/webhook/*')) {
            return $next($request);
        }
        
        // Ensure user is authenticated for protected routes
        if (!auth()->check() && !$request->is('login', 'register', 'password/*')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        // Log and reject any attempts to use X-Company-Id header
        if ($request->hasHeader('X-Company-Id')) {
            Log::warning('Rejected request with X-Company-Id header', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'user_id' => auth()->id(),
                'header_value' => $request->header('X-Company-Id')
            ]);
            
            // Remove the header to prevent any accidental usage
            $request->headers->remove('X-Company-Id');
        }
        
        return $next($request);
    }
}