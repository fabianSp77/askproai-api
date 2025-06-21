<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

/**
 * Wraps the entire middleware pipeline to ensure proper response format
 * This should be the FIRST middleware in the global stack
 */
class ResponseWrapper
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
            
            // If it's not a proper response, convert it
            if (!$this->isProperResponse($response)) {
                return $this->convertToProperResponse($response, $request);
            }
            
            return $response;
        } catch (\Throwable $e) {
            // Check if this is the specific Livewire headers error
            if ($this->isLivewireHeadersError($e) || $this->isSessionMiddlewareError($e)) {
                \Log::warning('Response type error intercepted', [
                    'message' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'type' => get_class($e)
                ]);
                
                // For Livewire requests, return JSON redirect
                if ($request->hasHeader('X-Livewire')) {
                    return new JsonResponse([
                        'effects' => [
                            'redirect' => '/admin/login'
                        ]
                    ]);
                }
                
                // Return a proper redirect response
                return new RedirectResponse('/admin/login');
            }
            
            // Re-throw other exceptions
            throw $e;
        }
    }
    
    /**
     * Check if the response is a proper Symfony/Laravel response
     */
    private function isProperResponse($response): bool
    {
        return $response instanceof \Symfony\Component\HttpFoundation\Response;
    }
    
    /**
     * Check if the exception is the Livewire headers error
     */
    private function isLivewireHeadersError(\Throwable $e): bool
    {
        return $e instanceof \ErrorException &&
               str_contains($e->getMessage(), 'Undefined property') &&
               str_contains($e->getMessage(), 'headers') &&
               (str_contains($e->getMessage(), 'Livewire') || 
                str_contains($e->getFile(), 'livewire'));
    }
    
    /**
     * Check if the exception is from StartSession middleware
     */
    private function isSessionMiddlewareError(\Throwable $e): bool
    {
        return $e instanceof \TypeError &&
               str_contains($e->getMessage(), 'StartSession::addCookieToResponse()') &&
               str_contains($e->getMessage(), 'Livewire\\Features\\SupportRedirects\\Redirector');
    }
    
    /**
     * Convert various response types to proper Response objects
     */
    private function convertToProperResponse($response, Request $request)
    {
        // Handle Livewire Redirector
        if (is_object($response)) {
            $className = get_class($response);
            
            if (str_contains($className, 'Redirector')) {
                // Try to get URL from redirector
                if (method_exists($response, 'getUrl')) {
                    $url = $response->getUrl();
                } elseif (method_exists($response, 'getTargetUrl')) {
                    $url = $response->getTargetUrl();
                } elseif (property_exists($response, 'url')) {
                    $url = $response->url;
                } else {
                    // Default to admin login if we can't get URL
                    $url = '/admin/login';
                }
                
                // For Livewire requests, return JSON
                if ($request->hasHeader('X-Livewire')) {
                    return new JsonResponse([
                        'effects' => [
                            'redirect' => $url
                        ]
                    ]);
                }
                
                return new RedirectResponse($url);
            }
            
            // Handle objects with toResponse method
            if (method_exists($response, 'toResponse')) {
                return $response->toResponse($request);
            }
        }
        
        // String response
        if (is_string($response)) {
            return new Response($response);
        }
        
        // Array or object to JSON
        if (is_array($response) || (is_object($response) && !is_callable($response))) {
            return new JsonResponse($response);
        }
        
        // Null or empty
        if (is_null($response)) {
            return new Response('');
        }
        
        // Default
        return new Response('');
    }
}