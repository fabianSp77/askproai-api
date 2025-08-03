<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Login as admin
$admin = User::where('email', 'admin@example.com')->first();
if (!$admin) {
    die("Admin user not found\n");
}

auth()->login($admin);

// Test routes
$testRoutes = [
    '/admin' => 'Dashboard',
    '/admin/calls' => 'Calls',
    '/admin/customers' => 'Customers',
    '/admin/appointments' => 'Appointments',
    '/admin/companies' => 'Companies',
    '/admin/branches' => 'Branches',
    '/admin/staff' => 'Staff',
];

echo "Testing Admin Panel Navigation\n";
echo "==============================\n\n";

foreach ($testRoutes as $route => $name) {
    echo "Testing: $name ($route)\n";
    
    // Create request
    $request = Request::create($route, 'GET');
    $request->setLaravelSession($app['session']->driver());
    $request->setUserResolver(function () use ($admin) {
        return $admin;
    });
    
    try {
        // Handle request
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        
        if ($statusCode === 200) {
            echo "✅ SUCCESS - Status: $statusCode\n";
            
            // Check for common issues in response
            $content = $response->getContent();
            if (strpos($content, 'pointer-events: none') !== false) {
                echo "⚠️  WARNING: Found 'pointer-events: none' in response\n";
            }
            if (strpos($content, 'universal-click-handler.js') !== false) {
                echo "✅ Universal click handler is loaded\n";
            }
        } else {
            echo "❌ FAILED - Status: $statusCode\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test if JavaScript files exist
echo "\nChecking JavaScript Files:\n";
echo "==========================\n";

$jsFiles = [
    'public/js/universal-click-handler.js',
    'public/js/console-cleanup.js',
];

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}