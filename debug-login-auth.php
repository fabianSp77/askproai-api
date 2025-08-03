<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

echo "=== Debug Portal User Authentication ===\n\n";

// Find user without global scope
$user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit;
}

echo "✅ User found:\n";
echo "   ID: " . $user->id . "\n";
echo "   Email: " . $user->email . "\n";
echo "   Company ID: " . $user->company_id . "\n";
echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
echo "   Password hash: " . substr($user->password, 0, 20) . "...\n\n";

// Test various passwords
$passwords = ['password', 'password123', 'demo', 'Demo123!', 'askproai'];

echo "Testing passwords:\n";
foreach ($passwords as $pwd) {
    $valid = Hash::check($pwd, $user->password);
    echo "   '$pwd': " . ($valid ? '✅ VALID' : '❌ Invalid') . "\n";
}

// Let's also check what the login view expects
$loginView = file_get_contents(resource_path('views/portal/auth/login.blade.php'));
if (preg_match('/Demo Login:.*?([^\/]+)\s*\/\s*([^<\s]+)/', $loginView, $matches)) {
    echo "\nLogin page shows: " . trim($matches[1]) . " / " . trim($matches[2]) . "\n";
}

// Check if there's any scope being applied
echo "\n=== Checking Scopes ===\n";
$query = PortalUser::where('email', 'demo@askproai.de');
echo "Query SQL: " . $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";

$userWithScope = $query->first();
echo "User found WITH scope: " . ($userWithScope ? 'Yes (ID: ' . $userWithScope->id . ')' : 'No') . "\n";

// Check the actual login controller query
echo "\n=== Testing Login Query ===\n";
$loginQuery = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de');
echo "Login query SQL: " . $loginQuery->toSql() . "\n";
$loginUser = $loginQuery->first();
echo "Login query result: " . ($loginUser ? 'Found (ID: ' . $loginUser->id . ')' : 'Not found') . "\n";