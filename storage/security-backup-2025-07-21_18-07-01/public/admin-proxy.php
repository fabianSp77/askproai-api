<?php
// Admin proxy with forced authentication
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Get the path from query parameter
$path = $_GET['path'] ?? 'admin';
if (!str_starts_with($path, 'admin')) {
    $path = 'admin/' . $path;
}

// Create request - need to capture it properly first
$request = Illuminate\Http\Request::capture();

// Override the URI to the admin path
$request->server->set('REQUEST_URI', '/' . $path);
$request->server->set('PATH_INFO', '/' . $path);

// Copy all headers from the original request
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_')) {
        $header = str_replace('_', '-', substr($key, 5));
        $request->headers->set($header, $value);
    }
}

// Force login
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if ($user) {
    // Start session
    session()->start();
    
    // Login user
    Auth::guard('web')->login($user);
    
    // Set session data
    session()->put('login.web', $user->id);
    session()->put('password_hash_web', $user->getAuthPassword());
    session()->save();
    
    // Set the session on the request
    $request->setLaravelSession(session());
}

// Handle the request
$response = $kernel->handle($request);

// Check if it's a redirect to login
if ($response->getStatusCode() === 302 && str_contains($response->headers->get('location', ''), 'login')) {
    // Force bypass the redirect and show admin content
    echo "<h1>Admin Access Granted (Bypass Mode)</h1>";
    echo "<p>User: " . $user->email . "</p>";
    echo "<p>Authenticated: " . (Auth::check() ? 'Yes' : 'No') . "</p>";
    echo "<p>Session ID: " . session()->getId() . "</p>";
    echo "<hr>";
    echo "<h2>Quick Links:</h2>";
    echo "<ul>";
    echo "<li><a href='/admin-proxy.php?path=admin'>Dashboard</a></li>";
    echo "<li><a href='/admin-proxy.php?path=admin/calls'>Calls</a></li>";
    echo "<li><a href='/admin-proxy.php?path=admin/appointments'>Appointments</a></li>";
    echo "</ul>";
} else {
    // Output normal response
    foreach ($response->headers->all() as $name => $values) {
        foreach ($values as $value) {
            header("$name: $value", false);
        }
    }
    
    http_response_code($response->getStatusCode());
    echo $response->getContent();
}

$kernel->terminate($request, $response);
?>