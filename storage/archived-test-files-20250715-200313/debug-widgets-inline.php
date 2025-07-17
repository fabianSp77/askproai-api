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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Widgets Inline</title>
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    @livewireStyles
    @filamentStyles
    @vite('resources/css/app.css')
    <style>
        .widget-container {
            border: 2px solid #ccc;
            padding: 20px;
            margin: 20px;
            background: #f9f9f9;
        }
        .widget-info {
            background: #e0e0e0;
            padding: 10px;
            margin-bottom: 10px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>Direct Widget Rendering Test</h1>
    
    <?php
    // Test 1: Direct PHP instantiation
    echo '<div class="widget-container">';
    echo '<h2>Test 1: Direct PHP Widget Creation</h2>';
    
    try {
        $widget = new \App\Filament\Admin\Widgets\CallKpiWidget();
        echo '<div class="widget-info">Widget class: ' . get_class($widget) . '</div>';
        echo '<div class="widget-info">Can view: ' . ($widget->canView() ? 'Yes' : 'No') . '</div>';
        
        // Try to get the view
        $view = $widget->render();
        echo '<div class="widget-info">View type: ' . (is_object($view) ? get_class($view) : gettype($view)) . '</div>';
        
        if ($view) {
            echo '<div style="border: 1px solid blue; padding: 10px;">';
            echo $view;
            echo '</div>';
        }
    } catch (\Exception $e) {
        echo '<div style="color: red;">Error: ' . $e->getMessage() . '</div>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    echo '</div>';
    
    // Test 2: Stats from widget directly
    echo '<div class="widget-container">';
    echo '<h2>Test 2: Widget Stats Data</h2>';
    
    try {
        $widget = new \App\Filament\Admin\Widgets\CallKpiWidget();
        $stats = $widget->getStats();
        
        echo '<div class="widget-info">Stats count: ' . count($stats) . '</div>';
        foreach ($stats as $index => $stat) {
            echo '<div style="border: 1px solid green; padding: 10px; margin: 5px;">';
            echo '<strong>Stat ' . ($index + 1) . ':</strong><br>';
            echo 'Label: ' . $stat->getLabel() . '<br>';
            echo 'Value: ' . $stat->getValue() . '<br>';
            if ($stat->getDescription()) {
                echo 'Description: ' . $stat->getDescription() . '<br>';
            }
            echo '</div>';
        }
    } catch (\Exception $e) {
        echo '<div style="color: red;">Error: ' . $e->getMessage() . '</div>';
    }
    echo '</div>';
    
    // Test 3: List all available widgets
    echo '<div class="widget-container">';
    echo '<h2>Test 3: All Available Widgets</h2>';
    
    $widgetClasses = [
        \App\Filament\Admin\Widgets\CallKpiWidget::class,
        \App\Filament\Admin\Widgets\CallLiveStatusWidget::class,
        \App\Filament\Admin\Widgets\GlobalFilterWidget::class,
        \App\Filament\Admin\Resources\CallResource\Widgets\CallAnalyticsWidget::class,
    ];
    
    foreach ($widgetClasses as $widgetClass) {
        echo '<div style="margin: 10px; padding: 10px; background: #f0f0f0;">';
        echo '<strong>' . class_basename($widgetClass) . '</strong><br>';
        
        try {
            $widget = new $widgetClass();
            echo 'Instantiated: ✓<br>';
            echo 'Can View: ' . ($widget->canView() ? '✓' : '✗') . '<br>';
            
            if (method_exists($widget, 'getStats')) {
                $stats = $widget->getStats();
                echo 'Stats Count: ' . count($stats) . '<br>';
            }
            
            if (method_exists($widget, 'getColumns')) {
                echo 'Columns: ' . $widget->getColumns() . '<br>';
            }
        } catch (\Exception $e) {
            echo '<span style="color: red;">Error: ' . $e->getMessage() . '</span>';
        }
        
        echo '</div>';
    }
    echo '</div>';
    ?>
    
    <div class="widget-container">
        <h2>Test 4: Livewire Component Rendering</h2>
        <p>Attempting to render widgets as Livewire components...</p>
        
        <div style="border: 2px solid red; padding: 20px; margin: 10px;">
            <h3>CallKpiWidget (Livewire)</h3>
            @livewire(\App\Filament\Admin\Widgets\CallKpiWidget::class)
        </div>
        
        <div style="border: 2px solid blue; padding: 20px; margin: 10px;">
            <h3>CallLiveStatusWidget (Livewire)</h3>
            @livewire(\App\Filament\Admin\Widgets\CallLiveStatusWidget::class)
        </div>
    </div>
    
    <div class="widget-container">
        <h2>JavaScript Debug</h2>
        <button onclick="checkFrameworks()">Check Frameworks</button>
        <button onclick="forceWidgets()">Force Show Widgets</button>
        <div id="debug-output" style="margin-top: 10px; padding: 10px; background: #000; color: #0f0; font-family: monospace;"></div>
    </div>
    
    <script>
        function checkFrameworks() {
            const output = document.getElementById('debug-output');
            output.innerHTML = `
                Alpine: ${window.Alpine ? 'Loaded (v' + window.Alpine.version + ')' : 'Not loaded'}<br>
                Livewire: ${window.Livewire ? 'Loaded' : 'Not loaded'}<br>
                Widgets found: ${document.querySelectorAll('.fi-wi, .fi-widget').length}<br>
                Wire components: ${document.querySelectorAll('[wire\\:id]').length}
            `;
        }
        
        function forceWidgets() {
            document.querySelectorAll('.fi-wi, .fi-widget, [wire\\:id]').forEach(el => {
                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
                el.style.border = '2px solid lime';
            });
            
            const output = document.getElementById('debug-output');
            output.innerHTML += '<br>Forced visibility on all widgets!';
        }
    </script>
    
    @livewireScripts
    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>