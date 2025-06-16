<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixLivewireUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Fix Livewire URL issues when APP_URL doesn't match the current request URL
        $currentHost = $request->getHost();
        $currentScheme = $request->getScheme();
        $currentUrl = $currentScheme . '://' . $currentHost;
        
        $appUrl = config('app.url');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        
        // If we're on a different host than configured APP_URL, update Livewire config
        if ($currentHost !== $appHost || $currentHost === 'localhost' || $currentHost === '127.0.0.1') {
            config(['livewire.asset_url' => $currentUrl]);
            
            // Also update the app.url temporarily for this request
            config(['app.url' => $currentUrl]);
            
            // If we're on localhost without HTTPS, disable secure cookies
            if ($currentScheme === 'http' && ($currentHost === 'localhost' || $currentHost === '127.0.0.1')) {
                config(['session.secure' => false]);
            }
        }
        
        // Force HTTPS for Livewire URLs if the request is HTTPS
        if ($request->secure()) {
            $request->server->set('HTTPS', 'on');
            \URL::forceScheme('https');
        }
        
        return $next($request);
    }
}