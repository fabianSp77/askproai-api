<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Customer Portal Phase 1 - Test Data Seeder
 *
 * Creates test users with proper branch_id and staff_id assignments
 * for testing multi-level access control in Customer Portal
 *
 * Usage:
 *   php artisan db:seed --class=CustomerPortalTestDataSeeder
 *
 * What it creates:
 * 1. Test company "Test Portal GmbH"
 * 2. 2 branches (Main Branch, Secondary Branch)
 * 3. 4 staff members
 * 4. 5 test users (owner, admin, 2 managers, staff)
 * 5. Proper role assignments
 * 6. Proper branch_id and staff_id assignments
 *
 * Security:
 * - Password for all test users: "password"
 * - Only use in development/staging environments
 * - DO NOT run in production
 */
class CustomerPortalTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safety check: Only run in non-production environments
        if (app()->environment('production')) {
            $this->command->error('❌ Cannot run test data seeder in production!');
            return;
        }

        $this->command->info('🚀 Starting Customer Portal Test Data Seeder...');

        // Create or get test company
        $company = $this->createTestCompany();
        $this->command->info("✅ Company created: {$company->name} (ID: {$company->id})");

        // Create branches
        [$mainBranch, $secondaryBranch] = $this->createBranches($company);
        $this->command->info("✅ Created 2 branches");

        // Create staff members
        $staffMembers = $this->createStaffMembers($company, $mainBranch, $secondaryBranch);
        $this->command->info("✅ Created {$staffMembers->count()} staff members");

        // Ensure roles exist
        $this->ensureRolesExist();

        // Create test users
        $users = $this->createTestUsers($company, $mainBranch, $secondaryBranch, $staffMembers);
        $this->command->info("✅ Created {$users->count()} test users");

        // Display summary
        $this->displaySummary($company, $users);

        $this->command->info('🎉 Customer Portal Test Data Seeder completed!');
    }

    /**
     * Create or get test company
     */
    private function createTestCompany(): Company
    {
        return Company::firstOrCreate(
            ['name' => 'Test Portal GmbH'],
            [
                'email' => 'info@testportal.de',
                'phone' => '+49 30 12345678',
                'timezone' => 'Europe/Berlin',
                'locale' => 'de',
                'currency' => 'EUR',
            ]
        );
    }

    /**
     * Create test branches
     */
    private function createBranches(Company $company): array
    {
        $mainBranch = Branch::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Hauptfiliale',
            ],
            [
                'is_main' => true,
                'email' => 'hauptfiliale@testportal.de',
                'phone' => '+49 30 11111111',
                'address' => 'Hauptstraße 1',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country' => 'DE',
            ]
        );

        $secondaryBranch = Branch::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Filiale Mitte',
            ],
            [
                'is_main' => false,
                'email' => 'mitte@testportal.de',
                'phone' => '+49 30 22222222',
                'address' => 'Mittelstraße 10',
                'city' => 'Berlin',
                'postal_code' => '10117',
                'country' => 'DE',
            ]
        );

        return [$mainBranch, $secondaryBranch];
    }

    /**
     * Create staff members
     */
    private function createStaffMembers(Company $company, Branch $mainBranch, Branch $secondaryBranch)
    {
        $staff = collect();

        // Staff 1: Main branch
        $staff->push(Staff::firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'anna.schmidt@testportal.de',
            ],
            [
                'name' => 'Anna Schmidt',
                'branch_id' => $mainBranch->id,
                'phone' => '+49 30 11111112',
                'position' => 'Kundenberaterin',
                'is_active' => true,
            ]
        ));

        // Staff 2: Main branch
        $staff->push(Staff::firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'max.mueller@testportal.de',
            ],
            [
                'name' => 'Max Müller',
                'branch_id' => $mainBranch->id,
                'phone' => '+49 30 11111113',
                'position' => 'Kundenberater',
                'is_active' => true,
            ]
        ));

        // Staff 3: Secondary branch
        $staff->push(Staff::firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'lisa.weber@testportal.de',
            ],
            [
                'name' => 'Lisa Weber',
                'branch_id' => $secondaryBranch->id,
                'phone' => '+49 30 22222223',
                'position' => 'Kundenberaterin',
                'is_active' => true,
            ]
        ));

        // Staff 4: Secondary branch
        $staff->push(Staff::firstOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'tom.fischer@testportal.de',
            ],
            [
                'name' => 'Tom Fischer',
                'branch_id' => $secondaryBranch->id,
                'phone' => '+49 30 22222224',
                'position' => 'Kundenberater',
                'is_active' => true,
            ]
        ));

        return $staff;
    }

    /**
     * Ensure customer portal roles exist
     */
    private function ensureRolesExist(): void
    {
        $roles = [
            'company_owner' => 'Company Owner - Full access to company data',
            'company_admin' => 'Company Admin - Full access to company data',
            'company_manager' => 'Company Manager - Access to assigned branch only',
            'company_staff' => 'Company Staff - Access to own data only',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }

    /**
     * Create test users with proper assignments
     */
    private function createTestUsers(Company $company, Branch $mainBranch, Branch $secondaryBranch, $staffMembers)
    {
        $users = collect();

        // User 1: Company Owner (no branch/staff assignment)
        $owner = User::firstOrCreate(
            ['email' => 'owner@testportal.de'],
            [
                'name' => 'Portal Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'branch_id' => null, // Owners don't need branch assignment
                'staff_id' => null,
            ]
        );
        $owner->syncRoles(['company_owner']);
        $users->push($owner);

        // User 2: Company Admin (no branch/staff assignment)
        $admin = User::firstOrCreate(
            ['email' => 'admin@testportal.de'],
            [
                'name' => 'Portal Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'branch_id' => null, // Admins don't need branch assignment
                'staff_id' => null,
            ]
        );
        $admin->syncRoles(['company_admin']);
        $users->push($admin);

        // User 3: Company Manager - Main Branch
        $managerMain = User::firstOrCreate(
            ['email' => 'manager.main@testportal.de'],
            [
                'name' => 'Manager Hauptfiliale',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'branch_id' => $mainBranch->id, // Assigned to main branch
                'staff_id' => null,
            ]
        );
        $managerMain->syncRoles(['company_manager']);
        $users->push($managerMain);

        // User 4: Company Manager - Secondary Branch
        $managerSecondary = User::firstOrCreate(
            ['email' => 'manager.mitte@testportal.de'],
            [
                'name' => 'Manager Filiale Mitte',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'branch_id' => $secondaryBranch->id, // Assigned to secondary branch
                'staff_id' => null,
            ]
        );
        $managerSecondary->syncRoles(['company_manager']);
        $users->push($managerSecondary);

        // User 5: Company Staff - Linked to Anna Schmidt
        $staff = User::firstOrCreate(
            ['email' => 'anna.schmidt@testportal.de'],
            [
                'name' => 'Anna Schmidt',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'branch_id' => $mainBranch->id,
                'staff_id' => $staffMembers->firstWhere('email', 'anna.schmidt@testportal.de')->id,
            ]
        );
        $staff->syncRoles(['company_staff']);
        $users->push($staff);

        return $users;
    }

    /**
     * Display summary of created data
     */
    private function displaySummary(Company $company, $users): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('           CUSTOMER PORTAL TEST DATA SUMMARY');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info("Company: {$company->name}");
        $this->command->info("Company ID: {$company->id}");
        $this->command->info('');
        $this->command->info('Test Users Created:');
        $this->command->info('───────────────────────────────────────────────────────────');

        foreach ($users as $user) {
            $role = $user->roles->first()->name ?? 'No role';
            $branch = $user->branch_id ? "Branch: {$user->branch->name}" : 'No branch';
            $staff = $user->staff_id ? "Staff: {$user->staff->name}" : 'No staff';

            $this->command->info(sprintf(
                "%-30s | Role: %-20s | %s",
                $user->email,
                $role,
                $user->branch_id ? $branch : ($user->staff_id ? $staff : 'All company data')
            ));
        }

        $this->command->info('───────────────────────────────────────────────────────────');
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('  Email: Any of the emails above');
        $this->command->info('  Password: password');
        $this->command->info('');
        $this->command->info('Portal URLs:');
        $this->command->info('  Admin Panel: /admin');
        $this->command->info('  Customer Portal: /portal');
        $this->command->info('');
        $this->command->info('Access Levels:');
        $this->command->info('  company_owner:   See ALL company data');
        $this->command->info('  company_admin:   See ALL company data');
        $this->command->info('  company_manager: See ONLY assigned branch data');
        $this->command->info('  company_staff:   See ONLY own data');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
