<?php
// Test admin portal access flow

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$kernel->terminate($request, $response);

// Step 1: Create a test token in cache
$token = bin2hex(random_bytes(32));
$tokenData = [
    'admin_id' => 1, // Admin user ID
    'company_id' => 1, // Test company ID
    'created_at' => now(),
];

// Store in cache
cache()->put('admin_portal_access_' . $token, $tokenData, now()->addMinutes(15));

echo "Test token created: $token\n";
echo "Token data: " . json_encode($tokenData, JSON_PRETTY_PRINT) . "\n\n";

// Step 2: Test if token is retrievable
$retrievedData = cache()->get('admin_portal_access_' . $token);
if ($retrievedData) {
    echo "✅ Token successfully stored and retrievable from cache\n";
} else {
    echo "❌ Token not found in cache\n";
}

// Step 3: Display access URL
echo "\nAccess URL: http://localhost/business/admin-access?token=$token\n";

// Step 4: Check if portal users exist
$portalUsers = DB::table('portal_users')->count();
echo "\nTotal portal users: $portalUsers\n";

// Step 5: Check admin user
$adminUser = \App\Models\User::find(1);
if ($adminUser) {
    echo "Admin user found: " . $adminUser->email . "\n";
    echo "Has Super Admin role: " . ($adminUser->hasRole('Super Admin') ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Admin user with ID 1 not found\n";
}

// Step 6: Check company
$company = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(1);
if ($company) {
    echo "Company found: " . $company->name . "\n";
} else {
    echo "❌ Company with ID 1 not found\n";
}

echo "\n✅ Test setup complete. You can now access the URL above to test admin portal access.\n";