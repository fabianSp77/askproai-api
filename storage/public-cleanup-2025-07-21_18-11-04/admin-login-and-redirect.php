<?php
session_start();

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Get action
$action = $_GET['action'] ?? 'login';

if ($action === 'login') {
    // Auto-login as admin
    \Illuminate\Support\Facades\Auth::guard('web')->loginUsingId(6); // admin@askproai.de
    
    // Set session
    session(['auth.password_confirmed_at' => time()]);
    
    // Redirect to admin/calls
    header('Location: /admin/calls');
    exit;
} elseif ($action === 'check') {
    // Check current auth status
    $user = \Illuminate\Support\Facades\Auth::user();
    
    header('Content-Type: application/json');
    echo json_encode([
        'logged_in' => $user !== null,
        'user' => $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'company' => $user->company ? $user->company->name : null
        ] : null
    ]);
    exit;
}

// Terminate the kernel
$kernel->terminate($request, $response);