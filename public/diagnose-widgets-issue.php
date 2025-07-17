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

// Get the ListCalls page instance
$listCallsPage = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();

// Use reflection to access protected method
$reflection = new ReflectionClass($listCallsPage);
$method = $reflection->getMethod('getHeaderWidgets');
$method->setAccessible(true);
$widgets = $method->invoke($listCallsPage);

echo "=== Widget Diagnosis ===\n\n";

echo "1. Configured Header Widgets:\n";
foreach ($widgets as $widget) {
    echo "   - $widget\n";
}

echo "\n2. Widget Status:\n";
foreach ($widgets as $widgetClass) {
    echo "\n   Widget: " . basename(str_replace('\\', '/', $widgetClass)) . "\n";
    
    // Check class exists
    if (!class_exists($widgetClass)) {
        echo "   ❌ Class does not exist\n";
        continue;
    }
    echo "   ✅ Class exists\n";
    
    // Check if can instantiate
    try {
        $instance = new $widgetClass();
        echo "   ✅ Can instantiate\n";
    } catch (\Exception $e) {
        echo "   ❌ Cannot instantiate: " . $e->getMessage() . "\n";
        continue;
    }
    
    // Check view
    if (property_exists($instance, 'view')) {
        $viewReflection = new ReflectionProperty($widgetClass, 'view');
        $viewReflection->setAccessible(true);
        $view = $viewReflection->getValue($instance);
        
        if ($view) {
            echo "   View: $view\n";
            if (view()->exists($view)) {
                echo "   ✅ View exists\n";
            } else {
                echo "   ❌ View missing\n";
            }
        }
    }
    
    // For StatsOverviewWidget, check if getStats returns data
    if ($instance instanceof \Filament\Widgets\StatsOverviewWidget) {
        try {
            $statsMethod = new ReflectionMethod($instance, 'getStats');
            $statsMethod->setAccessible(true);
            $stats = $statsMethod->invoke($instance);
            echo "   Stats count: " . count($stats) . "\n";
            if (count($stats) > 0) {
                echo "   ✅ Returns stats data\n";
            } else {
                echo "   ⚠️  No stats returned (might be due to no data)\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ Error getting stats: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n3. Call Data Check:\n";
$company = $admin->company;
if ($company) {
    $totalCalls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->count();
    
    $todayCalls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereDate('start_timestamp', today())
        ->count();
    
    echo "   Company: " . $company->name . "\n";
    echo "   Total calls: $totalCalls\n";
    echo "   Today's calls: $todayCalls\n";
} else {
    echo "   ❌ No company associated with admin\n";
}

echo "\n4. Filament Configuration:\n";
$panel = \Filament\Facades\Filament::getPanel('admin');
echo "   Panel ID: " . $panel->getId() . "\n";

// Check if widgets are discoverable
$discoverableWidgets = $panel->getWidgets();
echo "   Total registered widgets: " . count($discoverableWidgets) . "\n";

echo "\n5. Potential Issues:\n";
$issues = [];

// Check if widgets are being overridden by panel registration
foreach ($widgets as $widgetClass) {
    if (!in_array($widgetClass, $discoverableWidgets)) {
        $issues[] = "Widget $widgetClass is not in panel's discoverable widgets";
    }
}

// Check for CSS/JS issues
if (empty($issues)) {
    $issues[] = "Widgets are configured correctly, issue might be CSS/JavaScript related";
    $issues[] = "Try: php artisan filament:assets";
    $issues[] = "Try: Clear browser cache and hard refresh (Ctrl+F5)";
}

foreach ($issues as $issue) {
    echo "   ⚠️  $issue\n";
}

echo "\n6. Fix Commands:\n";
echo "   php artisan optimize:clear\n";
echo "   php artisan filament:assets\n";
echo "   php artisan view:clear\n";
echo "   php artisan cache:clear\n";

echo "\n=== End Diagnosis ===\n";