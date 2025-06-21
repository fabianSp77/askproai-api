<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures all responses are proper Symfony Response objects
 * This prevents the "Undefined property: $headers" error
 */
class EnsureProperResponseFormat
{
    public function handle(Request $request, Closure $next)
    {
        // Wrap the next middleware chain to catch any response type
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            // If there's an error, ensure it's properly formatted
            if (str_contains($e->getMessage(), 'Undefined property') && str_contains($e->getMessage(), 'headers')) {
                // Log the error for debugging
                \Log::error('Headers property access error caught', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Return a proper error response
                return new JsonResponse([
                    'message' => 'An error occurred processing the request',
                    'error' => app()->environment('local') ? $e->getMessage() : null
                ], 500);
            }
            
            // Re-throw other exceptions
            throw $e;
        }
        
        // Ensure response is a proper Symfony Response
        return $this->ensureProperResponse($response, $request);
    }
    
    /**
     * Convert various response types to proper Symfony Response objects
     */
    protected function ensureProperResponse($response, Request $request)
    {
        // Already a proper response
        if ($response instanceof Response) {
            return $response;
        }
        
        // Handle Livewire Redirector
        if (is_object($response)) {
            $className = get_class($response);
            
            // Livewire Redirector
            if (str_contains($className, 'Livewire') && str_contains($className, 'Redirector')) {
                if (method_exists($response, 'getUrl')) {
                    $url = $response->getUrl();
                    
                    // For Livewire AJAX requests
                    if ($request->hasHeader('X-Livewire')) {
                        return new JsonResponse([
                            'effects' => [
                                'redirect' => $url
                            ]
                        ]);
                    }
                    
                    // Standard redirect
                    return new RedirectResponse($url);
                }
            }
            
            // If object has toResponse method
            if (method_exists($response, 'toResponse')) {
                return $response->toResponse($request);
            }
            
            // If object is jsonable
            if (method_exists($response, 'toJson')) {
                return new JsonResponse($response);
            }
        }
        
        // String response
        if (is_string($response)) {
            return new \Illuminate\Http\Response($response);
        }
        
        // Array or stdClass
        if (is_array($response) || $response instanceof \stdClass) {
            return new JsonResponse($response);
        }
        
        // Null response
        if (is_null($response)) {
            return new \Illuminate\Http\Response('');
        }
        
        // Boolean response
        if (is_bool($response)) {
            return new JsonResponse(['success' => $response]);
        }
        
        // Numeric response
        if (is_numeric($response)) {
            return new \Illuminate\Http\Response((string) $response);
        }
        
        // Default: try to convert to string
        try {
            return new \Illuminate\Http\Response((string) $response);
        } catch (\Throwable $e) {
            // If conversion fails, return empty response
            return new \Illuminate\Http\Response('');
        }
    }
}