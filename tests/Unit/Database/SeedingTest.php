<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use Database\Seeders\DatabaseSeeder;

class SeedingTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function database_seeder_runs_without_errors()
    {
        // Run seeder
        $exitCode = Artisan::call('db:seed');
        
        $this->assertEquals(0, $exitCode);
        
        // Verify data was created
        $this->assertGreaterThan(0, Company::count());
    }

    /** @test */
    public function demo_data_seeder_creates_complete_company_setup()
    {
        // Run specific seeder
        Artisan::call('db:seed', ['--class' => 'DemoDataSeeder']);
        
        // Verify complete setup
        $company = Company::first();
        $this->assertNotNull($company);
        
        // Check related data
        $this->assertGreaterThan(0, $company->branches()->count());
        $this->assertGreaterThan(0, $company->staff()->count());
        $this->assertGreaterThan(0, $company->services()->count());
        $this->assertGreaterThan(0, $company->customers()->count());
    }

    /** @test */
    public function seeders_are_idempotent()
    {
        // Run seeder twice
        Artisan::call('db:seed');
        $firstCount = Company::count();
        
        Artisan::call('db:seed');
        $secondCount = Company::count();
        
        // Should not duplicate data
        $this->assertEquals($firstCount, $secondCount);
    }

    /** @test */
    public function factory_states_work_correctly()
    {
        // Test different factory states
        $activeCompany = Company::factory()->active()->create();
        $this->assertTrue($activeCompany->is_active);
        
        $inactiveCompany = Company::factory()->inactive()->create();
        $this->assertFalse($inactiveCompany->is_active);
        
        $withPrepaidBalance = Company::factory()->withBalance(500)->create();
        $this->assertEquals(500, $withPrepaidBalance->prepaid_balance);
    }

    /** @test */
    public function factories_respect_relationships()
    {
        // Create company with related data
        $company = Company::factory()
            ->has(Branch::factory()->count(3))
            ->has(Staff::factory()->count(5))
            ->has(Service::factory()->count(4))
            ->create();
        
        $this->assertCount(3, $company->branches);
        $this->assertCount(5, $company->staff);
        $this->assertCount(4, $company->services);
    }

    /** @test */
    public function mass_seeding_performance_is_acceptable()
    {
        $startTime = microtime(true);
        
        // Create large dataset
        Company::factory()
            ->count(10)
            ->has(Customer::factory()->count(100))
            ->create();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete within reasonable time (10 seconds)
        $this->assertLessThan(10, $executionTime);
        
        // Verify data
        $this->assertEquals(10, Company::count());
        $this->assertEquals(1000, Customer::count());
    }

    /** @test */
    public function seeder_creates_valid_test_users()
    {
        Artisan::call('db:seed', ['--class' => 'TestUserSeeder']);
        
        // Check admin user
        $adminUser = \App\Models\PortalUser::where('email', 'admin@askproai.de')->first();
        $this->assertNotNull($adminUser);
        $this->assertEquals('admin', $adminUser->role);
        
        // Check demo user
        $demoUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
        $this->assertNotNull($demoUser);
        $this->assertEquals('user', $demoUser->role);
    }

    /** @test */
    public function seeder_handles_unique_constraints()
    {
        // Create company with unique slug
        $company = Company::factory()->create(['slug' => 'unique-company']);
        
        // Try to create another with same slug via factory
        $newCompany = Company::factory()->make(['slug' => 'unique-company']);
        
        // Factory should generate different slug
        $this->assertNotEquals('unique-company', $newCompany->slug);
    }

    /** @test */
    public function conditional_seeding_based_on_environment()
    {
        // Mock environment
        app()->detectEnvironment(function () {
            return 'testing';
        });
        
        // Run seeder
        Artisan::call('db:seed');
        
        // In testing, should create minimal data
        $this->assertLessThan(5, Company::count());
        
        // Mock production
        app()->detectEnvironment(function () {
            return 'production';
        });
        
        // In production, seeder should not run (or create only essential data)
        // This is typically handled in the seeder class itself
    }

    /** @test */
    public function seeder_creates_realistic_business_hours()
    {
        Artisan::call('db:seed', ['--class' => 'BusinessHoursSeeder']);
        
        $branch = Branch::first();
        $workingHours = $branch->workingHours;
        
        // Should have working hours for weekdays
        $this->assertGreaterThanOrEqual(5, $workingHours->count());
        
        // Verify realistic hours
        $monday = $workingHours->where('day_of_week', 1)->first();
        $this->assertNotNull($monday);
        $this->assertStringContainsString('09:', $monday->start_time);
        $this->assertStringContainsString('17:', $monday->end_time);
    }

    /** @test */
    public function seeder_creates_valid_api_keys()
    {
        Artisan::call('db:seed');
        
        $companies = Company::all();
        
        foreach ($companies as $company) {
            // API keys should be properly formatted
            if ($company->retell_api_key) {
                $this->assertStringStartsWith('key_', $company->retell_api_key);
            }
            
            if ($company->calcom_api_key) {
                $this->assertStringStartsWith('cal_', $company->calcom_api_key);
            }
        }
    }

    /** @test */
    public function seeder_respects_foreign_key_constraints()
    {
        // This should not throw any foreign key violations
        $this->expectNotToPerformAssertions();
        
        Artisan::call('db:seed');
        
        // Create appointments with valid foreign keys
        $appointment = \App\Models\Appointment::first();
        if ($appointment) {
            $this->assertNotNull($appointment->company);
            $this->assertNotNull($appointment->customer);
            $this->assertNotNull($appointment->service);
        }
    }
}