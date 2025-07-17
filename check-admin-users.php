<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Looking for admin users...\n\n";

// Get all users
$users = \App\Models\User::all();
echo "Total users: " . $users->count() . "\n\n";

// Check each user for Super Admin role
foreach ($users as $user) {
    $roles = $user->getRoleNames();
    echo "User ID: {$user->id}, Email: {$user->email}, Roles: " . $roles->implode(', ') . "\n";
    if ($user->hasRole('Super Admin')) {
        echo "  ✅ This is a Super Admin!\n";
    }
}

echo "\n\nDirect role check:\n";
$superAdmins = \App\Models\User::role('Super Admin')->get();
echo "Super Admins found: " . $superAdmins->count() . "\n";
foreach ($superAdmins as $admin) {
    echo "  - ID: {$admin->id}, Email: {$admin->email}\n";
}