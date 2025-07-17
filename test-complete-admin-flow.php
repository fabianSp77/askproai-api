<?php
// Test complete admin portal access flow

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Testing complete admin portal access flow...\n\n";

// Step 1: Get a super admin
$admin = \App\Models\User::role('Super Admin')->first();
if (!$admin) {
    die("❌ No Super Admin found\n");
}
echo "✅ Using admin: {$admin->email} (ID: {$admin->id})\n";

// Step 2: Get a company
$company = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
if (!$company) {
    die("❌ No company found\n");
}
echo "✅ Using company: {$company->name} (ID: {$company->id})\n";

// Step 3: Simulate the token generation from BusinessPortalAdmin
$token = bin2hex(random_bytes(32));
$tokenData = [
    'admin_id' => $admin->id,
    'company_id' => $company->id,
    'created_at' => now(),
    'redirect_to' => '/business/dashboard',
];
cache()->put('admin_portal_access_' . $token, $tokenData, now()->addMinutes(15));
echo "✅ Token created and stored in cache\n";

// Step 4: Check if portal user will be created
$email = 'admin+' . $company->id . '@askproai.de';
$existingPortalUser = DB::table('portal_users')
    ->where('email', $email)
    ->where('company_id', $company->id)
    ->first();

if ($existingPortalUser) {
    echo "✅ Portal user already exists: {$email}\n";
} else {
    echo "ℹ️  Portal user will be created: {$email}\n";
}

// Step 5: Display the access URL
echo "\n📋 Access URL:\n";
echo "http://localhost/business/admin-access?token={$token}\n";

echo "\n✅ Test setup complete!\n";
echo "1. Click the URL above\n";
echo "2. You should be redirected to the business portal dashboard\n";
echo "3. You should see 'Admin-Ansicht: {$company->name}' button in the header\n";
echo "4. Click that button to exit admin view and return to admin panel\n";