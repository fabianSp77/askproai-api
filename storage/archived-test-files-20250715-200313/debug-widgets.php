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
    die("Admin user not found. Create admin@askproai.de first.");
}
\Illuminate\Support\Facades\Auth::login($admin);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Widget Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .widget-check { 
            margin: 20px 0; 
            padding: 15px; 
            border: 1px solid #ddd; 
            border-radius: 5px;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
        pre { 
            background: #f4f4f4; 
            padding: 10px; 
            border-radius: 3px; 
            overflow-x: auto;
        }
        .section {
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>🔍 Widget Debug Dashboard</h1>

    <div class="section">
        <h2>1. Widget Classes Check</h2>
        <?php
        $widgetClasses = [
            \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
            \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
            \App\Filament\Admin\Widgets\CallKpiWidget::class,
            \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
        ];
        
        foreach ($widgetClasses as $widgetClass) {
            echo '<div class="widget-check">';
            echo '<strong>' . $widgetClass . '</strong><br>';
            
            if (class_exists($widgetClass)) {
                echo '<span class="success">✓ Class exists</span><br>';
                
                // Check if widget can be instantiated
                try {
                    $widget = new $widgetClass();
                    echo '<span class="success">✓ Can be instantiated</span><br>';
                    
                    // Check view
                    $reflection = new ReflectionClass($widgetClass);
                    $viewProperty = $reflection->getProperty('view');
                    $viewProperty->setAccessible(true);
                    $viewName = $viewProperty->getValue($widget);
                    
                    if ($viewName) {
                        echo 'View: <code>' . $viewName . '</code><br>';
                        
                        // Check if view exists
                        try {
                            if (view()->exists($viewName)) {
                                echo '<span class="success">✓ View exists</span>';
                            } else {
                                echo '<span class="error">✗ View not found</span>';
                            }
                        } catch (\Exception $e) {
                            echo '<span class="error">✗ View check failed: ' . $e->getMessage() . '</span>';
                        }
                    } else {
                        echo '<span class="info">ℹ No custom view defined (using default)</span>';
                    }
                    
                } catch (\Exception $e) {
                    echo '<span class="error">✗ Cannot instantiate: ' . $e->getMessage() . '</span>';
                }
                
            } else {
                echo '<span class="error">✗ Class does not exist</span>';
            }
            echo '</div>';
        }
        ?>
    </div>

    <div class="section">
        <h2>2. ListCalls Page Analysis</h2>
        <?php
        $listCallsPage = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
        
        echo '<div class="widget-check info">';
        echo '<h3>getHeaderWidgets() method output:</h3>';
        echo '<pre>';
        $headerWidgets = $listCallsPage->getHeaderWidgets();
        print_r($headerWidgets);
        echo '</pre>';
        echo '</div>';
        ?>
    </div>

    <div class="section">
        <h2>3. Widget Registration Check</h2>
        <?php
        echo '<div class="widget-check info">';
        
        // Check Filament panel configuration
        $panel = \Filament\Facades\Filament::getPanel('admin');
        if ($panel) {
            echo '<h3>Admin Panel Configuration:</h3>';
            echo '<pre>';
            
            // Get widgets from panel
            $widgets = $panel->getWidgets();
            echo "Registered widgets count: " . count($widgets) . "\n\n";
            
            foreach ($widgets as $widget) {
                echo "- " . $widget . "\n";
            }
            
            echo '</pre>';
        } else {
            echo '<span class="error">Admin panel not found</span>';
        }
        echo '</div>';
        ?>
    </div>

    <div class="section">
        <h2>4. Call Data Check</h2>
        <?php
        echo '<div class="widget-check info">';
        $company = $admin->company;
        
        if ($company) {
            echo '<h3>Company: ' . $company->name . '</h3>';
            
            // Get call count
            $totalCalls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $company->id)
                ->count();
            
            $recentCalls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $company->id)
                ->where('start_timestamp', '>=', now()->subHours(24))
                ->count();
            
            echo '<pre>';
            echo "Total calls: " . $totalCalls . "\n";
            echo "Calls in last 24 hours: " . $recentCalls . "\n";
            echo '</pre>';
        } else {
            echo '<span class="error">No company associated with admin user</span>';
        }
        echo '</div>';
        ?>
    </div>

    <div class="section">
        <h2>5. Try Rendering Widgets</h2>
        <?php
        foreach ($widgetClasses as $widgetClass) {
            if (class_exists($widgetClass)) {
                echo '<div class="widget-check">';
                echo '<h3>' . basename(str_replace('\\', '/', $widgetClass)) . '</h3>';
                
                try {
                    // Try to get widget data
                    $widget = new $widgetClass();
                    
                    if (method_exists($widget, 'getViewData')) {
                        $reflection = new ReflectionMethod($widget, 'getViewData');
                        $reflection->setAccessible(true);
                        $data = $reflection->invoke($widget);
                        
                        echo '<h4>Widget Data:</h4>';
                        echo '<pre>';
                        print_r($data);
                        echo '</pre>';
                    }
                    
                } catch (\Exception $e) {
                    echo '<span class="error">Error: ' . $e->getMessage() . '</span>';
                }
                
                echo '</div>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>6. Session & Auth Check</h2>
        <?php
        echo '<div class="widget-check info">';
        echo '<pre>';
        echo "Authenticated: " . (auth()->check() ? 'Yes' : 'No') . "\n";
        if (auth()->check()) {
            echo "User: " . auth()->user()->email . "\n";
            echo "User ID: " . auth()->user()->id . "\n";
            echo "Company ID: " . (auth()->user()->company_id ?? 'None') . "\n";
        }
        echo "\nSession ID: " . session()->getId() . "\n";
        echo "Session Driver: " . config('session.driver') . "\n";
        echo '</pre>';
        echo '</div>';
        ?>
    </div>

    <div class="section">
        <h2>7. Recommendations</h2>
        <div class="widget-check info">
            <h3>🔧 Potential Fixes:</h3>
            <ol>
                <li><strong>Clear all caches:</strong>
                    <pre>php artisan optimize:clear
php artisan filament:cache-components
php artisan view:clear</pre>
                </li>
                <li><strong>Register widgets in panel provider:</strong>
                    <p>Check if widgets need to be registered in <code>AdminPanelProvider.php</code></p>
                </li>
                <li><strong>Check widget views:</strong>
                    <p>Ensure all widget blade views exist in <code>resources/views/filament/admin/widgets/</code></p>
                </li>
                <li><strong>Verify permissions:</strong>
                    <p>Make sure the user has permission to view these widgets</p>
                </li>
            </ol>
        </div>
    </div>
</body>
</html>