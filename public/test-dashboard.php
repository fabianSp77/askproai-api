<?php
// Test Dashboard access
require __DIR__.'/../vendor/autoload.php';

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    // Get the authenticated user
    $user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
    
    if (!$user) {
        echo "User not found!\n";
        exit;
    }
    
    echo "User found: " . $user->email . "\n";
    echo "User ID: " . $user->id . "\n";
    echo "Company ID: " . ($user->company_id ?? 'NULL') . "\n";
    echo "Tenant ID: " . ($user->tenant_id ?? 'NULL') . "\n";
    
    // Check roles
    echo "\nChecking roles:\n";
    if (method_exists($user, 'hasRole')) {
        echo "hasRole method exists\n";
        echo "Is super_admin: " . ($user->hasRole('super_admin') ? 'YES' : 'NO') . "\n";
        echo "Is company_admin: " . ($user->hasRole('company_admin') ? 'YES' : 'NO') . "\n";
    } else {
        echo "hasRole method NOT found!\n";
    }
    
    // Check if Spatie Permission is installed
    if (class_exists('\Spatie\Permission\Models\Role')) {
        echo "\nSpatie Permission installed: YES\n";
        
        // Check if user has roles relationship
        if (method_exists($user, 'roles')) {
            $roles = $user->roles()->get();
            echo "User roles count: " . $roles->count() . "\n";
            foreach ($roles as $role) {
                echo "  - " . $role->name . "\n";
            }
        }
    } else {
        echo "\nSpatie Permission installed: NO\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}