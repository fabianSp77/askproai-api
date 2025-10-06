<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::createDefaultPermissions();

        // Create roles
        Role::createDefaultRoles();

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        // Assign super-admin role to first user if exists
        $firstUser = User::first();
        if ($firstUser && !$firstUser->hasRole(Role::SUPER_ADMIN)) {
            $firstUser->assignRole(Role::SUPER_ADMIN);
        }
    }

    /**
     * Assign permissions to roles based on hierarchy
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin gets all permissions
        $superAdmin = Role::findByName(Role::SUPER_ADMIN);
        $superAdmin->syncPermissions(Permission::all());

        // Admin gets most permissions except system-critical ones
        $admin = Role::findByName(Role::ADMIN);
        $adminPermissions = Permission::where(function ($query) {
            $query->where('name', 'not like', 'system.%')
                ->orWhere('name', 'like', 'system.access_admin')
                ->orWhere('name', 'like', 'system.view_%');
        })->get();
        $admin->syncPermissions($adminPermissions);

        // Manager gets business-related permissions
        $manager = Role::findByName(Role::MANAGER);
        $managerPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', 'company.%')
                ->orWhere('name', 'like', 'branch.%')
                ->orWhere('name', 'like', 'staff.%')
                ->orWhere('name', 'like', 'service.%')
                ->orWhere('name', 'like', 'customer.%')
                ->orWhere('name', 'like', 'appointment.%')
                ->orWhere('name', 'like', 'call.%');
        })->where('name', 'not like', '%.delete')
        ->where('name', 'not like', '%.bulk_delete')
        ->get();
        $manager->syncPermissions($managerPermissions);

        // Operator gets operational permissions
        $operator = Role::findByName(Role::OPERATOR);
        $operatorPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', 'customer.%')
                ->orWhere('name', 'like', 'appointment.%')
                ->orWhere('name', 'like', 'call.%')
                ->orWhere('name', 'like', 'staff.view')
                ->orWhere('name', 'like', 'service.view')
                ->orWhere('name', 'like', 'branch.view')
                ->orWhere('name', 'like', 'company.view');
        })->where('name', 'not like', '%.delete')
        ->where('name', 'not like', '%.import')
        ->where('name', 'not like', '%.bulk_delete')
        ->get();
        $operator->syncPermissions($operatorPermissions);

        // Viewer gets only view permissions
        $viewer = Role::findByName(Role::VIEWER);
        $viewerPermissions = Permission::where('name', 'like', '%.view')->get();
        $viewer->syncPermissions($viewerPermissions);
    }
}