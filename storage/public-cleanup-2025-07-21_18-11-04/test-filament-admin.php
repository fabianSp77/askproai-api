<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filament Admin Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Filament Admin Debug</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">1. Filament Status</h2>
            <dl class="space-y-2">
                <dt class="font-medium">Filament Version:</dt>
                <dd class="text-gray-700"><?php echo class_exists(\Filament\FilamentManager::class) ? 'Filament 3.x installed' : 'Filament not found'; ?></dd>
                
                <dt class="font-medium">Admin Panel Registered:</dt>
                <dd class="text-gray-700"><?php 
                    try {
                        $panels = Filament::getPanels();
                        echo count($panels) . ' panel(s) registered';
                        foreach ($panels as $id => $panel) {
                            echo "<br>- Panel ID: $id";
                        }
                    } catch (Exception $e) {
                        echo 'Error: ' . $e->getMessage();
                    }
                ?></dd>
            </dl>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">2. Admin Routes</h2>
            <pre class="bg-gray-50 p-4 overflow-x-auto text-sm"><?php
            $routes = Route::getRoutes();
            foreach ($routes as $route) {
                $uri = $route->uri();
                if (strpos($uri, 'admin') === 0) {
                    echo str_pad($route->methods()[0] ?? 'ANY', 7) . ' ' . $uri . "\n";
                }
            }
            ?></pre>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">3. Test Admin Access</h2>
            <?php
            try {
                // Create a test request to /admin
                $adminRequest = \Illuminate\Http\Request::create('/admin', 'GET');
                $adminResponse = $kernel->handle($adminRequest);
                
                echo "<p>Admin route response code: <strong>" . $adminResponse->getStatusCode() . "</strong></p>";
                
                if ($adminResponse->getStatusCode() === 500) {
                    // Try to get the actual error
                    $content = $adminResponse->getContent();
                    if (strpos($content, 'React Demo') !== false) {
                        echo "<p class='text-red-600'>⚠️ React Demo content detected!</p>";
                    }
                    
                    // Check if APP_DEBUG is on
                    if (config('app.debug')) {
                        echo "<p>Debug mode is ON - should show error details</p>";
                    } else {
                        echo "<p>Debug mode is OFF - errors are hidden</p>";
                    }
                }
            } catch (Exception $e) {
                echo "<p class='text-red-600'>Error testing admin route: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">4. Check for Route Conflicts</h2>
            <?php
            // Check if there's a public file that might conflict
            $publicFiles = [
                'admin.html',
                'admin/index.html',
                'admin.php',
                'admin/index.php'
            ];
            
            foreach ($publicFiles as $file) {
                $fullPath = public_path($file);
                if (file_exists($fullPath)) {
                    echo "<p class='text-red-600'>⚠️ Found conflicting file: $fullPath</p>";
                } else {
                    echo "<p class='text-green-600'>✓ No conflict: $file</p>";
                }
            }
            ?>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">5. Actions</h2>
            <div class="space-y-2">
                <a href="/admin" class="inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Try Access /admin
                </a>
                <a href="/admin-enhanced.php" class="inline-block px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                    Use Enhanced Admin
                </a>
                <a href="?clear_cache=1" class="inline-block px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">
                    Clear All Caches
                </a>
            </div>
            
            <?php
            if (isset($_GET['clear_cache'])) {
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                echo "<p class='mt-4 text-green-600'>✓ All caches cleared!</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>