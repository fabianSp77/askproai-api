<?php
// Bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Force admin authentication
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($user) {
    // Set up the session
    session_start();
    
    // Create Filament admin session
    auth()->guard('admin')->login($user);
    
    // Also try web guard for compatibility
    auth()->guard('web')->login($user);
    
    // Set company context
    app()->instance('current_company_id', $user->company_id);
    
    // Redirect to admin dashboard
    header('Location: /admin');
    exit;
} else {
    echo "Admin user not found!";
}