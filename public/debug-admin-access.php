<?php
// Debug script for admin access issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "<h1>Admin Panel Debug Info</h1>";
echo "<h2>Current Time: " . date('Y-m-d H:i:s') . "</h2>";

// Check if Filament is installed
echo "<h3>Filament Status:</h3>";
if (class_exists('Filament\FilamentServiceProvider')) {
    echo "✅ Filament is installed<br>";
} else {
    echo "❌ Filament is NOT installed<br>";
}

// Check admin panel provider
echo "<h3>Admin Panel Provider:</h3>";
if (class_exists('App\Providers\Filament\AdminPanelProvider')) {
    echo "✅ AdminPanelProvider exists<br>";
    
    // Try to get the panel
    try {
        $panel = \Filament\Facades\Filament::getPanel('admin');
        echo "✅ Admin panel is registered<br>";
        echo "Path: " . $panel->getPath() . "<br>";
    } catch (Exception $e) {
        echo "❌ Error getting admin panel: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ AdminPanelProvider NOT found<br>";
}

// Check routes
echo "<h3>Admin Routes:</h3>";
try {
    $routes = app('router')->getRoutes();
    $adminRoutes = [];
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'admin') !== false) {
            $adminRoutes[] = $route->uri() . ' [' . implode(',', $route->methods()) . ']';
        }
    }
    if (count($adminRoutes) > 0) {
        echo "Found " . count($adminRoutes) . " admin routes:<br>";
        foreach (array_slice($adminRoutes, 0, 10) as $route) {
            echo "- " . $route . "<br>";
        }
    } else {
        echo "❌ No admin routes found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking routes: " . $e->getMessage() . "<br>";
}

// Check middleware
echo "<h3>Middleware Issues:</h3>";
try {
    $middleware = $app->make('router')->getMiddleware();
    echo "Registered middleware aliases: " . count($middleware) . "<br>";
    if (isset($middleware['branch.context'])) {
        echo "✅ branch.context middleware is registered<br>";
    } else {
        echo "⚠️ branch.context middleware NOT found in aliases<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking middleware: " . $e->getMessage() . "<br>";
}

// Test database connection
echo "<h3>Database Connection:</h3>";
try {
    \DB::connection()->getPdo();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<a href='/admin/'>Try accessing admin panel</a>";