<?php
// Start Laravel application
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test request
$request = Illuminate\Http\Request::create(
    '/api/admin/auth/login',
    'POST',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json']
);

// Handle the request
$response = $kernel->handle($request);

// Get middleware info
$router = app('router');
$route = $router->getRoutes()->match($request);

echo "<h1>CSRF Debug Info</h1>";
echo "<h2>Request Info:</h2>";
echo "<pre>";
echo "URL: " . $request->url() . "\n";
echo "Method: " . $request->method() . "\n";
echo "Is API: " . ($request->is('api/*') ? 'YES' : 'NO') . "\n";
echo "</pre>";

echo "<h2>Route Middleware:</h2>";
echo "<pre>";
if ($route) {
    echo "Route: " . $route->uri() . "\n";
    echo "Action: " . $route->getActionName() . "\n";
    echo "Middleware: " . implode(', ', $route->gatherMiddleware()) . "\n";
} else {
    echo "No route found!\n";
}
echo "</pre>";

echo "<h2>Session & CSRF:</h2>";
echo "<pre>";
echo "Session Driver: " . config('session.driver') . "\n";
echo "CSRF Except: \n";
$csrfMiddleware = new \App\Http\Middleware\VerifyCsrfToken(app(), app('encrypter'));
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
print_r($property->getValue($csrfMiddleware));
echo "</pre>";

echo "<h2>Test Login:</h2>";
echo "<pre>";
// Test actual login
$loginRequest = Illuminate\Http\Request::create(
    '/api/admin/auth/login',
    'POST',
    ['email' => 'admin@askproai.de', 'password' => 'admin123'],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json'
    ],
    json_encode(['email' => 'admin@askproai.de', 'password' => 'admin123'])
);

$loginRequest->setJson(new \Symfony\Component\HttpFoundation\ParameterBag(
    ['email' => 'admin@askproai.de', 'password' => 'admin123']
));

try {
    $loginResponse = $kernel->handle($loginRequest);
    echo "Response Status: " . $loginResponse->getStatusCode() . "\n";
    echo "Response: " . $loginResponse->getContent() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
echo "</pre>";

$kernel->terminate($request, $response);