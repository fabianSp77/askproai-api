<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\PortalUser;
use Tests\Helpers\TestDataBuilder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing environment
     */
    public function run(): void
    {
        // Only run in testing environment
        if (app()->environment() !== 'testing') {
            $this->command->warn('TestSeeder should only run in testing environment!');
            return;
        }

        $this->command->info('Seeding test data...');

        // Create default test company with all relations
        $company = TestDataBuilder::createCompleteCompany([
            'name' => 'AskProAI Test Company',
            'email' => 'test@askproai.de',
            'domain' => 'test.askproai.de',
            'retell_api_key' => env('DEFAULT_RETELL_API_KEY', 'test_key'),
            'retell_agent_id' => env('DEFAULT_RETELL_AGENT_ID', 'test_agent'),
            'calcom_api_key' => env('DEFAULT_CALCOM_API_KEY', 'test_key'),
            'calcom_team_slug' => env('DEFAULT_CALCOM_TEAM_SLUG', 'test-team'),
        ]);

        // Create additional test data for specific scenarios
        $this->createAuthenticationTestData($company);
        $this->createAppointmentTestData($company);
        $this->createCallTestData($company);
        $this->createBillingTestData($company);

        $this->command->info('Test data seeded successfully!');
    }

    /**
     * Create authentication test data
     */
    private function createAuthenticationTestData(Company $company): void
    {
        // Admin user
        PortalUser::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Regular user
        PortalUser::factory()->create([
            'company_id' => $company->id,
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Inactive user
        PortalUser::factory()->create([
            'company_id' => $company->id,
            'email' => 'inactive@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_active' => false,
        ]);
    }

    /**
     * Create appointment test data
     */
    private function createAppointmentTestData(Company $company): void
    {
        $branch = $company->branches->first();
        $staff = $company->staff->first();
        $service = $company->services->first();

        // Future appointments
        for ($i = 1; $i <= 5; $i++) {
            TestDataBuilder::createCompleteAppointment($company, [
                'start_time' => now()->addDays($i)->setTime(10, 0),
                'status' => 'scheduled',
            ]);
        }

        // Past appointments
        for ($i = 1; $i <= 3; $i++) {
            TestDataBuilder::createCompleteAppointment($company, [
                'start_time' => now()->subDays($i)->setTime(14, 0),
                'status' => 'completed',
            ]);
        }

        // Cancelled appointment
        TestDataBuilder::createCompleteAppointment($company, [
            'start_time' => now()->addDays(2)->setTime(16, 0),
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer request',
        ]);
    }

    /**
     * Create call test data
     */
    private function createCallTestData(Company $company): void
    {
        $customers = \App\Models\Customer::factory()->count(5)->create([
            'company_id' => $company->id,
        ]);

        foreach ($customers as $customer) {
            // Successful calls
            \App\Models\Call::factory()->count(rand(1, 3))->create([
                'company_id' => $company->id,
                'phone_number' => $customer->phone,
                'customer_id' => $customer->id,
                'status' => 'ended',
                'appointment_booked' => (bool)rand(0, 1),
            ]);

            // Failed calls
            \App\Models\Call::factory()->create([
                'company_id' => $company->id,
                'phone_number' => $customer->phone,
                'customer_id' => $customer->id,
                'status' => 'failed',
                'error_message' => 'Connection timeout',
            ]);
        }
    }

    /**
     * Create billing test data
     */
    private function createBillingTestData(Company $company): void
    {
        $prepaidBalance = $company->prepaidBalance;

        // Create topup history
        \App\Models\BalanceTopup::factory()->count(5)->create([
            'prepaid_balance_id' => $prepaidBalance->id,
            'status' => 'completed',
            'payment_method' => 'stripe',
        ]);

        // Create call charges
        $calls = $company->calls()->limit(10)->get();
        foreach ($calls as $call) {
            \App\Models\CallCharge::factory()->create([
                'call_id' => $call->id,
                'prepaid_balance_id' => $prepaidBalance->id,
                'status' => 'charged',
            ]);
        }
    }
}