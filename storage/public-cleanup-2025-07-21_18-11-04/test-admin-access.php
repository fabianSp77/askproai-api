<?php
// Simple Admin Panel Access Test
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;

echo "=== Admin Panel Access Test ===\n\n";

// 1. Check if admin route exists
echo "1. Checking admin routes:\n";
$adminRoute = Route::getRoutes()->getByName('filament.admin.pages.dashboard');
if ($adminRoute) {
    echo "✓ Admin dashboard route exists\n";
    echo "  URI: " . $adminRoute->uri() . "\n";
    echo "  Action: " . $adminRoute->getActionName() . "\n";
} else {
    echo "✗ Admin dashboard route not found\n";
}

// 2. Check admin login route
$loginRoute = Route::getRoutes()->getByName('filament.admin.auth.login');
if ($loginRoute) {
    echo "✓ Admin login route exists\n";
    echo "  URI: " . $loginRoute->uri() . "\n";
} else {
    echo "✗ Admin login route not found\n";
}

echo "\n2. Checking Filament installation:\n";
// Check if Filament is installed
if (class_exists('Filament\Panel')) {
    echo "✓ Filament Panel class exists\n";
} else {
    echo "✗ Filament Panel class not found\n";
}

if (class_exists('Filament\FilamentManager')) {
    echo "✓ Filament FilamentManager exists\n";
} else {
    echo "✗ Filament FilamentManager not found\n";
}

echo "\n3. Checking AdminPanelProvider:\n";
$providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
if (file_exists($providerPath)) {
    echo "✓ AdminPanelProvider file exists\n";
    
    // Try to instantiate it
    try {
        $provider = new \App\Providers\Filament\AdminPanelProvider($app);
        echo "✓ AdminPanelProvider can be instantiated\n";
    } catch (Exception $e) {
        echo "✗ Error instantiating AdminPanelProvider: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ AdminPanelProvider file not found\n";
}

echo "\n4. Checking admin user:\n";
$adminUser = User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    echo "✓ Admin user exists\n";
    echo "  ID: " . $adminUser->id . "\n";
    echo "  Email: " . $adminUser->email . "\n";
    echo "  Company ID: " . ($adminUser->company_id ?? 'null') . "\n";
} else {
    echo "✗ Admin user not found\n";
}

echo "\n5. Testing direct admin panel access:\n";
try {
    // Create a request to /admin
    $adminRequest = \Illuminate\Http\Request::create('/admin', 'GET');
    $adminRequest->setLaravelSession($request->session());
    
    // Try to handle the request
    $adminResponse = $kernel->handle($adminRequest);
    echo "Response Status: " . $adminResponse->getStatusCode() . "\n";
    
    if ($adminResponse->getStatusCode() === 500) {
        echo "✗ Got 500 error\n";
        
        // Try to get error details
        $content = $adminResponse->getContent();
        if (strpos($content, 'Exception') !== false) {
            // Extract error message
            preg_match('/<div class="text-2xl">(.*?)<\/div>/s', $content, $matches);
            if (isset($matches[1])) {
                echo "Error: " . strip_tags($matches[1]) . "\n";
            }
        }
    } elseif ($adminResponse->getStatusCode() === 302) {
        echo "✓ Got redirect (expected for unauthenticated access)\n";
        echo "  Redirect to: " . $adminResponse->headers->get('Location') . "\n";
    } else {
        echo "Response received with status: " . $adminResponse->getStatusCode() . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception during admin access: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n";
    $trace = array_slice($e->getTrace(), 0, 5);
    foreach ($trace as $i => $t) {
        echo "    #$i " . ($t['file'] ?? 'unknown') . ":" . ($t['line'] ?? '?') . " " . ($t['function'] ?? 'unknown') . "()\n";
    }
}

echo "\n6. Alternative Access Methods:\n";
echo "- Working admin: https://api.askproai.de/admin-working.php\n";
echo "- Debug panel: https://api.askproai.de/debug-admin-panel.php\n";
echo "- Auth debug: https://api.askproai.de/debug-auth-simple.php\n";

echo "\n=== Test Complete ===\n";