<?php
require_once __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";

echo "=== SIMPLE LOGIN CHECK ===\n";

// Check demo user
$demoUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where("email", "demo@askproai.de")
    ->first();

if ($demoUser) {
    echo "✅ Demo user found: ID " . $demoUser->id . ", Active: " . ($demoUser->is_active ? "Yes" : "No") . "\n";
    
    // Test password
    $passwordValid = \Illuminate\Support\Facades\Hash::check("password", $demoUser->password);
    echo "Password valid: " . ($passwordValid ? "✅ Yes" : "❌ No") . "\n";
} else {
    echo "❌ Demo user NOT found\n";
}

// Check auth config
echo "\nAuth guard portal driver: " . config("auth.guards.portal.driver") . "\n";
echo "Auth provider portal_users driver: " . config("auth.providers.portal_users.driver") . "\n";

// Check session config
echo "\nSession cookie: " . config("session.cookie") . "\n";
echo "Portal session cookie env: " . env("PORTAL_SESSION_COOKIE", "not_set") . "\n";

