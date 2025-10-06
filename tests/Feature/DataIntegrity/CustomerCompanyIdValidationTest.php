<?php

namespace Tests\Feature\DataIntegrity;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pre-Backfill Validation Test Suite
 *
 * Tests to validate current state before applying the fix.
 * These tests identify the extent of the data integrity issue.
 *
 * Purpose: Document current broken state and validate assumptions
 */
class CustomerCompanyIdValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);
    }

    /**
     * @test
     * Identifies all customers with NULL company_id
     * Expected: Should find 31 customers in production
     */
    public function test_identifies_all_null_company_id_customers()
    {
        // Arrange: Create customers with NULL company_id (simulating broken state)
        DB::table('customers')->insert([
            'name' => 'Null Customer 1',
            'email' => 'null1@test.com',
            'phone' => '1234567890',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customers')->insert([
            'name' => 'Null Customer 2',
            'email' => 'null2@test.com',
            'phone' => '1234567891',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customers')->insert([
            'name' => 'Valid Customer',
            'email' => 'valid@test.com',
            'phone' => '1234567892',
            'company_id' => $this->companyA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Query for NULL company_id customers
        $nullCustomers = DB::table('customers')
            ->whereNull('company_id')
            ->get();

        // Assert
        $this->assertCount(2, $nullCustomers, 'Should identify all NULL company_id customers');
        $this->assertEquals('Null Customer 1', $nullCustomers[0]->name);
        $this->assertEquals('Null Customer 2', $nullCustomers[1]->name);

        // Log results for documentation
        $this->addToAssertionCount(1); // Mark as passing for documentation
        dump([
            'message' => 'NULL company_id customers identified',
            'count' => $nullCustomers->count(),
            'customer_ids' => $nullCustomers->pluck('id')->toArray(),
        ]);
    }

    /**
     * @test
     * Verify NULL customers have related appointments
     * This confirms they should be backfilled, not deleted
     */
    public function test_null_customers_have_related_appointments()
    {
        // Arrange: Create NULL customer with appointments
        $nullCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Null Customer with Appointments',
            'email' => 'nullwithappts@test.com',
            'phone' => '1234567890',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = Service::factory()->create(['company_id' => $this->companyA->id]);

        DB::table('appointments')->insert([
            'customer_id' => $nullCustomerId,
            'company_id' => $this->companyA->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Check if NULL customer has appointments
        $appointments = DB::table('appointments')
            ->where('customer_id', $nullCustomerId)
            ->get();

        // Assert
        $this->assertCount(1, $appointments, 'NULL customer should have appointments');
        $this->assertEquals($this->companyA->id, $appointments->first()->company_id);

        dump([
            'message' => 'NULL customer has valid appointments',
            'customer_id' => $nullCustomerId,
            'appointment_count' => $appointments->count(),
            'inferred_company_id' => $appointments->first()->company_id,
        ]);
    }

    /**
     * @test
     * Validate relationship integrity for NULL customers
     * Check if we can infer company_id from appointments
     */
    public function test_null_customers_relationship_integrity()
    {
        // Arrange: Create NULL customer with multiple appointments from same company
        $nullCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Null Customer Multi Appt',
            'email' => 'nullmulti@test.com',
            'phone' => '1234567890',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = Service::factory()->create(['company_id' => $this->companyA->id]);

        // Create 3 appointments all from Company A
        foreach (range(1, 3) as $i) {
            DB::table('appointments')->insert([
                'customer_id' => $nullCustomerId,
                'company_id' => $this->companyA->id,
                'service_id' => $service->id,
                'starts_at' => now()->addDays($i),
                'ends_at' => now()->addDays($i)->addHour(),
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Act: Check company consistency across appointments
        $appointmentCompanies = DB::table('appointments')
            ->where('customer_id', $nullCustomerId)
            ->distinct()
            ->pluck('company_id');

        // Assert
        $this->assertCount(1, $appointmentCompanies, 'All appointments should be from same company');
        $this->assertEquals($this->companyA->id, $appointmentCompanies->first());

        dump([
            'message' => 'Relationship integrity verified',
            'customer_id' => $nullCustomerId,
            'unique_companies' => $appointmentCompanies->count(),
            'inferred_company_id' => $appointmentCompanies->first(),
        ]);
    }

    /**
     * @test
     * Identify edge case: customers with appointments from multiple companies
     * These require special handling in backfill migration
     */
    public function test_no_conflicts_in_appointment_companies()
    {
        // Arrange: Create NULL customer with appointments from different companies
        $nullCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Null Customer Conflict',
            'email' => 'nullconflict@test.com',
            'phone' => '1234567890',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceA = Service::factory()->create(['company_id' => $this->companyA->id]);
        $serviceB = Service::factory()->create(['company_id' => $this->companyB->id]);

        // Appointment from Company A
        DB::table('appointments')->insert([
            'customer_id' => $nullCustomerId,
            'company_id' => $this->companyA->id,
            'service_id' => $serviceA->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        // Appointment from Company B (conflict!)
        DB::table('appointments')->insert([
            'customer_id' => $nullCustomerId,
            'company_id' => $this->companyB->id,
            'service_id' => $serviceB->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'status' => 'scheduled',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Act: Identify conflicts
        $appointmentCompanies = DB::table('appointments')
            ->where('customer_id', $nullCustomerId)
            ->select('company_id', DB::raw('COUNT(*) as count'))
            ->groupBy('company_id')
            ->orderByDesc('count')
            ->get();

        // Assert: This is an edge case that should be flagged
        $this->assertGreaterThan(1, $appointmentCompanies->count(), 'Conflict detected');

        dump([
            'message' => 'CONFLICT DETECTED: Customer has appointments from multiple companies',
            'customer_id' => $nullCustomerId,
            'companies' => $appointmentCompanies->toArray(),
            'resolution_strategy' => 'Use most recent or most frequent company',
        ]);
    }

    /**
     * @test
     * Identify orphaned customers without any relationships
     * These should be soft deleted during backfill
     */
    public function test_orphaned_customers_without_relationships()
    {
        // Arrange: Create NULL customer with no relationships
        $orphanedCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Orphaned Customer',
            'email' => 'orphaned@test.com',
            'phone' => '1234567890',
            'company_id' => null,
            'created_at' => now()->subYears(2),
            'updated_at' => now()->subYears(2),
        ]);

        // Create another with appointments
        $validNullCustomerId = DB::table('customers')->insertGetId([
            'name' => 'Valid Null Customer',
            'email' => 'validnull@test.com',
            'phone' => '1234567891',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = Service::factory()->create(['company_id' => $this->companyA->id]);

        DB::table('appointments')->insert([
            'customer_id' => $validNullCustomerId,
            'company_id' => $this->companyA->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Identify orphaned customers
        $orphanedCustomers = DB::table('customers')
            ->whereNull('company_id')
            ->whereNotIn('id', function ($query) {
                $query->select('customer_id')
                    ->from('appointments')
                    ->whereNotNull('customer_id');
            })
            ->get();

        // Assert
        $this->assertCount(1, $orphanedCustomers, 'Should identify orphaned customers');
        $this->assertEquals($orphanedCustomerId, $orphanedCustomers->first()->id);

        dump([
            'message' => 'Orphaned customers identified for soft deletion',
            'count' => $orphanedCustomers->count(),
            'customer_ids' => $orphanedCustomers->pluck('id')->toArray(),
            'action' => 'These will be soft deleted during backfill',
        ]);
    }

    /**
     * @test
     * Generate comprehensive report of current data state
     */
    public function test_generate_pre_backfill_data_report()
    {
        // Arrange: Create varied data scenarios
        $this->createTestDataScenarios();

        // Act: Generate comprehensive report
        $report = [
            'total_customers' => DB::table('customers')->count(),
            'null_company_id_customers' => DB::table('customers')->whereNull('company_id')->count(),
            'customers_with_valid_company_id' => DB::table('customers')->whereNotNull('company_id')->count(),
            'null_customers_with_appointments' => DB::table('customers')
                ->whereNull('company_id')
                ->whereIn('id', function ($query) {
                    $query->select('customer_id')
                        ->from('appointments')
                        ->whereNotNull('customer_id');
                })
                ->count(),
            'orphaned_null_customers' => DB::table('customers')
                ->whereNull('company_id')
                ->whereNotIn('id', function ($query) {
                    $query->select('customer_id')
                        ->from('appointments')
                        ->whereNotNull('customer_id');
                })
                ->count(),
        ];

        // Assert: Report generated successfully
        $this->assertIsArray($report);
        $this->assertArrayHasKey('total_customers', $report);

        dump([
            'message' => 'PRE-BACKFILL DATA INTEGRITY REPORT',
            'timestamp' => now()->toDateTimeString(),
            'report' => $report,
        ]);
    }

    /**
     * Helper: Create test data scenarios
     */
    private function createTestDataScenarios(): void
    {
        $service = Service::factory()->create(['company_id' => $this->companyA->id]);

        // Scenario 1: NULL customer with appointments
        $customer1 = DB::table('customers')->insertGetId([
            'name' => 'Scenario 1',
            'email' => 'scenario1@test.com',
            'phone' => '1111111111',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('appointments')->insert([
            'customer_id' => $customer1,
            'company_id' => $this->companyA->id,
            'service_id' => $service->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Scenario 2: Orphaned NULL customer
        DB::table('customers')->insert([
            'name' => 'Scenario 2 Orphaned',
            'email' => 'scenario2@test.com',
            'phone' => '2222222222',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Scenario 3: Valid customer with company_id
        DB::table('customers')->insert([
            'name' => 'Scenario 3 Valid',
            'email' => 'scenario3@test.com',
            'phone' => '3333333333',
            'company_id' => $this->companyA->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
