<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

echo "🔍 MULTI-AUTH ANALYSIS\n";
echo "=====================\n\n";

// Check all guards
echo "1️⃣ Current Auth Status:\n";
$guards = ['web', 'portal', 'customer'];
foreach ($guards as $guard) {
    $user = Auth::guard($guard)->user();
    echo "- Guard '$guard': " . ($user ? "User ID {$user->id} ({$user->email})" : "Not logged in") . "\n";
}

// Check session keys
echo "\n2️⃣ Session Keys:\n";
$sessionData = Session::all();
foreach ($sessionData as $key => $value) {
    if (strpos($key, 'login_') === 0 || strpos($key, 'password_hash_') === 0) {
        echo "- $key: " . (is_string($value) ? substr($value, 0, 50) . '...' : json_encode($value)) . "\n";
    }
}

// Check auth configuration
echo "\n3️⃣ Auth Configuration:\n";
$authConfig = config('auth');
echo "- Session guards:\n";
foreach ($authConfig['guards'] as $name => $config) {
    if (isset($config['driver']) && $config['driver'] === 'session') {
        echo "  - $name: provider=" . ($config['provider'] ?? 'none') . "\n";
    }
}

echo "\n4️⃣ The Problem:\n";
echo "Laravel uses different session keys for each guard:\n";
echo "- Web guard: login_web_[hash]\n";
echo "- Portal guard: login_portal_[hash]\n";
echo "- They SHOULD work independently!\n";

echo "\n5️⃣ Solution:\n";
echo "The guards should work independently. Let me check the middleware...\n";