<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DashboardRouteResolver
{
    /**
     * Map of dashboard slugs to their class names
     */
    protected array $dashboardMap = [
        'security-dashboard' => 'SecurityDashboard',
        'cost-dashboard' => 'CostDashboard',
        'event-analytics-dashboard' => 'EventAnalyticsDashboard',
        'executive-dashboard' => 'ExecutiveDashboard',
        'operational-dashboard' => 'OperationalDashboard',
        'operations-dashboard' => 'OperationsDashboard',
        'roi-dashboard' => 'RoiDashboard',
        'mcp-dashboard' => 'MCPDashboard',
        'dashboard-v2' => 'DashboardV2',
        'dashboard' => 'OperationsDashboard',
    ];
    
    /**
     * Default dashboard route name
     */
    protected string $defaultDashboard = 'filament.admin.pages.operations-dashboard';
    
    /**
     * Resolve a dashboard route by its slug
     */
    public function resolve(string $slug): ?string
    {
        // Direct route check
        $routeName = "filament.admin.pages.{$slug}";
        if (Route::has($routeName)) {
            return $routeName;
        }
        
        // Check mapped class name
        if (isset($this->dashboardMap[$slug])) {
            $className = $this->dashboardMap[$slug];
            
            // Try various route naming conventions
            $possibleRoutes = [
                "filament.admin.pages." . Str::kebab($className),
                "filament.admin.pages." . Str::snake($className),
                "filament.admin.pages." . Str::kebab(Str::remove('Dashboard', $className)),
                "filament.admin.pages." . $slug,
            ];
            
            foreach ($possibleRoutes as $possibleRoute) {
                if (Route::has($possibleRoute)) {
                    return $possibleRoute;
                }
            }
        }
        
        // Return null if not found (let caller decide what to do)
        return null;
    }
    
    /**
     * Get the default dashboard route
     */
    public function getDefault(): string
    {
        return $this->defaultDashboard;
    }
    
    /**
     * Check if a dashboard exists
     */
    public function exists(string $slug): bool
    {
        if (!isset($this->dashboardMap[$slug])) {
            return false;
        }
        
        $className = $this->dashboardMap[$slug];
        $fullClassName = "App\\Filament\\Admin\\Pages\\{$className}";
        
        return class_exists($fullClassName);
    }
    
    /**
     * Get all available dashboards
     */
    public function getAllDashboards(): array
    {
        $available = [];
        
        foreach ($this->dashboardMap as $slug => $className) {
            $fullClassName = "App\\Filament\\Admin\\Pages\\{$className}";
            
            if (class_exists($fullClassName)) {
                $route = $this->resolve($slug);
                $available[$slug] = [
                    'class' => $className,
                    'exists' => true,
                    'route' => $route,
                    'accessible' => $route !== null,
                ];
            }
        }
        
        return $available;
    }
}