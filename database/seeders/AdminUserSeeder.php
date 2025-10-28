<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions if they don't exist
        if (Permission::count() === 0) {
            $this->command->info('Creating permissions...');
            Permission::createDefaultPermissions();
            $this->command->info('Created ' . Permission::count() . ' permissions');
        }

        // Get super_admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->error('super_admin role not found!');
            return;
        }

        // Assign all permissions to super_admin
        $superAdminRole->syncPermissions(Permission::all());
        $this->command->info('Assigned ' . Permission::count() . ' permissions to super_admin role');

        // Check if admin user already exists
        $existingUser = User::where('email', 'admin@askproai.de')->first();

        if ($existingUser) {
            $this->command->warn('User admin@askproai.de already exists (ID: ' . $existingUser->id . ')');

            // Ensure the user has the super_admin role
            if (!$existingUser->hasRole('super_admin')) {
                $existingUser->assignRole('super_admin');
                $this->command->info('Assigned super_admin role to existing user');
            } else {
                $this->command->info('User already has super_admin role');
            }

            return;
        }

        // Create new admin user
        $user = User::create([
            'name' => 'Admin AskPro',
            'email' => 'admin@askproai.de',
            'password' => Hash::make('AskProAdmin2025!'),
            'email_verified_at' => now(),
        ]);

        // Assign super_admin role
        $user->assignRole('super_admin');

        $this->command->info('');
        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('  User ID: ' . $user->id);
        $this->command->info('  Email: ' . $user->email);
        $this->command->info('  Name: ' . $user->name);
        $this->command->info('  Role: ' . $user->roles->pluck('name')->implode(', '));
        $this->command->info('  Permissions: ' . $user->getAllPermissions()->count());
        $this->command->info('');
        $this->command->info('🔐 Login Credentials:');
        $this->command->info('  URL: https://api.askproai.de/admin');
        $this->command->info('  Email: admin@askproai.de');
        $this->command->info('  Password: AskProAdmin2025!');
        $this->command->info('');
    }
}
