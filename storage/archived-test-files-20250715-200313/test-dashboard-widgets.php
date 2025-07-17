<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Login as admin
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    \Illuminate\Support\Facades\Auth::login($admin);
}

// Get the dashboard page
$dashboard = new \App\Filament\Admin\Pages\OperationsDashboard();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Widget Debug</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f3f4f6; }
        .widget-info { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Dashboard Widget Debug</h1>
    
    <div class="widget-info">
        <h2>Dashboard Configuration</h2>
        <p><strong>Dashboard Class:</strong> <?php echo get_class($dashboard); ?></p>
        <p><strong>Widget Count:</strong> <?php echo count($dashboard->getWidgets()); ?></p>
    </div>
    
    <div class="widget-info">
        <h2>Expected Widgets</h2>
        <ol>
        <?php foreach ($dashboard->getWidgets() as $widgetClass): ?>
            <li>
                <strong><?php echo class_basename($widgetClass); ?></strong>
                <br>
                Full class: <?php echo $widgetClass; ?>
                <br>
                <?php if (class_exists($widgetClass)): ?>
                    <span class="success">✓ Class exists</span>
                    <?php
                    try {
                        $widget = new $widgetClass();
                        echo '<span class="success"> | ✓ Can instantiate</span>';
                        
                        // Check if it has required methods
                        if (method_exists($widget, 'getViewData')) {
                            echo '<span class="success"> | ✓ Has getViewData()</span>';
                        }
                        
                        // Check view file using reflection
                        $reflection = new ReflectionClass($widget);
                        if ($reflection->hasProperty('view')) {
                            $viewProperty = $reflection->getProperty('view');
                            $viewProperty->setAccessible(true);
                            $viewName = $viewProperty->getValue($widget);
                            echo '<br>View: ' . $viewName;
                            
                            if (view()->exists($viewName)) {
                                echo '<span class="success"> | ✓ View exists</span>';
                            } else {
                                echo '<span class="error"> | ✗ View missing</span>';
                            }
                        } elseif (method_exists($widget, 'getView')) {
                            $viewName = $widget->getView();
                            echo '<br>View: ' . $viewName;
                            
                            if (view()->exists($viewName)) {
                                echo '<span class="success"> | ✓ View exists</span>';
                            } else {
                                echo '<span class="error"> | ✗ View missing</span>';
                            }
                        }
                        
                    } catch (\Exception $e) {
                        echo '<span class="error"> | ✗ Error: ' . $e->getMessage() . '</span>';
                    }
                    ?>
                <?php else: ?>
                    <span class="error">✗ Class does not exist</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ol>
    </div>
    
    <div class="widget-info">
        <h2>FilterableWidget Base Class Check</h2>
        <?php
        if (class_exists(\App\Filament\Admin\Widgets\FilterableWidget::class)) {
            echo '<span class="success">✓ FilterableWidget base class exists</span>';
            
            // Check if it has required methods
            $reflection = new ReflectionClass(\App\Filament\Admin\Widgets\FilterableWidget::class);
            echo '<h3>Methods:</h3><ul>';
            foreach ($reflection->getMethods() as $method) {
                if ($method->class == \App\Filament\Admin\Widgets\FilterableWidget::class) {
                    echo '<li>' . $method->name . '</li>';
                }
            }
            echo '</ul>';
        } else {
            echo '<span class="error">✗ FilterableWidget base class does not exist</span>';
        }
        ?>
    </div>
    
    <div class="widget-info">
        <h2>Widget View Files</h2>
        <?php
        $viewPath = resource_path('views/filament/admin/widgets');
        if (is_dir($viewPath)) {
            echo '<p>Widget view directory exists: ' . $viewPath . '</p>';
            echo '<ul>';
            foreach (glob($viewPath . '/*.blade.php') as $file) {
                echo '<li>' . basename($file) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<span class="error">Widget view directory does not exist!</span>';
        }
        ?>
    </div>
    
    <div class="widget-info">
        <h2>Test Widget Render</h2>
        <?php
        // Try to render CompactOperationsWidget
        try {
            $widgetClass = \App\Filament\Admin\Widgets\CompactOperationsWidget::class;
            echo '<h3>Testing: ' . class_basename($widgetClass) . '</h3>';
            
            if (class_exists($widgetClass)) {
                $widget = new $widgetClass();
                
                // Mount the widget
                if (method_exists($widget, 'mount')) {
                    $widget->mount();
                    echo '<p class="success">✓ Widget mounted successfully</p>';
                }
                
                // Set company ID if needed
                if (property_exists($widget, 'companyId')) {
                    $widget->companyId = $admin->company_id;
                    echo '<p>Company ID set to: ' . $widget->companyId . '</p>';
                }
                
                // Check if getViewData exists (it's protected, so we can't call it directly)
                $reflection = new ReflectionClass($widget);
                if ($reflection->hasMethod('getViewData')) {
                    $method = $reflection->getMethod('getViewData');
                    echo '<p class="success">✓ Has getViewData method (visibility: ' . ($method->isPublic() ? 'public' : ($method->isProtected() ? 'protected' : 'private')) . ')</p>';
                } else {
                    echo '<p class="error">✗ No getViewData method</p>';
                }
                
                // Check if widget can be rendered
                $viewName = null;
                $reflection = new ReflectionClass($widget);
                if ($reflection->hasProperty('view')) {
                    $viewProperty = $reflection->getProperty('view');
                    $viewProperty->setAccessible(true);
                    $viewName = $viewProperty->getValue($widget);
                }
                
                if ($viewName && view()->exists($viewName)) {
                    echo '<p class="success">✓ Widget view exists and can be rendered</p>';
                    echo '<p>View path: ' . $viewName . '</p>';
                } else {
                    echo '<p class="error">✗ Widget view not found or cannot be rendered</p>';
                }
            }
        } catch (\Exception $e) {
            echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }
        ?>
    </div>
    
    <div class="widget-info">
        <h2>Dashboard Page Method Check</h2>
        <?php
        try {
            $dashboard = new \App\Filament\Admin\Pages\OperationsDashboard();
            
            // Check required methods
            $methods = ['getWidgets', 'getVisibleWidgets', 'getColumns', 'getWidgetData'];
            foreach ($methods as $method) {
                if (method_exists($dashboard, $method)) {
                    echo '<p class="success">✓ ' . $method . '() exists</p>';
                    
                    // Call the method and show result
                    if ($method === 'getColumns') {
                        $result = $dashboard->$method();
                        echo '<pre>Result: ' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                    }
                } else {
                    echo '<p class="error">✗ ' . $method . '() missing</p>';
                }
            }
        } catch (\Exception $e) {
            echo '<p class="error">Error checking dashboard methods: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="widget-info">
        <h2>Actions</h2>
        <a href="/admin" style="display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;">Go to Dashboard</a>
        <a href="/admin/dashboard" style="display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">Go Directly to Operations Dashboard</a>
    </div>
    
    <div class="widget-info">
        <h2>Widget Instantiation Test</h2>
        <?php
        // Test instantiating all dashboard widgets
        $widgets = [
            \App\Filament\Admin\Widgets\CompactOperationsWidget::class,
            \App\Filament\Admin\Widgets\InsightsActionsWidget::class,
            \App\Filament\Admin\Widgets\FinancialIntelligenceWidget::class,
            \App\Filament\Admin\Widgets\BranchPerformanceMatrixWidget::class,
            \App\Filament\Admin\Widgets\LiveActivityFeedWidget::class,
        ];
        
        foreach ($widgets as $widgetClass) {
            echo '<h3>' . class_basename($widgetClass) . '</h3>';
            try {
                $widget = new $widgetClass();
                $widget->mount();
                
                // Get view path
                $reflection = new ReflectionClass($widget);
                $viewProperty = $reflection->getProperty('view');
                $viewProperty->setAccessible(true);
                $viewName = $viewProperty->getValue($widget);
                
                if (view()->exists($viewName)) {
                    echo '<p class="success">✓ Widget instantiated and view exists</p>';
                    
                    // Try to render the widget using the render method
                    try {
                        // Widgets render themselves via the render() method
                        if (method_exists($widget, 'render')) {
                            $view = $widget->render();
                            $html = $view->render();
                            echo '<p class="success">✓ Widget can be rendered (HTML length: ' . strlen($html) . ' chars)</p>';
                            
                            // Show a small preview
                            $preview = strip_tags(substr($html, 0, 200));
                            echo '<p>Preview: <em>' . htmlspecialchars($preview) . '...</em></p>';
                        } else {
                            echo '<p class="error">✗ No render method found</p>';
                        }
                    } catch (\Exception $e) {
                        echo '<p class="error">✗ Render error: ' . $e->getMessage() . '</p>';
                    }
                } else {
                    echo '<p class="error">✗ View not found: ' . $viewName . '</p>';
                }
            } catch (\Exception $e) {
                echo '<p class="error">✗ Cannot instantiate: ' . $e->getMessage() . '</p>';
            }
            echo '<hr>';
        }
        ?>
    </div>
</body>
</html>