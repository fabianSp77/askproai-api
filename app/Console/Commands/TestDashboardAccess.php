<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestDashboardAccess extends Command
{
    protected $signature = 'askproai:test-dashboard';
    protected $description = 'Test dashboard accessibility and widgets';

    public function handle()
    {
        $this->info('🧪 Testing Dashboard Access...');
        
        // Test 1: Check if dashboard page exists
        $dashboardClass = \App\Filament\Admin\Pages\Dashboard::class;
        if (!class_exists($dashboardClass)) {
            $this->error('❌ Dashboard class not found!');
            return 1;
        }
        $this->info('✓ Dashboard class exists');
        
        // Test 2: Check widgets
        $dashboard = new $dashboardClass();
        try {
            $widgets = $dashboard->getWidgets();
            $this->info('✓ Dashboard has ' . count($widgets) . ' widgets');
            
            foreach ($widgets as $widget) {
                if (class_exists($widget)) {
                    $this->line("  ✓ Widget: " . class_basename($widget));
                } else {
                    $this->error("  ❌ Widget not found: " . $widget);
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Error getting widgets: ' . $e->getMessage());
        }
        
        // Test 3: Check header widgets using reflection
        try {
            $reflection = new \ReflectionClass($dashboard);
            if ($reflection->hasMethod('getHeaderWidgets')) {
                $method = $reflection->getMethod('getHeaderWidgets');
                $method->setAccessible(true);
                $headerWidgets = $method->invoke($dashboard);
                
                $this->info('✓ Dashboard has ' . count($headerWidgets) . ' header widgets');
                
                foreach ($headerWidgets as $widget) {
                    if (class_exists($widget)) {
                        $this->line("  ✓ Header Widget: " . class_basename($widget));
                    } else {
                        $this->error("  ❌ Header Widget not found: " . $widget);
                    }
                }
            } else {
                $this->info('ℹ️  Dashboard does not use header widgets');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error getting header widgets: ' . $e->getMessage());
        }
        
        // Test 4: Try to instantiate each widget
        $this->info('');
        $this->info('Testing widget instantiation...');
        
        $allWidgets = $dashboard->getWidgets() ?? [];
        
        // Add header widgets using reflection
        try {
            $reflection = new \ReflectionClass($dashboard);
            if ($reflection->hasMethod('getHeaderWidgets')) {
                $method = $reflection->getMethod('getHeaderWidgets');
                $method->setAccessible(true);
                $headerWidgets = $method->invoke($dashboard);
                $allWidgets = array_merge($allWidgets, $headerWidgets);
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        foreach ($allWidgets as $widgetClass) {
            if (class_exists($widgetClass)) {
                try {
                    $widget = new $widgetClass();
                    $this->line("  ✓ " . class_basename($widgetClass) . " instantiated successfully");
                } catch (\Exception $e) {
                    $this->error("  ❌ " . class_basename($widgetClass) . " failed: " . $e->getMessage());
                }
            }
        }
        
        $this->info('');
        $this->info('✅ Dashboard test completed!');
        
        return 0;
    }
}