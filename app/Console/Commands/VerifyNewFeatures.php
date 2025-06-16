<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class VerifyNewFeatures extends Command
{
    protected $signature = 'askproai:verify-features';
    protected $description = 'Verify all new features mentioned in the system';

    private array $features = [];

    public function handle()
    {
        $this->info('=== AskProAI Feature Verification ===');
        $this->newLine();

        // 1. Dashboard Features
        $this->verifyDashboards();
        
        // 2. Security Features
        $this->verifySecurityFeatures();
        
        // 3. Performance Features
        $this->verifyPerformanceFeatures();
        
        // 4. Backup & Migration Features
        $this->verifyBackupFeatures();
        
        // 5. Event Management Features
        $this->verifyEventManagement();
        
        // 6. Commands
        $this->verifyCommands();
        
        // 7. Widgets
        $this->verifyWidgets();
        
        // Summary
        $this->showSummary();
    }

    private function verifyDashboards()
    {
        $this->info('📊 Checking Dashboards:');
        
        $dashboards = [
            'SimpleDashboard' => '/admin',
            'EventAnalyticsDashboard' => '/admin/event-analytics-dashboard',
            'SecurityDashboard' => '/admin/security-dashboard',
            'SystemCockpit' => '/admin/system-cockpit',
            'SystemStatus' => '/admin/system-status',
        ];
        
        foreach ($dashboards as $name => $route) {
            $file = app_path("Filament/Admin/Pages/{$name}.php");
            if (File::exists($file)) {
                $this->features[$name] = true;
                $this->line("  ✅ {$name} - Available at {$route}");
            } else {
                $this->features[$name] = false;
                $this->line("  ❌ {$name} - Not found");
            }
        }
        $this->newLine();
    }

    private function verifySecurityFeatures()
    {
        $this->info('🔒 Checking Security Features:');
        
        $securityFiles = [
            'EncryptionService' => 'app/Security/EncryptionService.php',
            'ThreatDetector' => 'app/Security/ThreatDetector.php',
            'RateLimiter' => 'app/Security/RateLimiter.php',
            'AskProAISecurityLayer' => 'app/Security/AskProAISecurityLayer.php',
            'ThreatDetectionMiddleware' => 'app/Http/Middleware/ThreatDetectionMiddleware.php',
            'AdaptiveRateLimitMiddleware' => 'app/Http/Middleware/AdaptiveRateLimitMiddleware.php',
            'MetricsMiddleware' => 'app/Http/Middleware/MetricsMiddleware.php',
        ];
        
        foreach ($securityFiles as $name => $path) {
            if (File::exists(base_path($path))) {
                $this->features[$name] = true;
                $this->line("  ✅ {$name}");
            } else {
                $this->features[$name] = false;
                $this->line("  ❌ {$name}");
            }
        }
        $this->newLine();
    }

    private function verifyPerformanceFeatures()
    {
        $this->info('⚡ Checking Performance Features:');
        
        $performanceFiles = [
            'QueryOptimizer' => 'app/Services/QueryOptimizer.php',
            'QueryMonitor' => 'app/Services/QueryMonitor.php',
            'QueryCache' => 'app/Services/QueryCache.php',
            'CacheService' => 'app/Services/CacheService.php',
            'EagerLoadingAnalyzer' => 'app/Services/EagerLoadingAnalyzer.php',
            'EagerLoadingMiddleware' => 'app/Http/Middleware/EagerLoadingMiddleware.php',
        ];
        
        foreach ($performanceFiles as $name => $path) {
            if (File::exists(base_path($path))) {
                $this->features[$name] = true;
                $this->line("  ✅ {$name}");
            } else {
                $this->features[$name] = false;
                $this->line("  ❌ {$name}");
            }
        }
        $this->newLine();
    }

    private function verifyBackupFeatures()
    {
        $this->info('💾 Checking Backup & Migration Features:');
        
        $backupFiles = [
            'SystemBackupCommand' => 'app/Console/Commands/SystemBackupCommand.php',
            'SmartMigrateCommand' => 'app/Console/Commands/SmartMigrateCommand.php',
            'SmartMigrationService' => 'app/Services/SmartMigrationService.php',
        ];
        
        foreach ($backupFiles as $name => $path) {
            if (File::exists(base_path($path))) {
                $this->features[$name] = true;
                $this->line("  ✅ {$name}");
            } else {
                $this->features[$name] = false;
                $this->line("  ❌ {$name}");
            }
        }
        $this->newLine();
    }

    private function verifyEventManagement()
    {
        $this->info('📅 Checking Event Management Features:');
        
        $eventFiles = [
            'EventTypeImportWizard' => 'app/Filament/Admin/Pages/EventTypeImportWizard.php',
            'StaffEventAssignment' => 'app/Filament/Admin/Pages/StaffEventAssignment.php',
            'CalcomEventTypeResource' => 'app/Filament/Admin/Resources/CalcomEventTypeResource.php',
            'AvailabilityService' => 'app/Services/AvailabilityService.php',
        ];
        
        foreach ($eventFiles as $name => $path) {
            if (File::exists(base_path($path))) {
                $this->features[$name] = true;
                $this->line("  ✅ {$name}");
            } else {
                $this->features[$name] = false;
                $this->line("  ❌ {$name}");
            }
        }
        $this->newLine();
    }

    private function verifyCommands()
    {
        $this->info('⚙️ Checking Commands:');
        
        $commands = [
            'askproai:security-audit' => 'Security audit command',
            'askproai:backup' => 'System backup command',
            'migrate:smart' => 'Smart migration command',
            'cache:warm' => 'Cache warming command',
            'performance:analyze' => 'Performance analysis command',
            'calcom:sync-event-types' => 'Cal.com event type sync',
        ];
        
        foreach ($commands as $signature => $description) {
            try {
                $exists = $this->getApplication()->has($signature);
                $this->features["command:{$signature}"] = $exists;
                if ($exists) {
                    $this->line("  ✅ {$signature} - {$description}");
                } else {
                    $this->line("  ❌ {$signature} - Not registered");
                }
            } catch (\Exception $e) {
                $this->features["command:{$signature}"] = false;
                $this->line("  ❌ {$signature} - Error checking");
            }
        }
        $this->newLine();
    }

    private function verifyWidgets()
    {
        $this->info('🎯 Checking Widgets:');
        
        $widgets = [
            'StatsOverview',
            'RecentCalls',
            'RecentAppointments',
            'SystemStatus',
            'DashboardStats',
            'PerformanceMetricsWidget',
            'SystemStatusEnhanced',
            'ActivityLogWidget',
            'CompaniesChartWidget',
            'CustomerChartWidget',
        ];
        
        foreach ($widgets as $widget) {
            $file = app_path("Filament/Admin/Widgets/{$widget}.php");
            if (File::exists($file)) {
                $this->features["widget:{$widget}"] = true;
                $this->line("  ✅ {$widget}");
            } else {
                $this->features["widget:{$widget}"] = false;
                $this->line("  ❌ {$widget}");
            }
        }
        $this->newLine();
    }

    private function showSummary()
    {
        $total = count($this->features);
        $working = collect($this->features)->filter()->count();
        $percentage = $total > 0 ? round(($working / $total) * 100, 1) : 0;
        
        $this->info('📊 Summary:');
        $this->line("  Total features checked: {$total}");
        $this->line("  Working features: {$working}");
        $this->line("  Success rate: {$percentage}%");
        $this->newLine();
        
        if ($percentage < 100) {
            $this->warn('⚠️  Some features are missing or not properly registered.');
            $this->line('Missing features:');
            foreach ($this->features as $feature => $status) {
                if (!$status) {
                    $this->line("  - {$feature}");
                }
            }
        } else {
            $this->info('✅ All features are properly installed and registered!');
        }
        
        $this->newLine();
        $this->info('🔍 To access the features:');
        $this->line('  - Main Dashboard: /admin');
        $this->line('  - Event Analytics: /admin/event-analytics-dashboard');
        $this->line('  - Security Dashboard: /admin/security-dashboard (super admin only)');
        $this->line('  - System Status: /admin/system-status');
        $this->line('  - Metrics API: /api/metrics');
    }
}