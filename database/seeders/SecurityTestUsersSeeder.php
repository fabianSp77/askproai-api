<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SecurityTestUsersSeeder extends Seeder
{
    /**
     * Create test users for security testing
     *
     * Creates 3 test users with known passwords:
     * 1. SuperAdmin: superadmin-test@askproai.de / TestAdmin2024!
     * 2. Reseller: reseller-test@askproai.de / TestReseller2024!
     * 3. Customer: customer-test@askproai.de / TestCustomer2024!
     */
    public function run(): void
    {
        $password = 'Test2024!'; // Common password for all test users

        // 1. Create SuperAdmin Test User
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin-test@askproai.de'],
            [
                'name' => 'SuperAdmin Test',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Assign super_admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncRoles([$superAdminRole]);

        $this->command->info('✅ SuperAdmin Test User created: superadmin-test@askproai.de / ' . $password);

        // 2. Create Reseller Test User
        // Find or create a reseller company
        $resellerCompany = Company::updateOrCreate(
            ['name' => 'Test Reseller GmbH'],
            [
                'email' => 'reseller-test@askproai.de',
                'phone' => '+49 123 456789',
                'is_reseller' => true,
                'parent_company_id' => null, // Reseller has no parent
            ]
        );

        $reseller = User::updateOrCreate(
            ['email' => 'reseller-test@askproai.de'],
            [
                'name' => 'Reseller Test',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'company_id' => $resellerCompany->id,
            ]
        );

        // Assign reseller_owner role
        $resellerRole = Role::firstOrCreate(['name' => 'reseller_owner', 'guard_name' => 'web']);
        $reseller->syncRoles([$resellerRole]);

        $this->command->info('✅ Reseller Test User created: reseller-test@askproai.de / ' . $password);
        $this->command->info('   Company: ' . $resellerCompany->name . ' (ID: ' . $resellerCompany->id . ')');

        // 3. Create Customer Test User
        // Create a customer company (child of reseller)
        $customerCompany = Company::updateOrCreate(
            ['name' => 'Test Kunde GmbH'],
            [
                'email' => 'customer-test@askproai.de',
                'phone' => '+49 987 654321',
                'is_reseller' => false,
                'parent_company_id' => $resellerCompany->id, // Customer belongs to reseller
            ]
        );

        $customer = User::updateOrCreate(
            ['email' => 'customer-test@askproai.de'],
            [
                'name' => 'Customer Test',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'company_id' => $customerCompany->id,
            ]
        );

        // Assign company_owner role
        $customerRole = Role::firstOrCreate(['name' => 'company_owner', 'guard_name' => 'web']);
        $customer->syncRoles([$customerRole]);

        $this->command->info('✅ Customer Test User created: customer-test@askproai.de / ' . $password);
        $this->command->info('   Company: ' . $customerCompany->name . ' (ID: ' . $customerCompany->id . ')');
        $this->command->info('   Parent (Reseller): ' . $resellerCompany->name . ' (ID: ' . $resellerCompany->id . ')');

        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🔐 Test User Credentials for Puppeteer Tests');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('1️⃣  SuperAdmin:');
        $this->command->info('   Email:    superadmin-test@askproai.de');
        $this->command->info('   Password: ' . $password);
        $this->command->info('');
        $this->command->info('2️⃣  Reseller:');
        $this->command->info('   Email:    reseller-test@askproai.de');
        $this->command->info('   Password: ' . $password);
        $this->command->info('');
        $this->command->info('3️⃣  Customer:');
        $this->command->info('   Email:    customer-test@askproai.de');
        $this->command->info('   Password: ' . $password);
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('Run tests with:');
        $this->command->info('node tests/Browser/widget-security-test.cjs');
        $this->command->info('');
    }
}
