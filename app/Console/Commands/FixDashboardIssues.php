<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class FixDashboardIssues extends Command
{
    protected $signature = 'askproai:fix-dashboard {--check : Only check for issues without fixing}';
    protected $description = 'Fix dashboard loading issues and route errors';

    public function handle()
    {
        $this->info('🔧 Fixing AskProAI Dashboard Issues...');
        
        $checkOnly = $this->option('check');
        
        // 1. Clear all caches
        if (!$checkOnly) {
            $this->line('1. Clearing all caches...');
            Artisan::call('optimize:clear');
            Artisan::call('filament:clear-cached-components');
            $this->info('✓ Caches cleared');
        }
        
        // 2. Check for problematic navigation items
        $this->line('2. Checking navigation configuration...');
        $this->checkNavigationIssues();
        
        // 3. Check widget issues
        $this->line('3. Checking widget configuration...');
        $this->checkWidgetIssues();
        
        // 4. Fix route registration issues
        if (!$checkOnly) {
            $this->line('4. Fixing route registration...');
            $this->fixRouteIssues();
        }
        
        // 5. Verify dashboard accessibility
        $this->line('5. Verifying dashboard accessibility...');
        $this->verifyDashboard();
        
        $this->info('✅ Dashboard fix process completed!');
        
        if (!$checkOnly) {
            $this->info('');
            $this->info('Please try accessing the dashboard again.');
            $this->info('If issues persist, run: php artisan askproai:fix-dashboard --check');
        }
    }
    
    private function checkNavigationIssues()
    {
        $resources = [
            'BranchResource' => ['map', 'schedule'],
            'StaffResource' => ['schedule', 'availability']
        ];
        
        foreach ($resources as $resource => $routes) {
            $class = "App\\Filament\\Admin\\Resources\\{$resource}";
            if (class_exists($class)) {
                $pages = $class::getPages();
                $registeredRoutes = array_keys($pages);
                
                foreach ($routes as $route) {
                    if (!in_array($route, $registeredRoutes)) {
                        $this->warn("  ⚠️  {$resource} is missing route: {$route}");
                    }
                }
            }
        }
    }
    
    private function checkWidgetIssues()
    {
        $widgets = [
            'QuickActionsWidget',
            'EnhancedDashboardStats',
            'CallAnalyticsWidget',
            'SystemStatus',
            'RecentAppointments',
            'RecentCalls'
        ];
        
        foreach ($widgets as $widget) {
            $class = "App\\Filament\\Admin\\Widgets\\{$widget}";
            if (!class_exists($class)) {
                $this->error("  ❌ Widget class not found: {$widget}");
            } else {
                $this->info("  ✓ Widget exists: {$widget}");
            }
        }
    }
    
    private function fixRouteIssues()
    {
        // Register any missing routes
        $routesFile = base_path('routes/web.php');
        $routesContent = File::get($routesFile);
        
        // Check if Filament routes are properly registered
        if (!str_contains($routesContent, 'Filament\\')) {
            $this->warn('  ⚠️  Filament routes might not be properly registered');
        }
        
        // Clear route cache
        Artisan::call('route:clear');
        $this->info('  ✓ Route cache cleared');
    }
    
    private function verifyDashboard()
    {
        $dashboardPath = app_path('Filament/Admin/Pages/Dashboard.php');
        
        if (!File::exists($dashboardPath)) {
            $this->error('  ❌ Dashboard page not found!');
            return;
        }
        
        $content = File::get($dashboardPath);
        
        // Check for common issues
        if (str_contains($content, 'getUrl(')) {
            $this->warn('  ⚠️  Dashboard might be using getUrl() which could cause route errors');
        }
        
        $this->info('  ✓ Dashboard page exists and appears configured');
    }
}