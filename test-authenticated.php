<?php
// Test script to simulate authenticated admin access

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the application
$request = Illuminate\Http\Request::create('/');
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

// Authenticate as admin
$admin = User::find(6); // Admin user ID 6
if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

Auth::login($admin);
echo "✅ Authenticated as: " . $admin->email . "\n\n";

// Test 1: Check permissions
echo "=== TESTING PERMISSIONS ===\n";
$canViewIntegrations = $admin->can('view_any_integration');
echo "Can view integrations: " . ($canViewIntegrations ? "YES" : "NO") . "\n";

// Test 2: Try to load Integration 6
echo "\n=== TESTING INTEGRATION 6 ===\n";
try {
    $integration = \App\Models\Integration::find(6);
    if ($integration) {
        echo "✅ Integration 6 found: " . $integration->name . "\n";

        // Check if user can view this integration
        $canView = $admin->can('view', $integration);
        echo "Can view this integration: " . ($canView ? "YES" : "NO") . "\n";
    } else {
        echo "❌ Integration 6 not found!\n";
    }
} catch (\Exception $e) {
    echo "❌ Error loading integration: " . $e->getMessage() . "\n";
}

// Test 3: Try to execute IntegrationResource query
echo "\n=== TESTING INTEGRATION RESOURCE QUERY ===\n";
try {
    // Check if IntegrationResource exists
    $resourceClass = '\App\Filament\Resources\IntegrationResource';
    if (!class_exists($resourceClass)) {
        echo "❌ IntegrationResource class not found!\n";
    } else {
        echo "✅ IntegrationResource class exists\n";

        // Try to get the Eloquent query
        $query = $resourceClass::getEloquentQuery();
        echo "✅ Query builder obtained\n";

        // Try to execute the query
        $result = $query->find(6);
        if ($result) {
            echo "✅ Integration 6 loaded via Resource query\n";
        } else {
            echo "❌ Integration 6 not found via Resource query\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error in IntegrationResource: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 4: Check ServiceResource (which was causing issues)
echo "\n=== TESTING SERVICE RESOURCE QUERY ===\n";
try {
    $serviceResourceClass = '\App\Filament\Resources\ServiceResource';
    if (class_exists($serviceResourceClass)) {
        echo "✅ ServiceResource class exists\n";

        // Get the table query
        $query = $serviceResourceClass::getEloquentQuery();

        // Check if withCount is properly set
        echo "Testing withCount query...\n";
        $services = $query->limit(1)->get();
        echo "✅ ServiceResource query executed successfully\n";

        if ($services->count() > 0) {
            $service = $services->first();
            echo "Sample service: " . $service->name . "\n";

            // Check if the counts are available
            if (isset($service->upcoming_appointments)) {
                echo "✅ WithCount fields are working\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error in ServiceResource: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 5: Simulate actual page request
echo "\n=== SIMULATING PAGE REQUEST ===\n";
try {
    // Create a request for the integrations page
    $request = Illuminate\Http\Request::create('/admin/integrations/6/edit', 'GET');
    $request->setUserResolver(function () use ($admin) { return $admin; });

    // Try to handle the request
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);

    $statusCode = $response->getStatusCode();
    echo "Response status: " . $statusCode . "\n";

    if ($statusCode === 500) {
        echo "❌ Got 500 error!\n";

        // Try to get error details
        $content = $response->getContent();
        if (strpos($content, 'Exception') !== false) {
            // Extract error message
            preg_match('/<div class="message">(.*?)<\/div>/s', $content, $matches);
            if (isset($matches[1])) {
                echo "Error message: " . strip_tags($matches[1]) . "\n";
            }
        }
    } elseif ($statusCode === 200) {
        echo "✅ Page loaded successfully!\n";
    } else {
        echo "Got status code: " . $statusCode . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception during request: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
