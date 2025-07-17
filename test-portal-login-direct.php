<?php

// Laravel bootstrap
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Find user
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();

if (!$user) {
    die("User not found\n");
}

echo "User found: " . $user->email . "\n";
echo "Company: " . $user->company->name . "\n";
echo "Role: " . $user->role . "\n";
echo "Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";

// Check password
if (!Hash::check('demo123', $user->password)) {
    echo "Password incorrect!\n";
    die();
}

echo "Password correct!\n";

// Login the user
Auth::guard('portal')->login($user);

// Check if login successful
if (Auth::guard('portal')->check()) {
    echo "\n✅ LOGIN SUCCESSFUL!\n";
    echo "Logged in user: " . Auth::guard('portal')->user()->email . "\n";
    echo "User ID: " . Auth::guard('portal')->id() . "\n";
    
    // Set session data
    session(['portal_user_id' => $user->id]);
    session(['portal_login' => $user->id]);
    
    echo "\nSession data set:\n";
    echo "portal_user_id: " . session('portal_user_id') . "\n";
    echo "portal_login: " . session('portal_login') . "\n";
} else {
    echo "\n❌ LOGIN FAILED!\n";
}

echo "\nYou can now access the portal at:\n";
echo "https://api.askproai.de/business/dashboard\n";