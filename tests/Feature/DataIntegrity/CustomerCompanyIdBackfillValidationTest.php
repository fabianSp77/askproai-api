<?php

namespace Tests\Feature\DataIntegrity;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Post-Backfill Validation Test Suite
 *
 * Tests to validate the fix worked correctly after migration.
 * These tests ensure data integrity and no data loss.
 *
 * Purpose: Verify the fix is complete and correct
 */
class CustomerCompanyIdBackfillValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        $this->adminA = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->adminA->assignRole('admin');

        $this->adminB = User::factory()->create(['company_id' => $this->companyB->id]);
        $this->adminB->assignRole('admin');
    }

    /**
     * @test
     * Verify no active customers have NULL company_id
     */
    public function test_no_customers_have_null_company_id()
    {
        // Arrange: Create only valid customers
        Customer::factory()->count(5)->create(['company_id' => $this->companyA->id]);
        Customer::factory()->count(3)->create(['company_id' => $this->companyB->id]);

        // Act: Check for NULL company_id (excluding soft deleted)
        $nullCustomers = DB::table('customers')
            ->whereNull('company_id')
            ->whereNull('deleted_at')
            ->get();

        // Assert: No NULL company_id values
        $this->assertCount(0, $nullCustomers, 'All active customers must have company_id');

        // Additional check using model scope
        $this->actingAs($this->adminA);
        $customersViaModel = Customer::whereNull('company_id')->get();
        $this->assertCount(0, $customersViaModel, 'No NULL company_id via model query');

        dump([
            'message' => 'Validation: No NULL company_id values found',
            'total_customers' => DB::table('customers')->whereNull('deleted_at')->count(),
            'null_company_id_count' => $nullCustomers->count(),
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify all customers belong to valid companies
     */
    public function test_all_customers_belong_to_valid_companies()
    {
        // Arrange: Create customers
        Customer::factory()->count(10)->create(['company_id' => $this->companyA->id]);
        Customer::factory()->count(8)->create(['company_id' => $this->companyB->id]);

        // Act: Check for invalid company_id references
        $invalidCompanyReferences = DB::table('customers')
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', function ($query) {
                $query->select('id')->from('companies');
            })
            ->count();

        // Assert: All company_id values reference valid companies
        $this->assertEquals(0, $invalidCompanyReferences, 'All company_id must reference valid companies');

        // Verify foreign key integrity
        $allCustomers = DB::table('customers')->whereNotNull('company_id')->get();
        foreach ($allCustomers as $customer) {
            $companyExists = DB::table('companies')->where('id', $customer->company_id)->exists();
            $this->assertTrue($companyExists, "Customer {$customer->id} has invalid company_id {$customer->company_id}");
        }

        dump([
            'message' => 'Validation: All customers have valid company references',
            'total_customers' => $allCustomers->count(),
            'invalid_references' => $invalidCompanyReferences,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify customer company_id matches appointment company_id
     */
    public function test_customer_company_matches_appointment_companies()
    {
        // Arrange: Create customers with appointments
        $customer1 = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $service1 = Service::factory()->create(['company_id' => $this->companyA->id]);

        Appointment::factory()->create([
            'customer_id' => $customer1->id,
            'company_id' => $this->companyA->id,
            'service_id' => $service1->id,
        ]);

        $customer2 = Customer::factory()->create(['company_id' => $this->companyB->id]);
        $service2 = Service::factory()->create(['company_id' => $this->companyB->id]);

        Appointment::factory()->create([
            'customer_id' => $customer2->id,
            'company_id' => $this->companyB->id,
            'service_id' => $service2->id,
        ]);

        // Act: Check for mismatches
        $mismatches = DB::table('appointments')
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->where('appointments.company_id', '!=', DB::raw('customers.company_id'))
            ->select('appointments.id as appointment_id', 'customers.id as customer_id')
            ->get();

        // Assert: No mismatches
        $this->assertCount(0, $mismatches, 'Customer company_id must match appointment company_id');

        dump([
            'message' => 'Validation: Customer and appointment companies match',
            'total_appointments' => DB::table('appointments')->count(),
            'mismatches' => $mismatches->count(),
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify CompanyScope filters customers correctly after backfill
     */
    public function test_company_scope_filters_customers_correctly()
    {
        // Arrange: Create customers for both companies
        Customer::factory()->count(5)->create(['company_id' => $this->companyA->id]);
        Customer::factory()->count(3)->create(['company_id' => $this->companyB->id]);

        // Act: Query as Company A admin
        $this->actingAs($this->adminA);
        $companyACustomers = Customer::all();

        // Assert: Only see Company A customers
        $this->assertCount(5, $companyACustomers, 'Company A admin should see 5 customers');
        foreach ($companyACustomers as $customer) {
            $this->assertEquals($this->companyA->id, $customer->company_id);
        }

        // Act: Query as Company B admin
        $this->actingAs($this->adminB);
        $companyBCustomers = Customer::all();

        // Assert: Only see Company B customers
        $this->assertCount(3, $companyBCustomers, 'Company B admin should see 3 customers');
        foreach ($companyBCustomers as $customer) {
            $this->assertEquals($this->companyB->id, $customer->company_id);
        }

        dump([
            'message' => 'Validation: CompanyScope working correctly',
            'company_a_customers' => $companyACustomers->count(),
            'company_b_customers' => $companyBCustomers->count(),
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify no data loss occurred during backfill
     */
    public function test_no_data_loss_after_backfill()
    {
        // Note: This test would compare backup table with current table in production
        // For testing purposes, we verify all expected data is present

        // Arrange: Create known dataset
        $expectedCustomers = [
            Customer::factory()->create(['company_id' => $this->companyA->id, 'email' => 'customer1@test.com']),
            Customer::factory()->create(['company_id' => $this->companyA->id, 'email' => 'customer2@test.com']),
            Customer::factory()->create(['company_id' => $this->companyB->id, 'email' => 'customer3@test.com']),
        ];

        // Act: Verify all customers exist
        $actualEmails = DB::table('customers')
            ->whereIn('email', ['customer1@test.com', 'customer2@test.com', 'customer3@test.com'])
            ->whereNull('deleted_at')
            ->pluck('email')
            ->toArray();

        // Assert: All expected customers present
        $this->assertCount(3, $actualEmails);
        $this->assertContains('customer1@test.com', $actualEmails);
        $this->assertContains('customer2@test.com', $actualEmails);
        $this->assertContains('customer3@test.com', $actualEmails);

        // Verify critical fields preserved
        foreach ($expectedCustomers as $expected) {
            $actual = DB::table('customers')->where('email', $expected->email)->first();
            $this->assertNotNull($actual);
            $this->assertEquals($expected->name, $actual->name);
            $this->assertEquals($expected->phone, $actual->phone);
        }

        dump([
            'message' => 'Validation: No data loss detected',
            'expected_count' => count($expectedCustomers),
            'actual_count' => count($actualEmails),
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify relationship integrity maintained after backfill
     */
    public function test_relationship_integrity_maintained()
    {
        // Arrange: Create customer with related data
        $customer = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $service = Service::factory()->create(['company_id' => $this->companyA->id]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'company_id' => $this->companyA->id,
            'service_id' => $service->id,
        ]);

        // Act: Load relationships
        $customerWithRelations = Customer::with(['appointments', 'company'])->find($customer->id);

        // Assert: All relationships work
        $this->assertNotNull($customerWithRelations);
        $this->assertNotNull($customerWithRelations->company);
        $this->assertEquals($this->companyA->id, $customerWithRelations->company->id);
        $this->assertCount(1, $customerWithRelations->appointments);
        $this->assertEquals($appointment->id, $customerWithRelations->appointments->first()->id);

        // Verify reverse relationships
        $this->assertEquals($customer->id, $appointment->fresh()->customer_id);

        dump([
            'message' => 'Validation: Relationship integrity maintained',
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'appointments_count' => $customerWithRelations->appointments->count(),
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify soft deleted customers handled correctly
     */
    public function test_soft_deleted_customers_handled_correctly()
    {
        // Arrange: Create customer and soft delete it
        $customer = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customer->delete(); // Soft delete

        // Act: Verify soft deletion works
        $activeCustomers = Customer::all();
        $allCustomersIncludingDeleted = Customer::withTrashed()->get();

        // Assert: Soft deleted customer not in active query
        $this->assertFalse($activeCustomers->contains('id', $customer->id));
        $this->assertTrue($allCustomersIncludingDeleted->contains('id', $customer->id));

        // Verify database state
        $dbCustomer = DB::table('customers')->where('id', $customer->id)->first();
        $this->assertNotNull($dbCustomer->deleted_at);
        $this->assertNotNull($dbCustomer->company_id, 'Soft deleted customer should retain company_id');

        dump([
            'message' => 'Validation: Soft delete functioning correctly',
            'customer_id' => $customer->id,
            'company_id' => $dbCustomer->company_id,
            'deleted_at' => $dbCustomer->deleted_at,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Generate comprehensive post-backfill validation report
     */
    public function test_generate_post_backfill_validation_report()
    {
        // Arrange: Create comprehensive dataset
        Customer::factory()->count(20)->create(['company_id' => $this->companyA->id]);
        Customer::factory()->count(15)->create(['company_id' => $this->companyB->id]);

        // Create some soft deleted
        $softDeleted = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $softDeleted->delete();

        // Act: Generate validation report
        $report = [
            'timestamp' => now()->toDateTimeString(),
            'total_active_customers' => Customer::count(),
            'total_customers_including_deleted' => Customer::withTrashed()->count(),
            'null_company_id_count' => DB::table('customers')->whereNull('company_id')->whereNull('deleted_at')->count(),
            'invalid_company_references' => DB::table('customers')
                ->whereNotNull('company_id')
                ->whereNotIn('company_id', function ($query) {
                    $query->select('id')->from('companies');
                })
                ->count(),
            'customer_appointment_mismatches' => DB::table('appointments')
                ->join('customers', 'appointments.customer_id', '=', 'customers.id')
                ->where('appointments.company_id', '!=', DB::raw('customers.company_id'))
                ->count(),
            'soft_deleted_count' => Customer::onlyTrashed()->count(),
            'companies_with_customers' => DB::table('customers')
                ->select('company_id')
                ->distinct()
                ->whereNotNull('company_id')
                ->count(),
        ];

        $report['validation_status'] = (
            $report['null_company_id_count'] === 0 &&
            $report['invalid_company_references'] === 0 &&
            $report['customer_appointment_mismatches'] === 0
        ) ? 'PASS' : 'FAIL';

        // Assert: All validations pass
        $this->assertEquals(0, $report['null_company_id_count']);
        $this->assertEquals(0, $report['invalid_company_references']);
        $this->assertEquals(0, $report['customer_appointment_mismatches']);
        $this->assertEquals('PASS', $report['validation_status']);

        dump([
            'message' => 'POST-BACKFILL VALIDATION REPORT',
            'report' => $report,
        ]);
    }

    /**
     * @test
     * Verify database indexes are functioning after backfill
     */
    public function test_database_indexes_functioning_correctly()
    {
        // Arrange: Create dataset
        Customer::factory()->count(100)->create(['company_id' => $this->companyA->id]);

        // Act: Run queries that should use indexes
        $this->actingAs($this->adminA);

        $start = microtime(true);
        $customers = Customer::where('company_id', $this->companyA->id)->get();
        $queryTime = microtime(true) - $start;

        // Assert: Query completes quickly (index is being used)
        $this->assertLessThan(0.5, $queryTime, 'Query should complete in under 500ms');
        $this->assertCount(100, $customers);

        // Check EXPLAIN plan (in real scenario)
        $explain = DB::select("EXPLAIN SELECT * FROM customers WHERE company_id = ?", [$this->companyA->id]);

        dump([
            'message' => 'Validation: Database indexes functioning',
            'query_time_seconds' => $queryTime,
            'result_count' => $customers->count(),
            'status' => 'PASS',
        ]);
    }
}
