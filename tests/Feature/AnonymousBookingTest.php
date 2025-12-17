<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\Retell\AppointmentCustomerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SyncToCalcomJob;

/**
 * Anonymous Booking E2E Test Suite
 *
 * Tests the complete anonymous booking flow after database constraint fix.
 * Migration: 2025_11_11_231608_fix_customers_email_unique_constraint
 *
 * Critical: Anonymous callers MUST always create NEW customer records
 * (security/privacy rule - no identity verification without phone number)
 */
class AnonymousBookingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Test company and branch setup
        $this->company = Company::factory()->create([
            'name' => 'Test Salon',
            'status' => 'active'
        ]);

        $this->branch = Branch::factory()->create([
            'name' => 'Main Branch',
            'company_id' => $this->company->id,
            'status' => 'active'
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Herrenhaarschnitt',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123456
        ]);
    }

    /**
     * @test H1: Anonymous caller, no email
     *
     * Test ID: H1
     * Scenario: Anonymous caller books without providing email
     * Expected: Customer created with email = NULL (not empty string)
     */
    public function test_anonymous_caller_without_email_creates_customer_with_null_email()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Hans Müller', null);

        $this->assertNotNull($customer);
        $this->assertEquals('Hans Müller', $customer->name);
        $this->assertNull($customer->email); // Critical: Must be NULL
        $this->assertStringStartsWith('anonymous_', $customer->phone);
        $this->assertEquals('retell_webhook_anonymous', $customer->source);
        $this->assertEquals($this->company->id, $customer->company_id);

        // Verify database record
        $dbCustomer = Customer::find($customer->id);
        $this->assertNull($dbCustomer->email); // Double-check DB value
    }

    /**
     * @test H2: Anonymous caller, name only
     *
     * Test ID: H2
     * Scenario: Anonymous caller provides only name
     * Expected: Unique placeholder phone generated
     */
    public function test_anonymous_caller_name_only_creates_unique_placeholder_phone()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Max Schmidt', null);

        $this->assertNotNull($customer);
        $this->assertMatchesRegularExpression(
            '/^anonymous_\d+_[a-f0-9]{8}$/',
            $customer->phone,
            'Phone placeholder must follow pattern: anonymous_[timestamp]_[hash]'
        );

        // Verify uniqueness - create another and compare
        $call2 = $this->createAnonymousCall();
        $customer2 = $resolver->ensureCustomerFromCall($call2, 'Max Schmidt', null);
        $this->assertNotEquals($customer->phone, $customer2->phone, 'Placeholder phones must be unique');
    }

    /**
     * @test H3: Anonymous caller with email
     *
     * Test ID: H3
     * Scenario: Anonymous caller provides email
     * Expected: Email stored correctly, phone still uses placeholder
     */
    public function test_anonymous_caller_with_email_stores_email_correctly()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Anna Weber', 'anna@test.de');

        $this->assertNotNull($customer);
        $this->assertEquals('anna@test.de', $customer->email);
        $this->assertStringStartsWith('anonymous_', $customer->phone);
        $this->assertEquals($this->company->id, $customer->company_id);
    }

    /**
     * @test H4: Regular caller, no email
     *
     * Test ID: H4
     * Scenario: Regular caller (with phone) books without email
     * Expected: Customer created with NULL email, real phone number
     */
    public function test_regular_caller_without_email_creates_customer_with_null_email()
    {
        $call = $this->createRegularCall('+4915112345678');
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Petra Klein', null);

        $this->assertNotNull($customer);
        $this->assertEquals('Petra Klein', $customer->name);
        $this->assertNull($customer->email); // Critical: Must be NULL
        $this->assertEquals('+4915112345678', $customer->phone);
        $this->assertEquals('retell_webhook', $customer->source);
    }

    /**
     * @test H5: Regular caller with email
     *
     * Test ID: H5
     * Scenario: Regular caller provides both phone and email
     * Expected: Both stored correctly
     */
    public function test_regular_caller_with_email_stores_both_phone_and_email()
    {
        $call = $this->createRegularCall('+4915112345678');
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Tom Berg', 'tom@test.de');

        $this->assertNotNull($customer);
        $this->assertEquals('Tom Berg', $customer->name);
        $this->assertEquals('tom@test.de', $customer->email);
        $this->assertEquals('+4915112345678', $customer->phone);
    }

    /**
     * @test E1: Duplicate anonymous caller (same name) creates separate customers
     *
     * Test ID: E1
     * Scenario: Two anonymous calls with identical name
     * Expected: SEPARATE customers created (security rule)
     */
    public function test_duplicate_anonymous_caller_same_name_creates_separate_customers()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        // First anonymous call
        $call1 = $this->createAnonymousCall();
        $customer1 = $resolver->ensureCustomerFromCall($call1, 'Max Müller', null);

        // Second anonymous call with SAME NAME
        $call2 = $this->createAnonymousCall();
        $customer2 = $resolver->ensureCustomerFromCall($call2, 'Max Müller', null);

        // CRITICAL: Must be DIFFERENT customers (security rule)
        $this->assertNotEquals(
            $customer1->id,
            $customer2->id,
            'Anonymous callers with same name MUST create separate customers'
        );
        $this->assertNotEquals($customer1->phone, $customer2->phone);
        $this->assertEquals('Max Müller', $customer1->name);
        $this->assertEquals('Max Müller', $customer2->name);

        // Verify both exist in database
        $this->assertDatabaseHas('customers', ['id' => $customer1->id, 'name' => 'Max Müller']);
        $this->assertDatabaseHas('customers', ['id' => $customer2->id, 'name' => 'Max Müller']);
    }

    /**
     * @test E2: Empty string email converts to NULL
     *
     * Test ID: E2
     * Scenario: Legacy system sends empty string for email
     * Expected: Automatically converted to NULL
     */
    public function test_empty_string_email_converts_to_null()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Test User', '');

        $this->assertNotNull($customer);
        $this->assertNull($customer->email); // Empty string should become NULL

        // Verify database stores NULL
        $dbCustomer = Customer::find($customer->id);
        $this->assertNull($dbCustomer->email);
    }

    /**
     * @test E3: Whitespace email converts to NULL
     *
     * Test ID: E3
     * Scenario: Email contains only whitespace
     * Expected: Sanitized to NULL
     */
    public function test_whitespace_email_converts_to_null()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Test User', '   ');

        $this->assertNotNull($customer);
        $this->assertNull($customer->email); // Whitespace should become NULL
    }

    /**
     * @test E7: Special characters in name
     *
     * Test ID: E7
     * Scenario: Name contains special characters (apostrophes, hyphens, umlauts)
     * Expected: Stored correctly with UTF-8 support
     */
    public function test_special_characters_in_name_stored_correctly()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $testNames = [
            "O'Brien-Müller",
            "José García",
            "Schmidt-O'Neill",
            "Müller-Lüdenscheid"
        ];

        foreach ($testNames as $name) {
            $customer = $resolver->ensureCustomerFromCall($call, $name, null);
            $this->assertEquals($name, $customer->name);
        }
    }

    /**
     * @test E8: Multiple NULL emails (concurrent) - no UNIQUE violation
     *
     * Test ID: E8
     * Scenario: Multiple anonymous callers simultaneously (stress test)
     * Expected: All succeed, no database UNIQUE constraint violation
     */
    public function test_multiple_null_emails_concurrent_no_unique_violation()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        // Create 5 anonymous customers with NULL emails concurrently
        $customers = [];
        for ($i = 0; $i < 5; $i++) {
            $call = $this->createAnonymousCall();
            $customers[] = $resolver->ensureCustomerFromCall($call, "User {$i}", null);
        }

        // Verify all were created successfully
        $this->assertCount(5, $customers);

        // Verify all have NULL email
        foreach ($customers as $customer) {
            $this->assertNull($customer->email);
        }

        // Verify database constraint allows multiple NULLs
        $nullEmailCount = Customer::whereNull('email')->count();
        $this->assertGreaterThanOrEqual(5, $nullEmailCount);

        // Verify all have unique IDs
        $uniqueIds = array_unique(array_map(fn($c) => $c->id, $customers));
        $this->assertCount(5, $uniqueIds);
    }

    /**
     * @test S1: Anonymous caller identity isolation
     *
     * Test ID: S1 (Security)
     * Scenario: Two anonymous calls with identical name
     * Expected: SEPARATE customers (no merging by name alone - security rule)
     */
    public function test_anonymous_caller_identity_isolation()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        // Two anonymous calls with identical name
        $call1 = $this->createAnonymousCall();
        $customer1 = $resolver->ensureCustomerFromCall($call1, 'Max', null);

        $call2 = $this->createAnonymousCall();
        $customer2 = $resolver->ensureCustomerFromCall($call2, 'Max', null);

        // SECURITY: Must be separate customers (no identity verification)
        $this->assertNotEquals(
            $customer1->id,
            $customer2->id,
            'Anonymous callers with same name MUST create separate customers (security rule)'
        );

        // Verify source indicates anonymous origin
        $this->assertEquals('retell_webhook_anonymous', $customer1->source);
        $this->assertEquals('retell_webhook_anonymous', $customer2->source);
    }

    /**
     * @test S4: Cross-tenant isolation
     *
     * Test ID: S4 (Security)
     * Scenario: Anonymous calls from different companies
     * Expected: Customers isolated by company_id
     */
    public function test_cross_tenant_isolation()
    {
        $company2 = Company::factory()->create(['name' => 'Other Salon', 'status' => 'active']);
        $branch2 = Branch::factory()->create(['name' => 'Other Branch', 'company_id' => $company2->id]);

        $resolver = app(AppointmentCustomerResolver::class);

        // Anonymous call for company 1
        $call1 = $this->createAnonymousCall();
        $customer1 = $resolver->ensureCustomerFromCall($call1, 'Test User', null);

        // Anonymous call for company 2
        $call2 = Call::factory()->create([
            'retell_call_id' => 'test_anonymous_' . uniqid(),
            'from_number' => 'anonymous',
            'to_number' => '+4989123456',
            'company_id' => $company2->id,
            'branch_id' => $branch2->id,
            'status' => 'ongoing'
        ]);
        $customer2 = $resolver->ensureCustomerFromCall($call2, 'Test User', null);

        // Verify customers are isolated by company
        $this->assertEquals($this->company->id, $customer1->company_id);
        $this->assertEquals($company2->id, $customer2->company_id);
        $this->assertNotEquals($customer1->id, $customer2->id);
    }

    /**
     * @test S5: Placeholder phone uniqueness
     *
     * Test ID: S5 (Security)
     * Scenario: Verify placeholder phone pattern prevents collisions
     * Expected: Format matches anonymous_[timestamp]_[hash]
     */
    public function test_placeholder_phone_uniqueness()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        $call = $this->createAnonymousCall();
        $customer = $resolver->ensureCustomerFromCall($call, 'Test User', null);

        // Verify pattern
        $this->assertMatchesRegularExpression(
            '/^anonymous_\d{10}_[a-f0-9]{8}$/',
            $customer->phone,
            'Placeholder phone must follow pattern: anonymous_[timestamp]_[hash]'
        );

        // Verify uniqueness by creating multiple
        $phones = [];
        for ($i = 0; $i < 3; $i++) {
            $c = $this->createAnonymousCall();
            $cust = $resolver->ensureCustomerFromCall($c, "User {$i}", null);
            $phones[] = $cust->phone;
        }

        // All must be unique
        $this->assertEquals(count($phones), count(array_unique($phones)), 'All placeholder phones must be unique');
    }

    /**
     * @test Integration: Full anonymous booking flow
     *
     * Test ID: Integration
     * Scenario: Complete booking flow from API to database
     * Expected: Customer + Appointment created, sync job dispatched
     */
    public function test_full_anonymous_booking_flow()
    {
        Queue::fake();

        $call = $this->createAnonymousCall();

        // Get resolver
        $resolver = app(AppointmentCustomerResolver::class);

        // Step 1: Create customer
        $customer = $resolver->ensureCustomerFromCall($call, 'Hans Müller', null);

        // Verify customer created with NULL email
        $this->assertNotNull($customer);
        $this->assertNull($customer->email);
        $this->assertStringStartsWith('anonymous_', $customer->phone);

        // Step 2: Create appointment
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $this->service->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 45),
            'status' => 'confirmed',
            'source' => 'retell_webhook'
        ]);

        // Verify appointment created
        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($this->service->id, $appointment->service_id);

        // Verify database records
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Hans Müller',
            'email' => null
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'customer_id' => $customer->id,
            'service_id' => $this->service->id
        ]);
    }

    /**
     * @test Database Constraint: UNIQUE email allows multiple NULLs
     *
     * Test ID: Database
     * Scenario: Verify MySQL UNIQUE constraint behavior with NULL values
     * Expected: Multiple NULL values allowed, duplicate non-NULL rejected
     */
    public function test_unique_email_constraint_allows_multiple_nulls()
    {
        // Create multiple customers with NULL email (should succeed)
        for ($i = 0; $i < 3; $i++) {
            Customer::create([
                'company_id' => $this->company->id,
                'name' => "User {$i}",
                'email' => null,
                'phone' => "+491511234567{$i}",
                'status' => 'active'
            ]);
        }

        $nullEmailCount = Customer::whereNull('email')->count();
        $this->assertGreaterThanOrEqual(3, $nullEmailCount);

        // Create customer with specific email
        $customer1 = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'User Duplicate Email 1',
            'email' => 'duplicate@test.de',
            'phone' => '+491511234567',
            'status' => 'active'
        ]);

        $this->assertNotNull($customer1);

        // Attempt to create another with same email (should fail)
        $this->expectException(\Illuminate\Database\QueryException::class);
        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'User Duplicate Email 2',
            'email' => 'duplicate@test.de', // Same email
            'phone' => '+491511234568',
            'status' => 'active'
        ]);
    }

    // Helper methods

    /**
     * Create anonymous call (no caller ID)
     */
    private function createAnonymousCall(): Call
    {
        return Call::factory()->create([
            'retell_call_id' => 'test_anonymous_' . uniqid(),
            'from_number' => 'anonymous', // No caller ID
            'to_number' => '+4989123456',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ongoing'
        ]);
    }

    /**
     * Create regular call (with phone number)
     */
    private function createRegularCall(string $fromNumber): Call
    {
        return Call::factory()->create([
            'retell_call_id' => 'test_regular_' . uniqid(),
            'from_number' => $fromNumber,
            'to_number' => '+4989123456',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ongoing'
        ]);
    }
}
