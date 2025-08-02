<?php
// Direct portal access for authenticated users
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Check if user is authenticated
if (auth()->guard('portal')->check()) {
    // User is authenticated, show the integrated portal directly
    $user = auth()->guard('portal')->user();
    $view = view('portal.business-integrated')->render();
    echo $view;
} else {
    // Not authenticated, redirect to login
    header('Location: /business/login');
    exit;
}

$kernel->terminate($request, $response);