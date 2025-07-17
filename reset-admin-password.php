<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔐 Reset Admin Password\n";
echo str_repeat("=", 50) . "\n\n";

// Reset password for main admin accounts
$accounts = [
    'fabian@askproai.de' => 'Fabian',
    'admin@askproai.de' => 'Admin',
];

$newPassword = 'demo123'; // You can change this

foreach ($accounts as $email => $name) {
    $user = \App\Models\User::where('email', $email)->first();
    
    if ($user) {
        $user->password = Hash::make($newPassword);
        $user->save();
        
        echo "✓ Password reset for $name ($email)\n";
        echo "  New password: $newPassword\n\n";
    } else {
        echo "✗ User not found: $email\n\n";
    }
}

// Also check Filament login
echo "Testing Filament Admin Login:\n";
$testEmail = 'fabian@askproai.de';
$user = \App\Models\User::where('email', $testEmail)->first();

if ($user && Hash::check($newPassword, $user->password)) {
    echo "✓ Password verification successful for $testEmail\n";
    
    // Test actual login
    if (\Illuminate\Support\Facades\Auth::guard('web')->attempt(['email' => $testEmail, 'password' => $newPassword])) {
        echo "✓ Login test successful!\n";
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
    } else {
        echo "✗ Login test failed\n";
    }
} else {
    echo "✗ Password verification failed\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Passwords reset! Try logging in with:\n";
echo "- Email: fabian@askproai.de OR admin@askproai.de\n";
echo "- Password: $newPassword\n";
echo "- URL: https://api.askproai.de/admin/login\n";