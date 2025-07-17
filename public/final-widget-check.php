<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Login als Admin
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    die("Admin user not found.");
}
\Illuminate\Support\Facades\Auth::login($admin);

echo "=== Final Widget Check ===\n\n";

// Check if widgets can be instantiated and have canView permission
$widgets = [
    \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
    \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
    \App\Filament\Admin\Widgets\CallKpiWidget::class,
    \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
];

foreach ($widgets as $widgetClass) {
    echo "Widget: " . basename(str_replace('\\', '/', $widgetClass)) . "\n";
    
    // Check if class exists
    if (!class_exists($widgetClass)) {
        echo "  ❌ Class does not exist\n\n";
        continue;
    }
    
    // Instantiate
    try {
        $widget = new $widgetClass();
        echo "  ✅ Instantiated\n";
    } catch (\Exception $e) {
        echo "  ❌ Cannot instantiate: " . $e->getMessage() . "\n\n";
        continue;
    }
    
    // Check canView
    if (method_exists($widget, 'canView')) {
        $canView = $widget::canView();
        echo "  " . ($canView ? "✅" : "❌") . " canView: " . ($canView ? "true" : "false") . "\n";
    } else {
        echo "  ℹ️  No canView method (defaults to true)\n";
    }
    
    // Check authorization
    if (method_exists($widget, 'authorize')) {
        try {
            $authorized = $widget->authorize(request());
            echo "  " . ($authorized ? "✅" : "❌") . " authorize: " . ($authorized ? "true" : "false") . "\n";
        } catch (\Exception $e) {
            echo "  ❌ Authorization error: " . $e->getMessage() . "\n";
        }
    }
    
    // For StatsOverviewWidget, check data
    if ($widget instanceof \Filament\Widgets\StatsOverviewWidget) {
        try {
            $reflection = new ReflectionMethod($widget, 'getStats');
            $reflection->setAccessible(true);
            $stats = $reflection->invoke($widget);
            
            if (empty($stats)) {
                echo "  ⚠️  No stats returned (empty data)\n";
            } else {
                echo "  ✅ Returns " . count($stats) . " stats\n";
                
                // Show first stat for debugging
                if (isset($stats[0])) {
                    $firstStat = $stats[0];
                    echo "  📊 First stat: " . $firstStat->getLabel() . " = " . $firstStat->getValue() . "\n";
                }
            }
        } catch (\Exception $e) {
            echo "  ❌ Error getting stats: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

// Check Livewire component registration
echo "Livewire Component Check:\n";
$livewireComponents = [
    'filament.admin.widgets.call-live-status-widget',
    'filament.admin.widgets.global-filter-widget', 
    'filament.admin.widgets.call-kpi-widget',
    'filament.admin.resources.call-resource.widgets.call-analytics-widget'
];

foreach ($livewireComponents as $componentName) {
    try {
        $componentClass = app('livewire')->getClass($componentName);
        if ($componentClass) {
            echo "  ✅ $componentName => $componentClass\n";
        } else {
            echo "  ❌ $componentName not registered\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ $componentName: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Recommendation ===\n";
echo "If widgets are still not showing:\n";
echo "1. Clear browser cache completely (Ctrl+Shift+Del)\n";
echo "2. Check browser console for JavaScript errors\n";
echo "3. Inspect HTML to see if widget containers exist but are hidden\n";
echo "4. Check network tab for 404 errors on widget resources\n";