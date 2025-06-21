<?php

namespace App\Helpers;

use App\Services\DashboardRouteResolver;
use Illuminate\Support\Facades\Route;

class DashboardHelper
{
    /**
     * Get the route for a dashboard, with fallback to SimpleDashboard
     */
    public static function route(string $dashboard): string
    {
        $resolver = new DashboardRouteResolver();
        
        // Try to resolve the dashboard route
        $route = $resolver->resolve($dashboard);
        
        if ($route && Route::has($route)) {
            return route($route);
        }
        
        // Fallback to default dashboard
        return route($resolver->getDefault());
    }
    
    /**
     * Check if a dashboard route exists
     */
    public static function exists(string $dashboard): bool
    {
        $resolver = new DashboardRouteResolver();
        return $resolver->exists($dashboard) && $resolver->resolve($dashboard) !== null;
    }
    
    /**
     * Get all available dashboard routes
     */
    public static function all(): array
    {
        $resolver = new DashboardRouteResolver();
        $dashboards = $resolver->getAllDashboards();
        
        $routes = [];
        foreach ($dashboards as $slug => $info) {
            if ($info['accessible']) {
                $routes[$slug] = route($info['route']);
            }
        }
        
        return $routes;
    }
}