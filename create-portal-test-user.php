<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Clear any existing test users
DB::table('portal_users')->where('email', 'test@portal.de')->delete();

// Get first active company
$company = Company::where('is_active', true)->first();
if (!$company) {
    die("No active company found!\n");
}

// Create portal user
$user = PortalUser::create([
    'company_id' => $company->id,
    'name' => 'Test Portal User',
    'email' => 'test@portal.de',
    'password' => Hash::make('test123'),
    'is_active' => true,
    'role' => 'admin',
    'permissions' => json_encode(['*']), // All permissions
]);

echo "\n✅ Portal Test User Created!\n";
echo "==========================\n";
echo "Email: test@portal.de\n";
echo "Password: test123\n";
echo "Company: {$company->name} (ID: {$company->id})\n";
echo "Portal URL: https://api.askproai.de/business/login\n";
echo "\n";

// Also create an admin user for the admin panel
DB::table('users')->where('email', 'admin@test.de')->delete();

DB::table('users')->insert([
    'name' => 'Test Admin',
    'fname' => 'Test',
    'lname' => 'Admin',
    'email' => 'admin@test.de',
    'password' => Hash::make('admin123'),
    'company_id' => $company->id,
    'tenant_id' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "\n✅ Admin Test User Created!\n";
echo "==========================\n";
echo "Email: admin@test.de\n";
echo "Password: admin123\n";
echo "Admin URL: https://api.askproai.de/admin\n";