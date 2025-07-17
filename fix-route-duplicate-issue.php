<?php
/**
 * Route Duplicate Fix Script
 * Behebt das duplicate staff.index Problem
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

echo "🔧 Fixing Route Duplicate Issue\n";
echo "==============================\n\n";

// 1. Analyze current routes
echo "1. Analyzing current routes:\n";
echo "---------------------------\n";

$routes = Route::getRoutes();
$staffRoutes = [];
$duplicates = [];

foreach ($routes as $route) {
    $name = $route->getName();
    $uri = $route->uri();
    
    if ($name && str_contains($name, 'staff')) {
        if (isset($staffRoutes[$name])) {
            $duplicates[$name][] = [
                'uri' => $uri,
                'methods' => implode('|', $route->methods()),
                'action' => $route->getActionName()
            ];
        } else {
            $staffRoutes[$name] = [
                'uri' => $uri,
                'methods' => implode('|', $route->methods()),
                'action' => $route->getActionName()
            ];
        }
    }
}

if (!empty($duplicates)) {
    echo "   ❌ Found duplicate routes:\n";
    foreach ($duplicates as $name => $routes) {
        echo "      - $name\n";
        foreach ($routes as $route) {
            echo "        URI: {$route['uri']} [{$route['methods']}]\n";
            echo "        Action: {$route['action']}\n";
        }
    }
} else {
    echo "   ✅ No duplicate staff routes found\n";
}

// 2. Check route files for duplicate definitions
echo "\n2. Checking route files:\n";
echo "------------------------\n";

$routeFiles = [
    'routes/web.php',
    'routes/api.php',
    'routes/business-portal.php',
    'routes/help-center.php'
];

foreach ($routeFiles as $file) {
    if (File::exists($file)) {
        $content = File::get($file);
        $staffResourceCount = substr_count($content, "Route::resource('staff'") + 
                             substr_count($content, 'Route::resource("staff"');
        
        if ($staffResourceCount > 0) {
            echo "   - $file: Found $staffResourceCount staff resource definition(s)\n";
            
            // Find line numbers
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (str_contains($line, "Route::resource('staff'") || 
                    str_contains($line, 'Route::resource("staff"')) {
                    echo "      Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
                }
            }
        }
    }
}

// 3. Temporary Fix
echo "\n3. Applying temporary fix:\n";
echo "--------------------------\n";

try {
    // Clear route cache
    if (File::exists(base_path('bootstrap/cache/routes-v7.php'))) {
        File::delete(base_path('bootstrap/cache/routes-v7.php'));
        echo "   ✅ Route cache cleared\n";
    } else {
        echo "   ℹ️ No route cache found\n";
    }
    
    // Clear config cache as well
    if (File::exists(base_path('bootstrap/cache/config.php'))) {
        File::delete(base_path('bootstrap/cache/config.php'));
        echo "   ✅ Config cache cleared\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Generate permanent fix suggestions
echo "\n4. Permanent Fix Suggestions:\n";
echo "----------------------------\n";

if (!empty($duplicates)) {
    echo "   To fix the duplicate routes permanently:\n";
    echo "   1. Check all route files listed above\n";
    echo "   2. Remove duplicate Route::resource('staff') definitions\n";
    echo "   3. Consider using route names/prefixes to avoid conflicts:\n";
    echo "      - Route::resource('admin.staff', StaffController::class)\n";
    echo "      - Route::resource('api.staff', ApiStaffController::class)\n";
    echo "   4. Or use route groups with prefixes:\n";
    echo "      Route::prefix('admin')->group(function () {\n";
    echo "          Route::resource('staff', StaffController::class);\n";
    echo "      });\n";
} else {
    echo "   ✅ No permanent fixes needed\n";
}

// 5. Test route functionality
echo "\n5. Testing route functionality:\n";
echo "-------------------------------\n";

$testRoutes = [
    'staff.index' => 'GET',
    'staff.create' => 'GET',
    'staff.store' => 'POST',
    'staff.show' => 'GET',
    'staff.edit' => 'GET',
    'staff.update' => 'PUT',
    'staff.destroy' => 'DELETE'
];

foreach ($testRoutes as $routeName => $method) {
    try {
        $route = Route::getRoutes()->getByName($routeName);
        if ($route) {
            echo "   ✅ $routeName [$method] -> " . $route->uri() . "\n";
        } else {
            echo "   ❌ $routeName not found\n";
        }
    } catch (\Exception $e) {
        echo "   ⚠️ $routeName: " . $e->getMessage() . "\n";
    }
}

// 6. Check for other common route issues
echo "\n6. Checking for other route issues:\n";
echo "-----------------------------------\n";

$allRoutes = [];
foreach ($routes as $route) {
    $key = $route->uri() . '|' . implode('|', $route->methods());
    if (isset($allRoutes[$key])) {
        echo "   ⚠️ Duplicate route pattern: " . $route->uri() . " [" . implode('|', $route->methods()) . "]\n";
    }
    $allRoutes[$key] = true;
}

echo "\n==============================\n";
echo "✅ Route analysis completed\n";
echo "\nRecommended actions:\n";
echo "1. Do NOT run 'php artisan route:cache' until duplicates are fixed\n";
echo "2. Review and fix duplicate route definitions\n";
echo "3. Test application functionality\n";
echo "4. Consider implementing route versioning for API\n";