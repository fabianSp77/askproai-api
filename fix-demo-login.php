<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

$email = 'demo@askproai.de';
$password = 'Demo123!';

echo "Fixing demo user login...\n";
echo "=====================================\n";

// Get the user
$user = PortalUser::where('email', $email)->first();

if (!$user) {
    die("❌ User not found!\n");
}

echo "✅ User found:\n";
echo "   - ID: {$user->id}\n";
echo "   - Company ID: {$user->company_id}\n";
echo "   - Company: " . ($user->company ? $user->company->name : 'No company') . "\n";

// Reset password
$user->password = Hash::make($password);
$user->save();

echo "\n🔐 Password reset to: $password\n";

// Test the password
if (Hash::check($password, $user->fresh()->password)) {
    echo "✅ Password hash verification successful!\n";
} else {
    echo "❌ Password hash verification failed!\n";
}

// Test authentication
echo "\n🧪 Testing authentication...\n";

// First logout any existing session
Auth::guard('portal')->logout();

$attempt = Auth::guard('portal')->attempt([
    'email' => $email,
    'password' => $password
]);

if ($attempt) {
    echo "✅ Authentication successful!\n";
    Auth::guard('portal')->logout();
} else {
    echo "❌ Authentication failed!\n";
    
    // Check what's wrong
    echo "\nDebugging authentication:\n";
    
    // Check if user exists with email
    $checkUser = PortalUser::where('email', $email)->first();
    echo "- User found by email: " . ($checkUser ? 'Yes' : 'No') . "\n";
    
    if ($checkUser) {
        echo "- User ID: {$checkUser->id}\n";
        echo "- Is active: " . ($checkUser->is_active ? 'Yes' : 'No') . "\n";
        echo "- Password check: " . (Hash::check($password, $checkUser->password) ? 'Pass' : 'Fail') . "\n";
    }
}

echo "\n=====================================\n";
echo "📋 Login Details:\n";
echo "   Email: $email\n";
echo "   Password: $password\n";
echo "   URL: https://api.askproai.de/business/login\n";
echo "=====================================\n";