<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== Fixing User Login Issue ===\n\n";

$email = 'fabian@askproai.de';
$password = 'Qwe421as1!1';

// Find user
$user = User::where('email', $email)->first();

if (!$user) {
    die("User not found!\n");
}

echo "User found: {$user->email} (ID: {$user->user_id})\n";

// Generate new password hash
$newHash = Hash::make($password);
echo "New hash generated: " . substr($newHash, 0, 20) . "...\n";

// Update directly in database to avoid any model issues
$result = DB::table('users')
    ->where('user_id', $user->user_id)
    ->update(['password' => $newHash]);

echo "Database updated: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Clear all caches
echo "\nClearing caches...\n";
\Illuminate\Support\Facades\Artisan::call('cache:clear');
\Illuminate\Support\Facades\Artisan::call('config:clear');
\Illuminate\Support\Facades\Artisan::call('view:clear');

// Test authentication
echo "\nTesting authentication...\n";
$credentials = ['email' => $email, 'password' => $password];

// Test 1: Direct auth attempt
if (\Auth::attempt($credentials)) {
    echo "✓ Direct auth test: PASSED\n";
    \Auth::logout();
} else {
    echo "✗ Direct auth test: FAILED\n";
}

// Test 2: Manual password verification
$user = User::where('email', $email)->first();
if (Hash::check($password, $user->password)) {
    echo "✓ Password verification: PASSED\n";
} else {
    echo "✗ Password verification: FAILED\n";
}

// Test 3: Check if user can access Filament
if ($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))) {
    echo "✓ Filament access: ALLOWED\n";
} else {
    echo "✗ Filament access: DENIED\n";
}

echo "\nDone! Try logging in now.\n";