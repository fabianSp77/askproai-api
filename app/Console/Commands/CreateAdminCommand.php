<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {--email= : Email address for the admin user}
                            {--name= : Full name of the admin user}
                            {--password= : Password (min 8 characters)}
                            {--super : Create as super admin with full access}
                            {--company= : Assign to specific company ID}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user with appropriate permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('👤 Create Admin User');
        $this->info('═══════════════════════════════════════════');

        // Gather user information
        $email = $this->option('email') ?? $this->ask('Email address');
        $name = $this->option('name') ?? $this->ask('Full name');
        $password = $this->option('password') ?? $this->secret('Password (min 8 characters)');
        $isSuper = $this->option('super');
        $companyId = $this->option('company');
        $force = $this->option('force');

        // If not forced and not super admin, ask about super admin
        if (!$force && !$isSuper && !$companyId) {
            $isSuper = $this->confirm('Create as super admin (full system access)?', true);
        }

        // Validate input
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|min:2|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  • {$error}");
            }
            return Command::FAILURE;
        }

        // Check if company exists (if specified)
        if ($companyId) {
            if (!\App\Models\Company::find($companyId)) {
                $this->error("Company with ID {$companyId} not found");
                return Command::FAILURE;
            }
        }

        // Show summary and confirm
        if (!$force) {
            $this->info("\n📋 User Details:");
            $this->line("  Email: {$email}");
            $this->line("  Name: {$name}");
            $this->line("  Type: " . ($isSuper ? 'Super Admin' : ($companyId ? "Company Admin (ID: {$companyId})" : 'Admin')));

            if (!$this->confirm('Create this admin user?')) {
                $this->warn('User creation cancelled');
                return Command::SUCCESS;
            }
        }

        try {
            // Create the user
            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'company_id' => $isSuper ? null : $companyId,
                'user_type' => $isSuper ? 'platform_owner' : 'company_admin',
                'is_active' => true,
                'can_switch_tenants' => $isSuper,
            ]);

            // Assign role
            $this->assignRole($user, $isSuper);

            // Set additional attributes for super admin
            if ($isSuper) {
                $user->update([
                    'is_platform_admin' => true,
                    'has_full_access' => true,
                ]);
            }

            $this->info("\n✅ Admin user created successfully!");
            $this->info("  User ID: {$user->id}");
            $this->info("  Email: {$user->email}");
            $this->info("  Name: {$user->name}");

            if ($isSuper) {
                $this->warn("  🔐 This user has FULL SYSTEM ACCESS as a Super Admin");
            }

            $this->info("\n📝 Next steps:");
            $this->line("  1. User can now login at: " . config('app.url') . '/admin');
            $this->line("  2. To reset password later: php artisan user:reset-password --email={$email}");
            $this->line("  3. To verify user access: php artisan user:info --email={$email}");

            // Log the creation
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'created_via' => 'artisan',
                    'is_super' => $isSuper,
                    'company_id' => $companyId,
                ])
                ->log('Admin user created via command line');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Assign appropriate role to the user
     */
    protected function assignRole(User $user, bool $isSuper)
    {
        // Check if Spatie Permission is properly configured
        if (!class_exists(Role::class)) {
            $this->warn('Spatie Permission not configured, skipping role assignment');
            return;
        }

        try {
            // Define role names to try
            $superRoles = ['super_admin', 'super-admin', 'Super Admin', 'platform_owner'];
            $adminRoles = ['admin', 'Admin', 'company_admin', 'administrator'];

            if ($isSuper) {
                // Try to find and assign super admin role
                foreach ($superRoles as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $user->assignRole($role);
                        $this->info("  Role assigned: {$role->name}");
                        return;
                    }
                }

                // If no super admin role exists, create it
                $role = Role::create([
                    'name' => 'super_admin',
                    'guard_name' => 'web',
                    'description' => 'Super Administrator with full system access',
                ]);

                // Assign all permissions to super admin role
                if (class_exists(\Spatie\Permission\Models\Permission::class)) {
                    $role->syncPermissions(\Spatie\Permission\Models\Permission::all());
                }

                $user->assignRole($role);
                $this->info("  Role created and assigned: super_admin");

            } else {
                // Try to find and assign admin role
                foreach ($adminRoles as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $user->assignRole($role);
                        $this->info("  Role assigned: {$role->name}");
                        return;
                    }
                }

                // If no admin role exists, create it
                $role = Role::create([
                    'name' => 'admin',
                    'guard_name' => 'web',
                    'description' => 'Administrator with company-level access',
                ]);

                $user->assignRole($role);
                $this->info("  Role created and assigned: admin");
            }

        } catch (\Exception $e) {
            $this->warn("  Could not assign role: " . $e->getMessage());
        }
    }
}