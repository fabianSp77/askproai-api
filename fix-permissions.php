<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Ensure admin user exists and has super-admin role
$adminUser = User::find(6);
if (!$adminUser) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "Found admin user: " . $adminUser->email . "\n";

// Create super-admin role if it doesn't exist
$superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
echo "✅ Super-admin role ready\n";

// Assign super-admin role to admin user
if (!$adminUser->hasRole('super-admin')) {
    $adminUser->assignRole('super-admin');
    echo "✅ Assigned super-admin role to admin user\n";
} else {
    echo "ℹ️ Admin already has super-admin role\n";
}

// Create and assign all necessary permissions
$permissions = [
    'view_any_integration',
    'view_integration',
    'create_integration',
    'update_integration',
    'delete_integration',
    'view_any_service',
    'view_service',
    'create_service',
    'update_service',
    'delete_service',
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
}

// Give super-admin role all permissions
$superAdminRole->syncPermissions(Permission::all());
echo "✅ Super-admin role has all permissions\n";

// Clear permission cache
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
echo "✅ Permission cache cleared\n";

// Verify
$canViewIntegrations = $adminUser->can('view_any_integration');
$canViewIntegration = $adminUser->can('view_integration');
echo "\nVerification:\n";
echo "Can view integrations: " . ($canViewIntegrations ? "YES ✅" : "NO ❌") . "\n";
echo "Can view integration: " . ($canViewIntegration ? "YES ✅" : "NO ❌") . "\n";

echo "\nPermissions fixed!\n";
