<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

echo "\n🔐 TESTING LOGIN NOW\n";
echo "===================\n\n";

$email = 'fabianspitzer@icloud.com';
$password = 'demo123';

// 1. Get user
$user = PortalUser::where('email', $email)->first();
echo "1️⃣ User Check:\n";
echo "   - Found: " . ($user ? 'YES' : 'NO') . "\n";
echo "   - Active: " . ($user && $user->is_active ? 'YES' : 'NO') . "\n";
echo "   - Company: " . ($user && $user->company ? $user->company->name : 'N/A') . "\n";
echo "   - Company Active: " . ($user && $user->company && $user->company->is_active ? 'YES' : 'NO') . "\n";

// 2. Test password
echo "\n2️⃣ Password Check:\n";
$passwordValid = Hash::check($password, $user->password);
echo "   - Valid: " . ($passwordValid ? 'YES' : 'NO') . "\n";

// 3. Test authentication
echo "\n3️⃣ Authentication Test:\n";

// Start session
Session::start();
echo "   - Session started: " . (Session::isStarted() ? 'YES' : 'NO') . "\n";
echo "   - Session ID: " . Session::getId() . "\n";

// Attempt login
$attempt = Auth::guard('portal')->attempt([
    'email' => $email,
    'password' => $password
]);

echo "   - Login attempt: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";

if ($attempt) {
    $authUser = Auth::guard('portal')->user();
    echo "   - Authenticated as: {$authUser->email}\n";
    echo "   - Auth check: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n";
    
    // Store in session
    Session::put('portal_user_id', $authUser->id);
    Session::put('portal_login', $authUser->id);
    Session::save();
    
    echo "   - Session data stored\n";
} else {
    // Debug why login failed
    echo "\n❌ Login failed! Debugging...\n";
    
    // Check LoginController logic
    if (!$user) {
        echo "   - Reason: User not found\n";
    } elseif (!$user->is_active) {
        echo "   - Reason: User is inactive\n";
    } elseif (!$passwordValid) {
        echo "   - Reason: Invalid password\n";
    } elseif ($user->company && !$user->company->is_active) {
        echo "   - Reason: Company is inactive\n";
    } else {
        echo "   - Reason: Unknown (check LoginController logic)\n";
    }
}

// 4. Test session persistence
echo "\n4️⃣ Session Persistence Test:\n";
$sessionData = Session::all();
echo "   - Session data: " . json_encode($sessionData, JSON_PRETTY_PRINT) . "\n";

echo "\n✅ Test complete!\n";