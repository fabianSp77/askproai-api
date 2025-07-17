<?php

require_once '/var/www/api-gateway/vendor/autoload.php';
$app = require_once '/var/www/api-gateway/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Admin User Role Check ===\n\n";

$adminUser = User::where('email', 'admin@askproai.de')->first();

if (!$adminUser) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✅ Admin user found:\n";
echo "   ID: " . $adminUser->id . "\n";
echo "   Name: " . $adminUser->name . "\n";
echo "   Email: " . $adminUser->email . "\n";
echo "   Role field: " . ($adminUser->role ?? 'NULL') . "\n\n";

echo "📋 Checking Spatie roles:\n";
$roles = $adminUser->roles;
if ($roles->count() > 0) {
    echo "   Roles: " . $roles->pluck('name')->join(', ') . "\n";
} else {
    echo "   ❌ No Spatie roles assigned\n";
}

echo "\n📋 Role checks:\n";
echo "   hasRole('admin'): " . ($adminUser->hasRole('admin') ? 'YES' : 'NO') . "\n";
echo "   hasRole('super_admin'): " . ($adminUser->hasRole('super_admin') ? 'YES' : 'NO') . "\n";
echo "   hasRole('super-admin'): " . ($adminUser->hasRole('super-admin') ? 'YES' : 'NO') . "\n";

echo "\n📋 Permission checks:\n";
echo "   can('view_any_company'): " . ($adminUser->can('view_any_company') ? 'YES' : 'NO') . "\n";
echo "   can('view_company'): " . ($adminUser->can('view_company') ? 'YES' : 'NO') . "\n";

echo "\n🔧 Assigning admin role...\n";
$adminUser->assignRole('admin');
echo "   ✅ Admin role assigned\n";

echo "\n📋 Re-checking after assignment:\n";
echo "   hasRole('admin'): " . ($adminUser->hasRole('admin') ? 'YES' : 'NO') . "\n";
echo "   can('view_any_company'): " . ($adminUser->can('view_any_company') ? 'YES' : 'NO') . "\n";

echo "\n✅ Done!\n";