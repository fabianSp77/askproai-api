<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for Bearer token in Authorization header
        if (!$request->bearerToken()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Bearer token is required'
            ], 401);
        }

        // Check if user is authenticated via Sanctum
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'error' => 'Unauthorized', 
                'message' => 'Invalid or expired token'
            ], 401);
        }

        // Set the authenticated user for the request
        Auth::setUser(Auth::guard('sanctum')->user());

        // Add API-specific headers to response
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->headers->set('X-API-Version', '1.0');
            $response->headers->set('X-RateLimit-Limit', '60');
            $response->headers->set('X-RateLimit-Remaining', '59'); // This should be dynamic in production
        }

        return $response;
    }
}