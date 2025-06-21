<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "Testing authentication for fabian@askproai.de\n";
echo "===========================================\n\n";

// Find user
$user = User::where('email', 'fabian@askproai.de')->first();

if (!$user) {
    echo "ERROR: User not found!\n";
    exit(1);
}

echo "User found: ID={$user->id}, Name={$user->name}\n";
echo "Password hash: " . substr($user->password, 0, 20) . "...\n\n";

// Test password verification
$testPassword = 'Qwe421as1!1';
echo "Testing password verification...\n";
$verified = Hash::check($testPassword, $user->password);
echo "Password verification: " . ($verified ? 'SUCCESS' : 'FAILED') . "\n\n";

// Test authentication attempt
echo "Testing Auth::attempt()...\n";
$attempt = Auth::attempt(['email' => 'fabian@askproai.de', 'password' => $testPassword]);
echo "Auth attempt: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n\n";

// Check auth guard
echo "Auth guard: " . config('auth.defaults.guard') . "\n";
echo "Auth provider: " . config('auth.guards.web.provider') . "\n";
echo "User model: " . config('auth.providers.users.model') . "\n\n";

// Check session config
echo "Session driver: " . config('session.driver') . "\n";
echo "Session lifetime: " . config('session.lifetime') . " minutes\n";
echo "Session path: " . config('session.path') . "\n";
echo "Session domain: " . config('session.domain') . "\n\n";

// Check if sessions table exists
try {
    $sessionCount = DB::table('sessions')->count();
    echo "Sessions table exists with {$sessionCount} records\n";
} catch (\Exception $e) {
    echo "Sessions table error: " . $e->getMessage() . "\n";
}

// Check Filament login
echo "\nChecking Filament authentication...\n";
if (method_exists($user, 'canAccessPanel')) {
    $panel = \Filament\Facades\Filament::getPanel('admin');
    $canAccess = $user->canAccessPanel($panel);
    echo "Can access Filament panel: " . ($canAccess ? 'YES' : 'NO') . "\n";
}

// Check for any middleware issues
echo "\nChecking middleware configuration...\n";
$middlewareGroups = app('router')->getMiddlewareGroups();
if (isset($middlewareGroups['web'])) {
    echo "Web middleware group:\n";
    foreach ($middlewareGroups['web'] as $middleware) {
        echo "  - " . (is_string($middleware) ? $middleware : get_class($middleware)) . "\n";
    }
}