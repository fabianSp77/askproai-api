<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ResolveDashboardRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is a dashboard-related route that's not found
        $path = $request->path();
        
        if (Str::startsWith($path, 'admin/') && Str::contains($path, 'dashboard')) {
            // Extract the dashboard slug from the path
            $segments = explode('/', $path);
            $dashboardSlug = end($segments);
            
            // Try to find a matching Filament route
            $possibleRoutes = [
                "filament.admin.pages.{$dashboardSlug}",
                "filament.admin.pages." . Str::kebab($dashboardSlug),
                "filament.admin.pages." . Str::snake($dashboardSlug),
            ];
            
            foreach ($possibleRoutes as $routeName) {
                if (Route::has($routeName)) {
                    return redirect()->route($routeName);
                }
            }
            
            // Log the missing route for debugging
            \Log::info("Dashboard route not found: {$path}, available routes: " . implode(', ', array_keys(Route::getRoutes()->getRoutesByName())));
            
            // If no matching route found and user is authenticated, redirect to OperationsDashboard
            if ($request->user()) {
                return redirect()->route('filament.admin.pages.operations-dashboard');
            }
        }
        
        return $next($request);
    }
}