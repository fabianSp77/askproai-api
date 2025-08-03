#!/usr/bin/env php
<?php

// This script tests the portal login flow after session key fixes

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap the console kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Import facades
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "=== PORTAL LOGIN SESSION KEY FIX TEST ===\n\n";

// Step 1: Verify session key generation consistency
echo "1. CHECKING SESSION KEY GENERATION\n";

$guard = Auth::guard('portal');
$expectedKey = $guard->getName();
$calculatedKey = 'login_portal_' . sha1(Illuminate\Auth\SessionGuard::class);

echo "Guard session key: $expectedKey\n";
echo "Calculated key: $calculatedKey\n";
echo "Keys match: " . ($expectedKey === $calculatedKey ? "YES ✅" : "NO ❌") . "\n\n";

// Step 2: Test user authentication
echo "2. TESTING USER AUTHENTICATION\n";

// Get demo user
$user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();

if (!$user) {
    echo "❌ Demo user not found!\n";
    exit(1);
}

echo "✅ Demo user found: ID={$user->id}, Company={$user->company_id}\n";

// Test password verification
$passwordCorrect = Hash::check('password', $user->password);
echo "Password verification: " . ($passwordCorrect ? "PASS ✅" : "FAIL ❌") . "\n";

// Attempt login
Auth::guard('portal')->login($user);
$isAuthenticated = Auth::guard('portal')->check();
echo "Authentication after login: " . ($isAuthenticated ? "PASS ✅" : "FAIL ❌") . "\n";

if ($isAuthenticated) {
    $authUser = Auth::guard('portal')->user();
    echo "Authenticated user ID: " . $authUser->id . "\n";
}

// Step 3: Check session data
echo "\n3. CHECKING SESSION DATA\n";
$sessionKey = $guard->getName();
$sessionData = session()->all();

echo "Session has portal auth key ($sessionKey): " . (isset($sessionData[$sessionKey]) ? "YES ✅" : "NO ❌") . "\n";
echo "Session has portal_user_id: " . (isset($sessionData['portal_user_id']) ? "YES ✅" : "NO ❌") . "\n";
echo "Session has company_id: " . (isset($sessionData['company_id']) ? "YES ✅" : "NO ❌") . "\n";

// Show all session keys
echo "\nAll session keys:\n";
foreach (array_keys($sessionData) as $key) {
    echo "  - $key\n";
}

// Step 4: Test middleware
echo "\n4. TESTING MIDDLEWARE\n";

// Test SharePortalSession middleware
$shareMiddleware = new \App\Http\Middleware\SharePortalSession();
echo "SharePortalSession middleware exists: YES ✅\n";

// Check if it uses correct session key
$reflection = new ReflectionClass($shareMiddleware);
$method = $reflection->getMethod('handle');
$source = file_get_contents($reflection->getFileName());
if (strpos($source, '$guard->getName()') !== false) {
    echo "SharePortalSession uses guard->getName(): YES ✅\n";
} else {
    echo "SharePortalSession uses guard->getName(): NO ❌\n";
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "- Session key consistency: " . ($expectedKey === $calculatedKey ? "PASS ✅" : "FAIL ❌") . "\n";
echo "- User authentication: " . ($isAuthenticated ? "PASS ✅" : "FAIL ❌") . "\n";
echo "- Session data correct: " . (isset($sessionData[$sessionKey]) ? "PASS ✅" : "FAIL ❌") . "\n";

echo "\nTest complete.\n";