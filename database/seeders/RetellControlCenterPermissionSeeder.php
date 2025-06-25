<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RetellControlCenterPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds to create permissions for Retell Control Center access
     */
    public function run(): void
    {
        // Create permission
        $permission = Permission::firstOrCreate([
            'name' => 'manage_retell_control_center',
            'guard_name' => 'web',
        ]);
        
        // Assign to super admin role
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        
        $superAdminRole->givePermissionTo($permission);
        
        // Create retell manager role
        $retellManagerRole = Role::firstOrCreate([
            'name' => 'retell_manager',
            'guard_name' => 'web',
        ]);
        
        $retellManagerRole->givePermissionTo($permission);
        
        // Log the creation
        $this->command->info('Created permission: manage_retell_control_center');
        $this->command->info('Assigned to roles: super_admin, retell_manager');
    }
}