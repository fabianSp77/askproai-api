<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Livewire\Features\SupportRedirects\Redirector as LivewireRedirector;

/**
 * Middleware to fix the Livewire headers access issue
 * This should be placed BEFORE other middleware that access response headers
 */
class FixLivewireHeadersIssue
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // If response is already a proper Symfony Response, return it
        if ($response instanceof Response) {
            return $response;
        }
        
        // Check if it's a Livewire Redirector
        if ($response instanceof LivewireRedirector || 
            (is_object($response) && get_class($response) === 'Livewire\Features\SupportRedirects\Redirector')) {
            
            // Handle Livewire redirector
            if (method_exists($response, 'getUrl')) {
                $url = $response->getUrl();
                
                // For Livewire requests, return proper JSON response
                if ($request->hasHeader('X-Livewire')) {
                    return new JsonResponse([
                        'effects' => [
                            'redirect' => $url
                        ]
                    ]);
                }
                
                // For non-Livewire requests, return standard redirect
                return new RedirectResponse($url);
            }
        }
        
        // Handle other response types
        if (is_string($response)) {
            return new \Illuminate\Http\Response($response);
        }
        
        if (is_array($response) || is_object($response)) {
            return new JsonResponse($response);
        }
        
        // Default: wrap in a standard response
        return new \Illuminate\Http\Response($response);
    }
}