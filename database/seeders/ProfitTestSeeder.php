<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Call;
use App\Models\Customer;
use App\Models\PhoneNumber;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class ProfitTestSeeder extends Seeder
{
    /**
     * Run the database seeds for profit testing.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Profit Test Data...');

        // Create roles if they don't exist
        $this->createRoles();

        // Create company hierarchy
        $companies = $this->createCompanyHierarchy();

        // Create users with different roles
        $users = $this->createTestUsers($companies);

        // Create realistic call data with profit scenarios
        $this->createCallsWithProfitScenarios($companies);

        $this->command->info('✅ Profit Test Data Seeded Successfully!');
        $this->displayTestCredentials($users);
    }

    private function createRoles(): void
    {
        $roles = ['super-admin', 'reseller_admin', 'reseller_owner', 'reseller_support', 'customer'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function createCompanyHierarchy(): array
    {
        $companies = [];

        // Create 3 Super Admin companies (internal)
        for ($i = 1; $i <= 3; $i++) {
            $companies['internal'][] = Company::factory()->internal()->create([
                'name' => "Internal Company $i",
                'email' => "internal$i@platform.com",
            ]);
        }

        // Create 5 Reseller companies
        for ($i = 1; $i <= 5; $i++) {
            $reseller = Company::factory()->reseller()->create([
                'name' => "Mandant $i GmbH",
                'email' => "mandant$i@reseller.com",
                'reseller_markup' => rand(15, 30), // 15-30% markup
                'customer_markup' => rand(40, 70), // 40-70% markup
                'credit_balance' => rand(1000, 5000),
            ]);
            $companies['resellers'][] = $reseller;

            // Create 3-5 customers for each reseller
            $customerCount = rand(3, 5);
            for ($j = 1; $j <= $customerCount; $j++) {
                $customer = Company::factory()->underReseller($reseller)->create([
                    'name' => "Kunde {$i}_{$j} AG",
                    'email' => "kunde_{$i}_{$j}@customer.com",
                    'credit_balance' => rand(100, 1000),
                ]);
                $companies['customers'][$reseller->id][] = $customer;
            }
        }

        // Create 10 direct customers (no reseller)
        for ($i = 1; $i <= 10; $i++) {
            $companies['direct_customers'][] = Company::factory()->directCustomer()->create([
                'name' => "Direktkunde $i GmbH",
                'email' => "direkt$i@customer.com",
                'credit_balance' => rand(200, 2000),
            ]);
        }

        return $companies;
    }

    private function createTestUsers(array $companies): array
    {
        $users = [];
        $password = bcrypt('Test123!');

        // Create Super Admins
        for ($i = 1; $i <= 3; $i++) {
            $user = User::factory()->create([
                'name' => "Super Admin $i",
                'email' => "superadmin$i@test.com",
                'password' => $password,
                'company_id' => $companies['internal'][0]->id,
            ]);
            $user->assignRole('super-admin');
            $users['super_admins'][] = $user;
        }

        // Create Reseller Admins (one for each reseller)
        foreach ($companies['resellers'] as $index => $reseller) {
            $user = User::factory()->create([
                'name' => "Mandant Admin " . ($index + 1),
                'email' => "mandant" . ($index + 1) . "@test.com",
                'password' => $password,
                'company_id' => $reseller->id,
            ]);
            $user->assignRole('reseller_admin');
            $users['reseller_admins'][] = $user;
        }

        // Create Customer Users (one for first customer of first reseller)
        if (isset($companies['customers'][$companies['resellers'][0]->id][0])) {
            $customerCompany = $companies['customers'][$companies['resellers'][0]->id][0];
            $user = User::factory()->create([
                'name' => "Customer User",
                'email' => "customer@test.com",
                'password' => $password,
                'company_id' => $customerCompany->id,
            ]);
            $user->assignRole('customer');
            $users['customers'][] = $user;
        }

        return $users;
    }

    private function createCallsWithProfitScenarios(array $companies): void
    {
        $this->command->info('Creating calls with various profit scenarios...');

        // Scenario 1: High-profit calls (last 7 days)
        $this->createHighProfitCalls($companies);

        // Scenario 2: Normal profit calls (last 30 days)
        $this->createNormalProfitCalls($companies);

        // Scenario 3: Low/negative profit calls
        $this->createLowProfitCalls($companies);

        // Scenario 4: Edge cases
        $this->createEdgeCaseCalls($companies);

        // Scenario 5: Today's calls for dashboard testing
        $this->createTodaysCalls($companies);
    }

    private function createHighProfitCalls(array $companies): void
    {
        foreach ($companies['resellers'] as $reseller) {
            if (!isset($companies['customers'][$reseller->id])) continue;

            foreach ($companies['customers'][$reseller->id] as $customer) {
                // Create 5 high-profit calls per customer
                for ($i = 0; $i < 5; $i++) {
                    Call::factory()->highProfit()->create([
                        'company_id' => $customer->id,
                        'created_at' => Carbon::now()->subDays(rand(0, 7)),
                        'call_time' => Carbon::now()->subDays(rand(0, 7)),
                    ]);
                }
            }
        }
    }

    private function createNormalProfitCalls(array $companies): void
    {
        foreach ($companies['resellers'] as $reseller) {
            if (!isset($companies['customers'][$reseller->id])) continue;

            foreach ($companies['customers'][$reseller->id] as $customer) {
                // Create 10 normal profit calls per customer
                for ($i = 0; $i < 10; $i++) {
                    Call::factory()->create([
                        'company_id' => $customer->id,
                        'created_at' => Carbon::now()->subDays(rand(8, 30)),
                        'call_time' => Carbon::now()->subDays(rand(8, 30)),
                    ]);
                }
            }
        }

        // Direct customers
        foreach ($companies['direct_customers'] as $customer) {
            for ($i = 0; $i < 10; $i++) {
                Call::factory()->forDirectCustomer($customer)->create([
                    'created_at' => Carbon::now()->subDays(rand(0, 30)),
                    'call_time' => Carbon::now()->subDays(rand(0, 30)),
                ]);
            }
        }
    }

    private function createLowProfitCalls(array $companies): void
    {
        // Create some loss-making calls
        foreach ($companies['resellers'] as $reseller) {
            if (!isset($companies['customers'][$reseller->id][0])) continue;

            $customer = $companies['customers'][$reseller->id][0];

            // Create 3 loss calls
            for ($i = 0; $i < 3; $i++) {
                Call::factory()->loss()->create([
                    'company_id' => $customer->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 15)),
                ]);
            }

            // Create 3 break-even calls
            for ($i = 0; $i < 3; $i++) {
                Call::factory()->breakEven()->create([
                    'company_id' => $customer->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 15)),
                ]);
            }
        }
    }

    private function createEdgeCaseCalls(array $companies): void
    {
        if (empty($companies['direct_customers'])) return;

        $customer = $companies['direct_customers'][0];

        // Zero duration call
        Call::factory()->create([
            'company_id' => $customer->id,
            'duration_sec' => 0,
            'base_cost' => 5, // Only base fee
            'customer_cost' => 8,
            'total_profit' => 3,
        ]);

        // Very long call (1 hour)
        Call::factory()->create([
            'company_id' => $customer->id,
            'duration_sec' => 3600,
            'base_cost' => 605, // 60 minutes * 10 + 5
            'customer_cost' => 1210,
            'total_profit' => 605,
        ]);

        // Null profit values
        Call::factory()->create([
            'company_id' => $customer->id,
            'platform_profit' => null,
            'reseller_profit' => null,
            'total_profit' => null,
        ]);

        // Extreme profit margin
        Call::factory()->create([
            'company_id' => $customer->id,
            'base_cost' => 10,
            'customer_cost' => 100,
            'total_profit' => 90,
            'profit_margin_total' => 900,
        ]);
    }

    private function createTodaysCalls(array $companies): void
    {
        // Create calls for today to test dashboard widgets
        foreach ($companies['resellers'] as $reseller) {
            if (!isset($companies['customers'][$reseller->id][0])) continue;

            $customer = $companies['customers'][$reseller->id][0];

            // Morning calls
            for ($i = 0; $i < 3; $i++) {
                Call::factory()->create([
                    'company_id' => $customer->id,
                    'created_at' => Carbon::today()->addHours(rand(8, 12)),
                    'call_time' => Carbon::today()->addHours(rand(8, 12)),
                ]);
            }

            // Afternoon calls
            for ($i = 0; $i < 3; $i++) {
                Call::factory()->create([
                    'company_id' => $customer->id,
                    'created_at' => Carbon::today()->addHours(rand(13, 17)),
                    'call_time' => Carbon::today()->addHours(rand(13, 17)),
                ]);
            }
        }
    }

    private function displayTestCredentials(array $users): void
    {
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info('📋 TEST CREDENTIALS');
        $this->command->info(str_repeat('=', 60));

        $this->command->info("\n🔐 Super Admin:");
        $this->command->info("   Email: superadmin1@test.com");
        $this->command->info("   Password: Test123!");

        $this->command->info("\n🔐 Mandant (Reseller) Admin:");
        $this->command->info("   Email: mandant1@test.com");
        $this->command->info("   Password: Test123!");

        $this->command->info("\n🔐 Customer User:");
        $this->command->info("   Email: customer@test.com");
        $this->command->info("   Password: Test123!");

        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info("📊 Test Data Summary:");
        $this->command->info("   - " . Company::where('company_type', 'reseller')->count() . " Mandanten (Resellers)");
        $this->command->info("   - " . Company::where('company_type', 'customer')->whereNotNull('parent_company_id')->count() . " Mandanten-Kunden");
        $this->command->info("   - " . Company::where('company_type', 'customer')->whereNull('parent_company_id')->count() . " Direkt-Kunden");
        $this->command->info("   - " . Call::count() . " Anrufe mit Profit-Daten");
        $this->command->info("   - " . Call::where('created_at', '>=', Carbon::today())->count() . " Anrufe heute");
        $this->command->info("   - " . Call::where('total_profit', '<', 0)->count() . " Verlust-Anrufe");
        $this->command->info("   - " . Call::where('total_profit', '>', 100)->count() . " High-Profit Anrufe");
        $this->command->info(str_repeat('=', 60) . "\n");
    }
}