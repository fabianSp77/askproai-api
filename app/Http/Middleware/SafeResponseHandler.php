<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SafeResponseHandler
{
    /**
     * Handle an incoming request and ensure safe response handling
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // This middleware ensures all responses are properly handled
        // It wraps any non-standard response types (like Livewire Redirector)
        // into proper HTTP responses
        
        // If it's already a proper response, return it
        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            return $response;
        }
        
        // Handle Livewire Redirector
        if (class_exists('\Livewire\Features\SupportRedirects\Redirector') && 
            $response instanceof \Livewire\Features\SupportRedirects\Redirector) {
            // Convert Livewire redirect to proper response
            // This prevents other middleware from trying to access non-existent properties
            return response()->json([
                'effects' => [
                    'redirect' => $response->getUrl() ?? '/'
                ]
            ]);
        }
        
        // For any other response type, try to convert to proper response
        return response($response);
    }
}