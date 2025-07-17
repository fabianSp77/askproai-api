<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a dummy request to bootstrap the app
$bootstrapRequest = \Illuminate\Http\Request::create('/', 'GET');
$kernel->handle($bootstrapRequest);

// Find a portal user
$portalUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('is_active', true)
    ->first();

if (!$portalUser) {
    die("No active portal user found\n");
}

echo "Testing with portal user: {$portalUser->email}\n";
echo "Company ID: {$portalUser->company_id}\n\n";

// Create the actual test request
$request = \Illuminate\Http\Request::create(
    '/business/api/calls',
    'GET',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
);

// Set up session as if user is logged in
$portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
session([$portalSessionKey => $portalUser->id]);
session(['portal_user_id' => $portalUser->id]);

// Execute the request
$response = $kernel->handle($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Headers:\n";
foreach ($response->headers->all() as $key => $values) {
    foreach ($values as $value) {
        echo "  $key: $value\n";
    }
}
echo "\nResponse Body:\n";
$content = $response->getContent();
if (strlen($content) > 1000) {
    echo substr($content, 0, 1000) . "...\n";
} else {
    echo $content . "\n";
}

// Check if CallApiController exists
echo "\n\nController Check:\n";
$controllerClass = 'App\Http\Controllers\Portal\Api\CallApiController';
echo "CallApiController exists: " . (class_exists($controllerClass) ? 'Yes' : 'No') . "\n";

// List routes
echo "\n\nRoutes matching '/business/api/calls':\n";
$routes = app('router')->getRoutes();
foreach ($routes as $route) {
    if (strpos($route->uri(), 'business/api/calls') !== false) {
        echo "  " . $route->methods()[0] . " " . $route->uri() . " -> " . $route->getActionName() . "\n";
    }
}

$kernel->terminate($request, $response);