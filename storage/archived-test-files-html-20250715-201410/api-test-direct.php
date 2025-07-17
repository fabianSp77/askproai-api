<?php

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Force JSON response
header('Content-Type: application/json');

// Get route info
$routes = app('router')->getRoutes();
$matchingRoutes = [];

foreach ($routes as $route) {
    if (strpos($route->uri(), 'business/api/calls') !== false) {
        $matchingRoutes[] = [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
        ];
    }
}

// Try to call the controller directly
try {
    $controller = new \App\Http\Controllers\Portal\Api\CallApiController();
    
    // Mock authentication
    $portalUser = \App\Models\PortalUser::withoutGlobalScopes()
        ->where('is_active', true)
        ->first();
    
    if ($portalUser) {
        \Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
        app()->instance('current_company_id', $portalUser->company_id);
    }
    
    $callsResponse = $controller->index($request);
    $callsData = json_decode($callsResponse->getContent(), true);
} catch (\Exception $e) {
    $callsData = ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
}

echo json_encode([
    'routes' => $matchingRoutes,
    'current_path' => $request->path(),
    'current_url' => $request->url(),
    'auth_check' => [
        'portal' => \Illuminate\Support\Facades\Auth::guard('portal')->check(),
        'user' => \Illuminate\Support\Facades\Auth::guard('portal')->user() ? \Illuminate\Support\Facades\Auth::guard('portal')->user()->email : null,
    ],
    'calls_data' => $callsData,
], JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);