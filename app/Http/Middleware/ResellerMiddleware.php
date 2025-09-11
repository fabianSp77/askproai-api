<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and is a reseller
        if (!auth()->guard('reseller')->check()) {
            return redirect()->route('filament.reseller.auth.login');
        }

        $user = auth()->guard('reseller')->user();
        
        // Verify the user's tenant is a reseller type
        if (!$user->tenant || $user->tenant->tenant_type !== 'reseller') {
            auth()->guard('reseller')->logout();
            return redirect()->route('filament.reseller.auth.login')
                ->with('error', 'Zugriff verweigert. Nur für Reseller.');
        }

        // Set the current reseller in the app context for global access
        app()->instance('current_reseller', $user->tenant);
        
        // Apply reseller-specific configuration
        config([
            'app.name' => $user->tenant->name . ' - Reseller Portal',
            'filament.brand.name' => $user->tenant->name,
        ]);

        return $next($request);
    }
}