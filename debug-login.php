<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "=== Portal Login Debug ===\n\n";

// Test credentials
$testCases = [
    ['email' => 'admin+1@askproai.de', 'password' => 'test123'],
    ['email' => 'demo@askproai.de', 'password' => 'Demo123!'],
];

foreach ($testCases as $test) {
    echo "Testing: {$test['email']}\n";
    echo str_repeat('-', 40) . "\n";
    
    // 1. Find user
    $user = PortalUser::where('email', $test['email'])->first();
    
    if (!$user) {
        echo "❌ User not found\n\n";
        continue;
    }
    
    echo "✅ User found (ID: {$user->id})\n";
    echo "   Company: {$user->company->name}\n";
    echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    
    // 2. Check password hash
    $passwordCorrect = Hash::check($test['password'], $user->password);
    echo "   Password hash check: " . ($passwordCorrect ? '✅ PASS' : '❌ FAIL') . "\n";
    
    // 3. Try Auth::attempt
    $authAttempt = Auth::guard('portal')->attempt([
        'email' => $test['email'],
        'password' => $test['password']
    ]);
    
    echo "   Auth::attempt result: " . ($authAttempt ? '✅ SUCCESS' : '❌ FAILED') . "\n";
    
    if ($authAttempt) {
        Auth::guard('portal')->logout();
    }
    
    // 4. Check for any login throttling
    $throttleKey = 'login.throttle.' . $test['email'];
    $throttled = \Illuminate\Support\Facades\Cache::has($throttleKey);
    echo "   Throttled: " . ($throttled ? '❌ YES' : '✅ NO') . "\n";
    
    echo "\n";
}

// Check portal_users table structure
echo "=== Portal Users Table Structure ===\n";
$columns = DB::select("SHOW COLUMNS FROM portal_users");
$relevantColumns = ['email', 'password', 'is_active', 'company_id'];
foreach ($columns as $column) {
    if (in_array($column->Field, $relevantColumns)) {
        echo "- {$column->Field}: {$column->Type}\n";
    }
}

// Reset password for admin+1 with a very simple password
echo "\n=== Creating New Test User ===\n";

// Delete if exists
PortalUser::where('email', 'testuser@askproai.de')->delete();

// Create fresh user
$newUser = PortalUser::create([
    'email' => 'testuser@askproai.de',
    'password' => Hash::make('password'),
    'name' => 'Test User',
    'company_id' => 1,
    'is_active' => true,
    'role' => 'admin',
    'permissions' => json_encode(['calls.view_all' => true, 'billing.view' => true])
]);

echo "Created new user:\n";
echo "Email: testuser@askproai.de\n";
echo "Password: password\n";

// Test immediate login
$loginTest = Auth::guard('portal')->attempt([
    'email' => 'testuser@askproai.de',
    'password' => 'password'
]);

echo "Immediate login test: " . ($loginTest ? '✅ SUCCESS' : '❌ FAILED') . "\n";

if ($loginTest) {
    Auth::guard('portal')->logout();
}