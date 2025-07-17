<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n🔍 Checking Admin Structure\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Check users table columns
echo "1. Users table columns:\n";
$columns = DB::select("SHOW COLUMNS FROM users");
foreach ($columns as $column) {
    echo "   - {$column->Field} ({$column->Type})\n";
}

// 2. Check if using Spatie permissions
echo "\n2. Checking for roles:\n";
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();

if ($admin) {
    echo "   - Admin user found: {$admin->email}\n";
    
    // Check if Spatie is available
    if (method_exists($admin, 'hasRole')) {
        echo "   ✓ Spatie permissions detected\n";
        echo "   - Roles: " . $admin->roles->pluck('name')->join(', ') . "\n";
        echo "   - Has Super Admin role: " . ($admin->hasRole('Super Admin') ? 'Yes' : 'No') . "\n";
        
        // Check model_has_roles table
        $roleData = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $admin->id)
            ->get();
            
        echo "   - Role assignments: " . $roleData->count() . "\n";
    } else {
        echo "   ✗ No role system detected\n";
    }
}

// 3. Check User model
echo "\n3. User model properties:\n";
if ($admin) {
    $properties = ['super_admin', 'is_admin', 'admin'];
    foreach ($properties as $prop) {
        if (property_exists($admin, $prop) || isset($admin->$prop)) {
            echo "   - {$prop}: " . ($admin->$prop ? 'true' : 'false') . "\n";
        } else {
            echo "   - {$prop}: not found\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";