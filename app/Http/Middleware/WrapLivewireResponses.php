<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WrapLivewireResponses
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
            
            // If it's a standard response, return it
            if ($response instanceof \Illuminate\Http\Response || 
                $response instanceof \Symfony\Component\HttpFoundation\Response) {
                return $response;
            }
            
            // If it's a Livewire redirector, handle it properly
            if (is_object($response) && get_class($response) === 'Livewire\Features\SupportRedirects\Redirector') {
                // Don't try to access any properties, just convert to redirect
                if (method_exists($response, 'getUrl')) {
                    return redirect($response->getUrl());
                }
            }
            
            // For any other response type, wrap it
            return response($response);
            
        } catch (\Throwable $e) {
            // If accessing properties causes an error, handle gracefully
            if (strpos($e->getMessage(), 'Undefined property') !== false && 
                strpos($e->getMessage(), 'headers') !== false) {
                // This is likely a Livewire response, return a basic redirect
                return redirect('/admin/login');
            }
            
            // Re-throw other errors
            throw $e;
        }
    }
}