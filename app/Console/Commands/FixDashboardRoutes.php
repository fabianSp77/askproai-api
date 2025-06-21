<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class FixDashboardRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filament:fix-dashboard-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose and fix dashboard routing issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Analyzing dashboard routing configuration...');
        
        // Find all dashboard files
        $dashboardPath = app_path('Filament/Admin/Pages');
        $dashboardFiles = File::glob($dashboardPath . '/*Dashboard*.php');
        
        $this->info("\nFound " . count($dashboardFiles) . " dashboard files:");
        
        $dashboards = [];
        foreach ($dashboardFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = "App\\Filament\\Admin\\Pages\\{$className}";
            
            // Check if class exists
            if (class_exists($fullClassName)) {
                $dashboards[] = [
                    'file' => basename($file),
                    'class' => $className,
                    'full_class' => $fullClassName,
                    'exists' => true,
                ];
                $this->line("✓ {$className}");
            } else {
                $this->error("✗ {$className} - Class not found!");
            }
        }
        
        // Check Filament routes
        $this->info("\nChecking Filament routes:");
        $routes = Route::getRoutes();
        $filamentDashboardRoutes = [];
        
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name && Str::startsWith($name, 'filament.admin.pages.') && 
                (Str::contains($name, 'dashboard') || in_array(Str::afterLast($name, '.'), ['operational', 'executive', 'roi', 'mcp', 'security', 'cost', 'event-analytics']))) {
                $filamentDashboardRoutes[] = $name;
                $this->line("✓ Route exists: {$name}");
            }
        }
        
        // Check for missing routes
        $this->info("\nChecking for missing routes:");
        foreach ($dashboards as $dashboard) {
            if (!$dashboard['exists']) continue;
            
            $expectedRouteNames = [
                'filament.admin.pages.' . Str::kebab($dashboard['class']),
                'filament.admin.pages.' . Str::kebab(Str::remove('Dashboard', $dashboard['class'])),
                'filament.admin.pages.' . Str::snake($dashboard['class']),
            ];
            
            $found = false;
            foreach ($expectedRouteNames as $routeName) {
                if (Route::has($routeName)) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $this->warn("⚠ No route found for {$dashboard['class']}");
                $this->line("  Expected one of: " . implode(', ', $expectedRouteNames));
            }
        }
        
        // Show current SimpleDashboard route
        if (Route::has('filament.admin.pages.simple-dashboard')) {
            $this->info("\n✓ Main dashboard route exists: filament.admin.pages.simple-dashboard");
        } else {
            $this->error("\n✗ Main dashboard route missing: filament.admin.pages.simple-dashboard");
        }
        
        // Recommendations
        $this->info("\nRecommendations:");
        $this->line("1. The SimpleDashboard is set as the default dashboard");
        $this->line("2. All dashboard routes in filament-fix.php will redirect unknown dashboards to SimpleDashboard");
        $this->line("3. Use 'php artisan route:clear && php artisan route:cache' to refresh routes");
        $this->line("4. Check the admin panel at /admin to verify dashboard access");
        
        return Command::SUCCESS;
    }
}