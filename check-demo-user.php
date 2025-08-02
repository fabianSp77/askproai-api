#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

echo "=== Checking Demo User ===\n";

// Find demo user
$user = PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->first();

if (!$user) {
    echo "Demo user not found!\n";
    
    // List all portal users
    echo "\nAll Portal Users:\n";
    $users = PortalUser::withoutGlobalScopes()->get(['id', 'email', 'company_id', 'is_active']);
    foreach ($users as $u) {
        echo "- ID: {$u->id}, Email: {$u->email}, Company: {$u->company_id}, Active: " . ($u->is_active ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "Demo user found:\n";
    echo "- ID: {$user->id}\n";
    echo "- Email: {$user->email}\n";
    echo "- Company ID: {$user->company_id}\n";
    echo "- Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "- Has Password: " . (!empty($user->password) ? 'Yes' : 'No') . "\n";
    
    // Test password
    $testPassword = 'password123';
    $passwordValid = Hash::check($testPassword, $user->password);
    echo "- Password 'password123' valid: " . ($passwordValid ? 'Yes' : 'No') . "\n";
    
    // Check guard configuration
    echo "\nGuard Configuration:\n";
    $guardConfig = config('auth.guards.portal');
    echo "- Driver: " . ($guardConfig['driver'] ?? 'not set') . "\n";
    echo "- Provider: " . ($guardConfig['provider'] ?? 'not set') . "\n";
    
    // Check provider configuration
    $providerConfig = config('auth.providers.portal_users');
    echo "\nProvider Configuration:\n";
    echo "- Driver: " . ($providerConfig['driver'] ?? 'not set') . "\n";
    echo "- Model: " . ($providerConfig['model'] ?? 'not set') . "\n";
}