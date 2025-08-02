<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize the application
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "<h1>Business Route Diagnostic</h1>";
echo "<pre>";

// Check session
echo "=== SESSION STATUS ===\n";
echo "Session ID: " . session()->getId() . "\n";
echo "Session Started: " . (session()->isStarted() ? 'Yes' : 'No') . "\n";
echo "Portal User ID: " . (session('portal_user_id') ?: 'Not set') . "\n";

// Check auth
echo "\n=== AUTH STATUS ===\n";
echo "Portal Auth: " . (Auth::guard('portal')->check() ? 'Logged in' : 'Not logged in') . "\n";
if (Auth::guard('portal')->check()) {
    $user = Auth::guard('portal')->user();
    echo "User: " . $user->email . " (ID: " . $user->id . ")\n";
}

// Check routes
echo "\n=== ROUTE STATUS ===\n";
$routes = [
    'business.dashboard' => '/business',
    'business.login' => '/business/login',
    'business.login.post' => '/business/login (POST)'
];

foreach ($routes as $name => $uri) {
    try {
        $route = app('router')->getRoutes()->getByName($name);
        echo "$name: ✓ Exists at $uri\n";
    } catch (Exception $e) {
        echo "$name: ✗ NOT FOUND\n";
    }
}

// Check middleware
echo "\n=== MIDDLEWARE STATUS ===\n";
try {
    $route = app('router')->getRoutes()->getByName('business.dashboard');
    $middleware = $route->gatherMiddleware();
    echo "Dashboard middleware: " . implode(', ', $middleware) . "\n";
} catch (Exception $e) {
    echo "Could not get middleware: " . $e->getMessage() . "\n";
}

// Test request
echo "\n=== TEST REQUEST ===\n";
echo "Current URL: " . $request->fullUrl() . "\n";
echo "Method: " . $request->method() . "\n";
echo "Is /business?: " . ($request->is('business') ? 'Yes' : 'No') . "\n";

// Check for errors
echo "\n=== ERROR LOG (Last 5 entries) ===\n";
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $errorLines = array_filter($lines, function($line) {
        return stripos($line, 'error') !== false || stripos($line, 'exception') !== false;
    });
    $lastErrors = array_slice($errorLines, -5);
    foreach ($lastErrors as $error) {
        echo substr($error, 0, 200) . "...\n";
    }
} else {
    echo "No log file found for today\n";
}

echo "</pre>";

// Add login/logout buttons
echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo '<form method="POST" action="/business/simple-login" style="display:inline;">';
echo '<input type="hidden" name="email" value="demo@askproai.de">';
echo '<input type="hidden" name="password" value="password">';
echo '<button type="submit">Login as Demo User</button>';
echo '</form> ';

echo '<a href="/business"><button>Go to Business Portal</button></a> ';
echo '<a href="/business/login"><button>Go to Login Page</button></a>';

$kernel->terminate($request, $response);