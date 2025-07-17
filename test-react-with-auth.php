<?php
// Test React app with authenticated user

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

echo "=== Testing React App with Auth ===\n\n";

// Get demo user
$demoUser = PortalUser::where('email', 'demo@business.portal')->first();
if (!$demoUser) {
    die("❌ Demo user not found. Run create-demo-portal-user.php first.\n");
}

echo "✅ Demo user found: {$demoUser->email}\n\n";

// Login the user
Auth::guard('portal')->login($demoUser);

// Create authenticated request to business portal
$authenticatedRequest = \Illuminate\Http\Request::create('/business', 'GET');
$authenticatedRequest->setUserResolver(function () use ($demoUser) {
    return $demoUser;
});

// Set session
$session = $app->make('session.store');
$session->put('login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal'), $demoUser->id);
$session->put('portal_user_id', $demoUser->id);
$authenticatedRequest->setLaravelSession($session);

echo "Making authenticated request to /business...\n";

try {
    $testResponse = $app->handle($authenticatedRequest);
    $statusCode = $testResponse->getStatusCode();
    $content = $testResponse->getContent();
    
    echo "Response status: $statusCode\n";
    
    if ($statusCode == 302) {
        echo "Redirected to: " . $testResponse->headers->get('Location') . "\n";
    } else {
        // Check for React app markers
        $hasAppDiv = strpos($content, 'id="app"') !== false;
        $hasReactAssets = strpos($content, '/build/assets/') !== false;
        $hasPortalApp = strpos($content, 'PortalApp') !== false;
        
        echo "\nReact App Checks:\n";
        echo "- App div found: " . ($hasAppDiv ? '✅ Yes' : '❌ No') . "\n";
        echo "- Vite assets found: " . ($hasReactAssets ? '✅ Yes' : '❌ No') . "\n";
        echo "- PortalApp reference: " . ($hasPortalApp ? '✅ Yes' : '❌ No') . "\n";
        
        if (!$hasAppDiv || !$hasReactAssets) {
            echo "\n⚠️  React app not being served!\n";
            echo "Content preview:\n";
            echo substr($content, 0, 500) . "...\n";
            
            // Save full response for inspection
            file_put_contents('business-portal-response.html', $content);
            echo "\nFull response saved to business-portal-response.html\n";
        } else {
            echo "\n✅ React app is being served correctly!\n";
            
            // Extract asset URLs
            preg_match_all('/\/build\/assets\/[^"\']+/', $content, $assets);
            if ($assets[0]) {
                echo "\nLoaded assets:\n";
                foreach (array_unique($assets[0]) as $asset) {
                    if (strpos($asset, 'PortalApp') !== false) {
                        echo "- $asset ✨\n";
                    } else {
                        echo "- $asset\n";
                    }
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Test other routes
echo "\n=== Testing Other Routes ===\n";
$testRoutes = [
    '/business/dashboard' => 'Dashboard',
    '/business/calls' => 'Calls',
    '/business/settings' => 'Settings',
];

foreach ($testRoutes as $route => $name) {
    $testRequest = \Illuminate\Http\Request::create($route, 'GET');
    $testRequest->setUserResolver(function () use ($demoUser) {
        return $demoUser;
    });
    $testRequest->setLaravelSession($session);
    
    try {
        $response = $app->handle($testRequest);
        echo "$name ($route): " . $response->getStatusCode();
        if ($response->getStatusCode() == 302) {
            echo " → " . $response->headers->get('Location');
        }
        echo "\n";
    } catch (\Exception $e) {
        echo "$name ($route): ERROR\n";
    }
}