<?php
// Direct test of error page
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap Laravel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Test the error catalog route
$_SERVER['REQUEST_URI'] = '/errors';
$_SERVER['REQUEST_METHOD'] = 'GET';

$errorRequest = Illuminate\Http\Request::create('/errors', 'GET');
$app->instance('request', $errorRequest);

try {
    $router = $app->make('router');
    $route = $router->getRoutes()->match($errorRequest);
    $errorRequest->setRouteResolver(function () use ($route) {
        return $route;
    });
    
    $controller = new \App\Http\Controllers\ErrorCatalogController();
    $view = $controller->index($errorRequest);
    
    if ($view instanceof \Illuminate\View\View) {
        echo "<h1>✅ Error Catalog View Test</h1>";
        
        $data = $view->getData();
        echo "<h2>View Data:</h2>";
        echo "<pre>";
        echo "Errors count: " . ($data['errors'] ? $data['errors']->count() : 0) . "\n";
        echo "Total errors: " . $data['totalErrors'] . "\n";
        echo "Categories: " . count($data['categories']) . "\n";
        echo "Tags: " . $data['allTags']->count() . "\n";
        echo "</pre>";
        
        echo "<h2>First 3 Errors:</h2>";
        if ($data['errors']->count() > 0) {
            echo "<ul>";
            foreach ($data['errors']->take(3) as $error) {
                echo "<li><strong>{$error->error_code}</strong>: {$error->title} ({$error->severity})</li>";
            }
            echo "</ul>";
        }
        
        // Render the actual view
        echo "<h2>Rendered View Preview:</h2>";
        echo "<div style='border: 2px solid #ccc; padding: 20px; margin: 20px 0;'>";
        try {
            $html = $view->render();
            // Show just a snippet
            echo substr($html, 0, 1000) . "...";
        } catch (\Exception $e) {
            echo "<p style='color: red;'>❌ Render Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
    } else {
        echo "<h1>❌ Error: View not returned</h1>";
    }
    
} catch (\Exception $e) {
    echo "<h1>❌ Exception</h1>";
    echo "<pre>";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine();
    echo "</pre>";
}