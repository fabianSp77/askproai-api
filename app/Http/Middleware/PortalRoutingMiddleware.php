<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalRoutingMiddleware
{
    /**
     * Handle an incoming request and redirect to appropriate portal based on user type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip for API routes
        if ($request->is('api/*')) {
            return $next($request);
        }
        
        // Skip if already on the correct portal
        $currentPath = $request->path();
        if ($this->isOnCorrectPortal($currentPath)) {
            return $next($request);
        }
        
        // Check if user is authenticated
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }
        
        // Determine correct portal based on user's portal_type
        $correctPortal = $user->getDefaultPortalRoute();
        
        // If user is trying to access wrong portal, redirect
        if ($this->shouldRedirect($currentPath, $correctPortal)) {
            return redirect($correctPortal);
        }
        
        return $next($request);
    }
    
    /**
     * Check if user is on the correct portal
     */
    protected function isOnCorrectPortal(string $path): bool
    {
        $user = Auth::user();
        if (!$user || !isset($user->portal_type)) {
            return true; // Allow access if no portal_type set
        }
        
        switch ($user->portal_type) {
            case 'admin':
                return str_starts_with($path, 'admin');
            case 'business':
                return str_starts_with($path, 'business') || str_starts_with($path, 'portal');
            case 'customer':
                return str_starts_with($path, 'customer');
            default:
                return true;
        }
    }
    
    /**
     * Check if redirect is needed
     */
    protected function shouldRedirect(string $currentPath, string $correctPortal): bool
    {
        // Don't redirect from login/logout pages
        if (str_contains($currentPath, 'login') || str_contains($currentPath, 'logout')) {
            return false;
        }
        
        // Don't redirect from correct portal
        $portalPrefix = trim($correctPortal, '/');
        if (str_starts_with($currentPath, $portalPrefix)) {
            return false;
        }
        
        // Redirect if trying to access different portal
        $restrictedPrefixes = ['admin', 'business', 'portal', 'customer'];
        foreach ($restrictedPrefixes as $prefix) {
            if (str_starts_with($currentPath, $prefix) && !str_starts_with($currentPath, $portalPrefix)) {
                return true;
            }
        }
        
        return false;
    }
}