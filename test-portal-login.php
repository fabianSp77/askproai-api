<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

echo "🔐 Testing Portal Login\n";
echo "======================\n\n";

// Find demo user
$user = PortalUser::where('email', 'demo@example.com')->first();
if (!$user) {
    echo "❌ Demo user not found!\n";
    exit(1);
}

echo "✅ Found demo user (ID: {$user->id})\n";

// Test password
$testPassword = 'password'; // Default demo password
$passwordValid = Hash::check($testPassword, $user->password);
echo "✅ Password hash check: " . ($passwordValid ? 'PASSED' : 'FAILED') . "\n";

// Test auth guard
echo "\n📋 Auth Guard Test:\n";
$attempt = Auth::guard('portal')->attempt([
    'email' => 'demo@example.com',
    'password' => $testPassword
]);

if ($attempt) {
    echo "✅ Login successful!\n";
    $authUser = Auth::guard('portal')->user();
    echo "   User ID: {$authUser->id}\n";
    echo "   Email: {$authUser->email}\n";
    echo "   Company ID: {$authUser->company_id}\n";
    
    // Check session
    echo "\n📊 Session Check:\n";
    echo "   Session ID: " . session()->getId() . "\n";
    echo "   Session Driver: " . config('session.driver') . "\n";
    echo "   Session Domain: " . config('session.domain') . "\n";
    
    Auth::guard('portal')->logout();
    echo "\n✅ Logout successful\n";
} else {
    echo "❌ Login failed!\n";
}

echo "\n✅ Test complete!\n";