<?php
/**
 * Widget Display Fix Script
 * 
 * This script diagnoses and fixes widget display issues in Filament
 */

require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "🔧 Widget Display Fix Script\n";
echo "===========================\n\n";

// 1. Clear all caches
echo "1. Clearing all caches...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
\Illuminate\Support\Facades\Artisan::call('view:clear');
\Illuminate\Support\Facades\Artisan::call('filament:clear-cached-components');
echo "   ✅ Caches cleared\n\n";

// 2. Rebuild Filament assets
echo "2. Rebuilding Filament assets...\n";
\Illuminate\Support\Facades\Artisan::call('filament:assets');
echo "   ✅ Assets rebuilt\n\n";

// 3. Check widget registration
echo "3. Checking widget registration...\n";
$panel = \Filament\Facades\Filament::getPanel('admin');
$widgets = $panel->getWidgets();

$requiredWidgets = [
    \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
    \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
    \App\Filament\Admin\Widgets\CallKpiWidget::class,
    \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
];

$missingWidgets = [];
foreach ($requiredWidgets as $widget) {
    if (!in_array($widget, $widgets)) {
        $missingWidgets[] = $widget;
    }
}

if (empty($missingWidgets)) {
    echo "   ✅ All required widgets are registered\n";
} else {
    echo "   ⚠️  Missing widgets:\n";
    foreach ($missingWidgets as $widget) {
        echo "      - " . basename(str_replace('\\', '/', $widget)) . "\n";
    }
}
echo "\n";

// 4. Test widget rendering
echo "4. Testing widget rendering...\n";
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    \Illuminate\Support\Facades\Auth::login($admin);
    
    foreach ($requiredWidgets as $widgetClass) {
        echo "   Testing " . basename(str_replace('\\', '/', $widgetClass)) . "...\n";
        
        try {
            $widget = new $widgetClass();
            
            // For StatsOverviewWidget
            if ($widget instanceof \Filament\Widgets\StatsOverviewWidget) {
                $reflection = new ReflectionMethod($widget, 'getStats');
                $reflection->setAccessible(true);
                $stats = $reflection->invoke($widget);
                echo "      ✅ Returns " . count($stats) . " stats\n";
            }
            
            // For Widget with custom view
            if (property_exists($widget, 'view')) {
                $viewReflection = new ReflectionProperty($widgetClass, 'view');
                $viewReflection->setAccessible(true);
                $view = $viewReflection->getValue($widget);
                
                if ($view && view()->exists($view)) {
                    echo "      ✅ View exists: $view\n";
                }
            }
            
        } catch (\Exception $e) {
            echo "      ❌ Error: " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";

// 5. Generate CSS fix
echo "5. Generating CSS fix...\n";
$cssContent = <<<'CSS'
/* Widget Display Fix */
.fi-page-header-widgets {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.fi-wi {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Ensure grid layout for widgets */
.fi-widgets-grid {
    display: grid !important;
    gap: 1rem !important;
}

/* Fix for hidden widgets */
.fi-page > .fi-page-header + div:not(.fi-section) {
    display: block !important;
}

/* Stats widget specific fixes */
.fi-wi-stats-overview {
    min-height: auto !important;
}

.fi-wi-stats-overview-stat {
    padding: 1rem !important;
}

/* Live status widget fix */
.fi-wi-live-status-widget {
    display: block !important;
}

/* Global filter widget fix */
.fi-wi-global-filter {
    display: block !important;
}
CSS;

file_put_contents(__DIR__ . '/../resources/css/filament/admin/widget-display-fix.css', $cssContent);
echo "   ✅ CSS fix generated\n\n";

// 6. Update vite config
echo "6. Checking Vite configuration...\n";
$viteConfigPath = __DIR__ . '/../app/Providers/Filament/AdminPanelProvider.php';
$viteConfig = file_get_contents($viteConfigPath);

if (!str_contains($viteConfig, 'widget-display-fix.css')) {
    echo "   ⚠️  Please add 'resources/css/filament/admin/widget-display-fix.css' to viteTheme in AdminPanelProvider\n";
} else {
    echo "   ✅ Widget display fix CSS is included\n";
}
echo "\n";

// 7. Final recommendations
echo "7. Final Steps:\n";
echo "   1. Run: npm run build\n";
echo "   2. Clear browser cache (Ctrl+F5)\n";
echo "   3. If widgets still don't show, check browser console for errors\n";
echo "   4. Verify that widgets have permission to be viewed\n\n";

echo "✨ Fix script completed!\n";