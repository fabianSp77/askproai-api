<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SimpleAdminSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting simple admin setup...');

        // 1. Create permissions using direct DB queries
        $this->command->info('Creating permissions...');

        $permissions = [
            'system.access_admin', 'system.view_logs', 'system.manage_settings',
            'company.view', 'company.create', 'company.update', 'company.delete',
            'branch.view', 'branch.create', 'branch.update', 'branch.delete',
            'staff.view', 'staff.create', 'staff.update', 'staff.delete',
            'service.view', 'service.create', 'service.update', 'service.delete',
            'customer.view', 'customer.create', 'customer.update', 'customer.delete',
            'appointment.view', 'appointment.create', 'appointment.update', 'appointment.delete',
            'call.view', 'call.create', 'call.update', 'call.delete',
            'phone_number.view', 'phone_number.create', 'phone_number.update', 'phone_number.delete',
        ];

        $now = now();
        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionCount = DB::table('permissions')->count();
        $this->command->info("Created {$permissionCount} permissions");

        // 2. Get super_admin role ID
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->error('super_admin role not found! Run RolesAndPermissionsSeeder first.');
            return;
        }

        $this->command->info('Found super_admin role (ID: ' . $superAdminRole->id . ')');

        // 3. Assign all permissions to super_admin role
        $this->command->info('Assigning permissions to super_admin...');

        $allPermissions = DB::table('permissions')->get();
        foreach ($allPermissions as $permission) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'role_id' => $superAdminRole->id,
            ]);
        }

        $rolePermissionCount = DB::table('role_has_permissions')
            ->where('role_id', $superAdminRole->id)
            ->count();
        $this->command->info("Assigned {$rolePermissionCount} permissions to super_admin");

        // 4. Create admin user
        $this->command->info('Creating admin user...');

        $existingUser = DB::table('users')->where('email', 'admin@askproai.de')->first();

        if ($existingUser) {
            $this->command->warn('User admin@askproai.de already exists (ID: ' . $existingUser->id . ')');
            $userId = $existingUser->id;
        } else {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Admin AskPro',
                'email' => 'admin@askproai.de',
                'password' => Hash::make('AskProAdmin2025!'),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info('Created user with ID: ' . $userId);
        }

        // 5. Assign super_admin role to user
        $this->command->info('Assigning super_admin role to user...');

        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $superAdminRole->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => $userId,
        ]);

        // 6. Verify setup
        $this->command->info('');
        $this->command->info('=== VERIFICATION ===');
        $this->command->info('Users: ' . DB::table('users')->count());
        $this->command->info('Roles: ' . DB::table('roles')->count());
        $this->command->info('Permissions: ' . DB::table('permissions')->count());
        $this->command->info('Role has permissions: ' . DB::table('role_has_permissions')->count());
        $this->command->info('Model has roles: ' . DB::table('model_has_roles')->count());
        $this->command->info('');
        $this->command->info('✅ Setup complete!');
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('  URL: https://api.askproai.de/admin');
        $this->command->info('  Email: admin@askproai.de');
        $this->command->info('  Password: AskProAdmin2025!');
        $this->command->info('');
    }
}
