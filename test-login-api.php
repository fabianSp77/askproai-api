<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Portal\Auth\LoginController;

echo "=== Testing Login Process ===\n\n";

// 1. Find user directly
$user = PortalUser::where('email', 'fabianspitzer@icloud.com')->first();
echo "1. Direct query: " . ($user ? "User found (ID: {$user->id})" : "User NOT found") . "\n";

// 2. Check without any scopes
$userNoScopes = PortalUser::withoutGlobalScopes()->where('email', 'fabianspitzer@icloud.com')->first();
echo "2. Without scopes: " . ($userNoScopes ? "User found (ID: {$userNoScopes->id})" : "User NOT found") . "\n";

// 3. Check password
if ($user) {
    $passCheck = Hash::check('demo123', $user->password);
    echo "3. Password check: " . ($passCheck ? "PASS" : "FAIL") . "\n";
    echo "   - Password hash: " . substr($user->password, 0, 20) . "...\n";
}

// 4. Simulate the exact query from LoginController
echo "\n4. Simulating LoginController query:\n";
$loginUser = \App\Models\PortalUser::where('email', 'fabianspitzer@icloud.com')->first();
echo "   - Query result: " . ($loginUser ? "User found" : "User NOT found") . "\n";

// 5. Check if there's a scope issue
echo "\n5. Checking for scope issues:\n";
$reflection = new ReflectionClass(\App\Models\PortalUser::class);
if ($reflection->hasMethod('getGlobalScopes')) {
    $method = $reflection->getMethod('getGlobalScopes');
    $method->setAccessible(true);
    $scopes = $method->invoke(new \App\Models\PortalUser);
    echo "   - Global scopes: " . json_encode(array_keys($scopes)) . "\n";
}

// 6. Raw database query
echo "\n6. Raw database query:\n";
$rawUser = \DB::select("SELECT id, email, password, is_active, company_id FROM portal_users WHERE email = ?", ['fabianspitzer@icloud.com']);
if (!empty($rawUser)) {
    $raw = $rawUser[0];
    echo "   - User found in DB\n";
    echo "   - ID: {$raw->id}\n";
    echo "   - Email: {$raw->email}\n";
    echo "   - Active: {$raw->is_active}\n";
    echo "   - Company: {$raw->company_id}\n";
    echo "   - Password hash starts with: " . substr($raw->password, 0, 20) . "\n";
    
    // Test password directly
    $directPassCheck = Hash::check('demo123', $raw->password);
    echo "   - Password 'demo123' valid: " . ($directPassCheck ? "YES" : "NO") . "\n";
}

// 7. Test with a fresh password
echo "\n7. Setting fresh password:\n";
if ($user) {
    $newHash = Hash::make('demo123');
    $user->password = $newHash;
    $user->save();
    echo "   - New password set\n";
    
    // Verify immediately
    $freshUser = PortalUser::find($user->id);
    $freshCheck = Hash::check('demo123', $freshUser->password);
    echo "   - Fresh password check: " . ($freshCheck ? "PASS" : "FAIL") . "\n";
}

echo "\n";