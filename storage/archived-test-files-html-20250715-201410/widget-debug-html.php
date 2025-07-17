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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Widget HTML Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .widget-test { 
            margin: 10px 0; 
            padding: 20px; 
            background: #f0f0f0; 
            border: 2px dashed blue;
        }
        pre { background: #f4f4f4; padding: 10px; overflow: auto; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Widget HTML Debug</h1>

    <div class="section">
        <h2>1. Check Widget HTML Structure</h2>
        <?php
        // Simulate the ListCalls page context
        $page = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
        
        // Get header widgets
        $reflection = new ReflectionMethod($page, 'getHeaderWidgets');
        $reflection->setAccessible(true);
        $headerWidgets = $reflection->invoke($page);
        
        echo "<p>Header widgets count: " . count($headerWidgets) . "</p>";
        ?>
    </div>

    <div class="section">
        <h2>2. Try Rendering Each Widget</h2>
        
        <?php foreach ($headerWidgets as $widgetClass): ?>
            <div class="widget-test">
                <h3><?php echo basename(str_replace('\\', '/', $widgetClass)); ?></h3>
                
                <?php
                try {
                    // Create widget instance
                    $widget = new $widgetClass();
                    
                    // Get the Livewire component name
                    $componentName = str_replace(['\\', 'App\\'], ['', 'app.'], $widgetClass);
                    $componentName = str_replace('.', '.', strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $componentName)));
                    
                    echo "<p>Component name: <code>$componentName</code></p>";
                    
                    // Check if it's a Livewire component
                    if (method_exists($widget, 'render')) {
                        echo "<p class='success'>✓ Has render method</p>";
                        
                        // Try to render
                        try {
                            if ($widget instanceof \Filament\Widgets\StatsOverviewWidget) {
                                // For stats widgets, get the stats first
                                $statsMethod = new ReflectionMethod($widget, 'getStats');
                                $statsMethod->setAccessible(true);
                                $stats = $statsMethod->invoke($widget);
                                
                                echo "<p>Stats count: " . count($stats) . "</p>";
                                
                                if (!empty($stats)) {
                                    echo "<div style='border: 1px solid green; padding: 10px; margin: 10px 0;'>";
                                    foreach ($stats as $stat) {
                                        echo "<div style='padding: 5px; background: white; margin: 5px;'>";
                                        echo "<strong>" . $stat->getLabel() . ":</strong> " . $stat->getValue();
                                        echo "</div>";
                                    }
                                    echo "</div>";
                                }
                            }
                            
                            // Get the view
                            if (property_exists($widget, 'view')) {
                                $viewReflection = new ReflectionProperty($widgetClass, 'view');
                                $viewReflection->setAccessible(true);
                                $view = $viewReflection->getValue($widget);
                                echo "<p>View: <code>$view</code></p>";
                            }
                            
                        } catch (\Exception $e) {
                            echo "<p class='error'>Render error: " . $e->getMessage() . "</p>";
                        }
                    }
                    
                } catch (\Exception $e) {
                    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section">
        <h2>3. Check Livewire Component Registration</h2>
        <?php
        $livewire = app('livewire');
        
        echo "<pre>";
        echo "Livewire version: " . \Composer\InstalledVersions::getVersion('livewire/livewire') . "\n";
        echo "Filament version: " . \Composer\InstalledVersions::getVersion('filament/filament') . "\n";
        echo "</pre>";
        ?>
    </div>

    <div class="section">
        <h2>4. Manual Widget HTML Test</h2>
        <p>Testing if widgets render when called directly:</p>
        
        <?php
        // Try to render a simple stats widget manually
        $testWidget = new \App\Filament\Admin\Widgets\CallKpiWidget();
        
        echo "<div style='border: 2px solid red; padding: 20px;'>";
        echo "<h4>CallKpiWidget Manual Render:</h4>";
        
        try {
            // For Filament widgets in v3, we need to use the Livewire component
            $componentClass = get_class($testWidget);
            
            // Register it as a Livewire component if not already
            $alias = 'test-widget-' . uniqid();
            \Livewire\Livewire::component($alias, $componentClass);
            
            // Render using Livewire syntax
            echo "@livewire('$alias')";
            echo "<p class='success'>Widget component registered as: $alias</p>";
            
        } catch (\Exception $e) {
            echo "<p class='error'>Manual render failed: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        ?>
    </div>

    <div class="section">
        <h2>5. Check Filament Page Structure</h2>
        <?php
        // Check if widgets are being included in the page
        $listCallsPage = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
        
        echo "<pre>";
        echo "Page class: " . get_class($listCallsPage) . "\n";
        echo "Parent classes: \n";
        $class = new ReflectionClass($listCallsPage);
        while ($parent = $class->getParentClass()) {
            echo "  - " . $parent->getName() . "\n";
            $class = $parent;
        }
        echo "</pre>";
        
        // Check for getCachedHeaderWidgets method
        if (method_exists($listCallsPage, 'getCachedHeaderWidgets')) {
            echo "<p class='success'>✓ Has getCachedHeaderWidgets method</p>";
        } else {
            echo "<p class='error'>✗ Missing getCachedHeaderWidgets method</p>";
        }
        ?>
    </div>

    <script>
        console.log('[Widget Debug] Page loaded');
        
        // Check for Livewire components in DOM
        setTimeout(() => {
            const livewireComponents = document.querySelectorAll('[wire\\:id]');
            console.log('[Widget Debug] Livewire components found:', livewireComponents.length);
            
            const widgets = document.querySelectorAll('.fi-wi');
            console.log('[Widget Debug] Widget elements found:', widgets.length);
        }, 1000);
    </script>
</body>
</html>