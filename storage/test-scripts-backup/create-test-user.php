<?php

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Check if user already exists
    $email = 'fabian@askproai.de';
    $user = User::where('email', $email)->first();
    
    if ($user) {
        echo "User already exists: {$email}\n";
        echo "User ID: {$user->id}\n";
        echo "Tenant ID: {$user->tenant_id}\n";
    } else {
        // Get first tenant from companies table or create one
        $company = Company::first();
        if (!$company) {
            echo "No company found, checking if we have tenants...\n";
            
            // Check if tenants table has data
            $tenantExists = DB::table('tenants')->first();
            if (!$tenantExists) {
                echo "Creating test tenant...\n";
                $tenantId = DB::table('tenants')->insertGetId([
                    'name' => 'AskProAI Test Tenant',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Also create a company record if needed
                $company = Company::create([
                    'id' => $tenantId,
                    'name' => 'AskProAI Test Company',
                    'subdomain' => 'test',
                    'is_active' => true,
                ]);
            } else {
                // Use existing tenant
                $company = Company::find($tenantExists->id);
                if (!$company) {
                    // Create company record for existing tenant
                    $company = Company::create([
                        'id' => $tenantExists->id,
                        'name' => $tenantExists->name ?? 'Existing Company',
                        'subdomain' => 'existing',
                        'is_active' => true,
                    ]);
                }
            }
        }
        
        echo "Creating user...\n";
        $user = User::create([
            'name' => 'Fabian',
            'email' => $email,
            'password' => Hash::make('password123'),
            'tenant_id' => $company->id,
            'email_verified_at' => now(),
        ]);
        
        echo "User created successfully!\n";
        echo "Email: {$email}\n";
        echo "Password: password123\n";
        echo "Tenant ID: {$user->tenant_id}\n";
    }
    
    // Check roles
    echo "\nChecking roles...\n";
    $adminRole = Role::where('name', 'Admin')->first();
    if (!$adminRole) {
        echo "Creating Admin role...\n";
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    }
    
    // Assign admin role if not already assigned
    if (!$user->hasRole('Admin')) {
        $user->assignRole('Admin');
        echo "Admin role assigned to user\n";
    } else {
        echo "User already has Admin role\n";
    }
    
    // List all users
    echo "\nAll users in database:\n";
    $allUsers = User::with('tenant')->get();
    foreach ($allUsers as $u) {
        echo "- {$u->email} (ID: {$u->id}, Tenant: " . ($u->tenant ? $u->tenant->name : 'None') . ")\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}