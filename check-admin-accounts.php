<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Admin Accounts Check ===\n\n";

// Check Users table
echo "1. Checking Users table (Admin accounts):\n";
$users = \App\Models\User::all();

if ($users->isEmpty()) {
    echo "   ❌ No users found in Users table\n";
} else {
    echo "   ✓ Found " . $users->count() . " users:\n\n";
    foreach ($users as $user) {
        echo "   Email: " . $user->email . "\n";
        echo "   Name: " . $user->name . "\n";
        echo "   Role: " . ($user->role ?? 'N/A') . "\n";
        echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        echo "   Created: " . $user->created_at . "\n";
        echo "   ---\n";
    }
}

// Create a test admin account
echo "\n2. Creating test admin account:\n";
try {
    $testUser = \App\Models\User::updateOrCreate(
        ['email' => 'admin@askproai.de'],
        [
            'name' => 'Admin User',
            'password' => bcrypt('admin123'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]
    );
    
    echo "   ✓ Admin account created/updated:\n";
    echo "   Email: admin@askproai.de\n";
    echo "   Password: admin123\n";
    echo "   Role: super_admin\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error creating admin account: " . $e->getMessage() . "\n";
}

// Also check PortalUsers table (Business Portal)
echo "\n3. Checking PortalUsers table (Business Portal accounts):\n";
$portalUsers = \App\Models\PortalUser::where('email', 'fabian@askproai.de')->get();

if ($portalUsers->isEmpty()) {
    echo "   ❌ No portal user found with email fabian@askproai.de\n";
} else {
    foreach ($portalUsers as $user) {
        echo "   ✓ Found Portal User:\n";
        echo "   Email: " . $user->email . "\n";
        echo "   Name: " . $user->name . "\n";
        echo "   Company: " . ($user->company->name ?? 'N/A') . "\n";
        echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        echo "   Note: This is a Business Portal account, not an Admin account!\n";
    }
}

// Reset password for fabian@askproai.de if exists in Users table
echo "\n4. Checking if fabian@askproai.de exists as admin:\n";
$fabianUser = \App\Models\User::where('email', 'fabian@askproai.de')->first();

if ($fabianUser) {
    echo "   ✓ Found admin account for fabian@askproai.de\n";
    echo "   Resetting password to: demo123\n";
    $fabianUser->password = bcrypt('demo123');
    $fabianUser->save();
    echo "   ✓ Password updated!\n";
} else {
    echo "   ❌ No admin account found for fabian@askproai.de\n";
    echo "   Creating new admin account...\n";
    
    try {
        $newAdmin = \App\Models\User::create([
            'name' => 'Fabian Admin',
            'email' => 'fabian@askproai.de',
            'password' => bcrypt('demo123'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        echo "   ✓ Admin account created:\n";
        echo "   Email: fabian@askproai.de\n";
        echo "   Password: demo123\n";
        echo "   Role: admin\n";
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "You can now login with either:\n\n";
echo "1. admin@askproai.de / admin123\n";
echo "2. fabian@askproai.de / demo123\n\n";
echo "Login URL: https://api.askproai.de/admin-react-login\n";

$kernel->terminate($request, $response);