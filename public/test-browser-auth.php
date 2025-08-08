<?php

// This file tests authentication from browser context

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Check if user is authenticated
if (auth()->check()) {
    $user = auth()->user();
    echo "<h1>Authenticated!</h1>";
    echo "<p>User: " . $user->email . "</p>";
    echo "<p>Company ID: " . $user->company_id . "</p>";
    echo "<p>Session ID: " . session()->getId() . "</p>";
    echo "<hr>";
    echo "<h2>Quick Links:</h2>";
    echo "<ul>";
    echo "<li><a href='/admin'>Admin Dashboard</a></li>";
    echo "<li><a href='/admin/calls'>Calls Page</a></li>";
    echo "<li><a href='/admin/companies'>Companies</a></li>";
    echo "</ul>";
    
    // Check Filament auth
    $panel = \Filament\Facades\Filament::getPanel('admin');
    if ($panel->auth()->check()) {
        echo "<p style='color: green;'>✓ Filament authenticated</p>";
    } else {
        echo "<p style='color: red;'>✗ Filament NOT authenticated</p>";
        // Try to authenticate in Filament
        $panel->auth()->login($user);
        if ($panel->auth()->check()) {
            echo "<p style='color: green;'>✓ Filament authenticated after login</p>";
        }
    }
    
} else {
    echo "<h1>Not Authenticated</h1>";
    echo "<p>You are not logged in.</p>";
    echo "<a href='/admin/login'>Go to Login</a>";
}

echo "<hr>";
echo "<h3>Debug Info:</h3>";
echo "<pre>";
echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie: " . config('session.cookie') . "\n";
echo "App URL: " . config('app.url') . "\n";
echo "Request URL: " . $request->fullUrl() . "\n";
echo "Request IP: " . $request->ip() . "\n";
echo "</pre>";