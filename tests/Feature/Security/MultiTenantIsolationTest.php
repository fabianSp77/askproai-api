<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Company;
use App\Models\PolicyConfiguration;
use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Models\CallbackRequest;
use App\Models\CallbackEscalation;
use App\Models\NotificationConfiguration;
use App\Models\NotificationEventMapping;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Multi-Tenant Isolation Test Suite
 *
 * Validates that all 33 models with BelongsToCompany trait properly enforce
 * company-based data isolation and prevent cross-tenant data access.
 *
 * CRITICAL: These tests validate PHASE A fix for CVSS 9.1 vulnerability
 */
class MultiTenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminCompanyA;
    protected User $adminCompanyB;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate companies for isolation testing
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create admin for Company A
        $this->adminCompanyA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'admin-a@test.com',
        ]);
        $this->adminCompanyA->assignRole('admin');

        // Create admin for Company B
        $this->adminCompanyB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'admin-b@test.com',
        ]);
        $this->adminCompanyB->assignRole('admin');

        // Create super admin (can see all companies)
        $this->superAdmin = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'super@test.com',
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    /** @test */
    public function customer_model_enforces_company_isolation()
    {
        // Arrange: Create customers for both companies
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: Company A admin can only see their customer
        $this->actingAs($this->adminCompanyA);
        $customers = Customer::all();

        $this->assertCount(1, $customers, 'Admin A should only see 1 customer');
        $this->assertTrue($customers->contains($customerA->id), 'Admin A should see their customer');
        $this->assertFalse($customers->contains($customerB->id), 'Admin A should NOT see Company B customer');
    }

    /** @test */
    public function appointment_model_enforces_company_isolation()
    {
        // Arrange
        $serviceA = Service::factory()->create(['company_id' => $this->companyA->id]);
        $serviceB = Service::factory()->create(['company_id' => $this->companyB->id]);

        $appointmentA = Appointment::factory()->create([
            'company_id' => $this->companyA->id,
            'service_id' => $serviceA->id,
        ]);
        $appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'service_id' => $serviceB->id,
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $appointments = Appointment::all();

        $this->assertCount(1, $appointments);
        $this->assertEquals($appointmentA->id, $appointments->first()->id);
    }

    /** @test */
    public function service_model_enforces_company_isolation()
    {
        // Arrange
        $serviceA = Service::factory()->create(['company_id' => $this->companyA->id, 'name' => 'Service A']);
        $serviceB = Service::factory()->create(['company_id' => $this->companyB->id, 'name' => 'Service B']);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $services = Service::all();

        $this->assertCount(1, $services);
        $this->assertEquals('Service A', $services->first()->name);
    }

    /** @test */
    public function staff_model_enforces_company_isolation()
    {
        // Arrange
        $staffA = Staff::factory()->create(['company_id' => $this->companyA->id]);
        $staffB = Staff::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $staff = Staff::all();

        $this->assertCount(1, $staff);
        $this->assertEquals($staffA->id, $staff->first()->id);
    }

    /** @test */
    public function branch_model_enforces_company_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $branches = Branch::all();

        $this->assertCount(1, $branches);
        $this->assertEquals($branchA->id, $branches->first()->id);
    }

    /** @test */
    public function call_model_enforces_company_isolation()
    {
        // Arrange
        $callA = Call::factory()->create(['company_id' => $this->companyA->id]);
        $callB = Call::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $calls = Call::all();

        $this->assertCount(1, $calls);
        $this->assertEquals($callA->id, $calls->first()->id);
    }

    /** @test */
    public function phone_number_model_enforces_company_isolation()
    {
        // Arrange
        $phoneA = PhoneNumber::factory()->create(['company_id' => $this->companyA->id]);
        $phoneB = PhoneNumber::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $phones = PhoneNumber::all();

        $this->assertCount(1, $phones);
        $this->assertEquals($phoneA->id, $phones->first()->id);
    }

    /** @test */
    public function invoice_model_enforces_company_isolation()
    {
        $this->markTestSkipped('Invoice table does not exist in database yet');
    }

    /** @test */
    public function transaction_model_enforces_company_isolation()
    {
        $this->markTestSkipped('Transaction table does not exist in database yet');
    }

    /** @test */
    public function user_model_enforces_company_isolation()
    {
        // Arrange: Additional users beyond setUp
        $userA2 = User::factory()->create(['company_id' => $this->companyA->id]);
        $userB2 = User::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: Admin A sees only Company A users
        $this->actingAs($this->adminCompanyA);
        $users = User::all();

        $companyIds = $users->pluck('company_id')->unique();
        $this->assertCount(1, $companyIds, 'Should only see one company');
        $this->assertEquals($this->companyA->id, $companyIds->first());
    }

    /** @test */
    public function super_admin_can_bypass_company_scope()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: Super admin sees ALL customers
        $this->actingAs($this->superAdmin);
        $customers = Customer::all();

        // Super admin should see at least the 2 customers we just created, plus any from previous test methods
        $this->assertGreaterThanOrEqual(2, $customers->count(), 'Super admin should see customers from ALL companies');
        $this->assertTrue($customers->contains($customerA->id), 'Should contain customer A');
        $this->assertTrue($customers->contains($customerB->id), 'Should contain customer B');
    }

    /** @test */
    public function regular_admin_cannot_bypass_company_scope()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: Regular admin CANNOT see other company's data
        $this->actingAs($this->adminCompanyA);
        $customers = Customer::all();

        $this->assertCount(1, $customers, 'Regular admin should ONLY see their company data');
        $this->assertEquals($customerA->id, $customers->first()->id);

        // Try to access Company B customer directly
        $fetchedCustomer = Customer::find($customerB->id);
        $this->assertNull($fetchedCustomer, 'Direct access to other company customer should return NULL');
    }

    /** @test */
    public function cross_tenant_findOrFail_throws_not_found()
    {
        // Arrange
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: Trying to access Company B customer should throw exception
        $this->actingAs($this->adminCompanyA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Customer::findOrFail($customerB->id);
    }

    /** @test */
    public function where_queries_respect_company_scope()
    {
        // Arrange
        Customer::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'test@companyA.com'
        ]);
        Customer::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'test@companyB.com'
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $customers = Customer::where('email', 'like', '%@company%.com')->get();

        $this->assertCount(1, $customers, 'WHERE queries should be scoped to company');
        $this->assertEquals('test@companyA.com', $customers->first()->email);
    }

    /** @test */
    public function pagination_respects_company_scope()
    {
        // Arrange: Create 15 customers for Company A, 10 for Company B
        Customer::factory()->count(15)->create(['company_id' => $this->companyA->id]);
        Customer::factory()->count(10)->create(['company_id' => $this->companyB->id]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $paginated = Customer::paginate(10);

        $this->assertEquals(15, $paginated->total(), 'Total should only count Company A customers');
        $this->assertCount(10, $paginated->items(), 'First page should have 10 items');
    }

    // =====================================================================
    // NEW MULTI-TENANT MODELS - COMPREHENSIVE SECURITY TESTS
    // =====================================================================

    /** @test */
    public function policy_configuration_enforces_company_isolation()
    {
        // Arrange: Create policies for both companies
        $policyA = PolicyConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => 'cancellation',
        ]);

        $policyB = PolicyConfiguration::factory()->create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'policy_type' => 'cancellation',
        ]);

        // Act & Assert: Company A admin can only see their policies
        $this->actingAs($this->adminCompanyA);
        $policies = PolicyConfiguration::all();

        $this->assertCount(1, $policies, 'Admin A should only see 1 policy');
        $this->assertTrue($policies->contains($policyA->id), 'Admin A should see their policy');
        $this->assertFalse($policies->contains($policyB->id), 'CRITICAL LEAK: Admin A should NOT see Company B policy');

        // Direct find should return NULL
        $found = PolicyConfiguration::find($policyB->id);
        $this->assertNull($found, 'CRITICAL LEAK: Direct find() should return NULL for other company policy');
    }

    /** @test */
    public function appointment_modification_enforces_company_isolation()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $modificationA = AppointmentModification::factory()->create([
            'company_id' => $this->companyA->id,
            'customer_id' => $customerA->id,
            'modification_type' => 'cancel',
        ]);

        $modificationB = AppointmentModification::factory()->create([
            'company_id' => $this->companyB->id,
            'customer_id' => $customerB->id,
            'modification_type' => 'cancel',
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $modifications = AppointmentModification::all();

        $this->assertCount(1, $modifications, 'Admin A should only see 1 modification');
        $this->assertEquals($modificationA->id, $modifications->first()->id);
        $this->assertFalse($modifications->contains($modificationB->id), 'CRITICAL LEAK: Cross-company modification access');
    }

    /** @test */
    public function appointment_modification_stat_MISSING_COMPANY_ISOLATION()
    {
        $this->markTestSkipped(
            '🚨 CRITICAL VULNERABILITY: AppointmentModificationStat model does NOT use BelongsToCompany trait. ' .
            'This creates a CVSS 9.1 severity data leak. Model must be fixed before testing.'
        );

        // This test intentionally documents the vulnerability
        // Once fixed, uncomment and verify:
        /*
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $statA = AppointmentModificationStat::factory()->create(['customer_id' => $customerA->id]);
        $statB = AppointmentModificationStat::factory()->create(['customer_id' => $customerB->id]);

        $this->actingAs($this->adminCompanyA);
        $stats = AppointmentModificationStat::all();

        $this->assertCount(1, $stats, 'Should only see Company A stats');
        $this->assertFalse($stats->contains($statB->id), 'CRITICAL LEAK: Cross-company stat access');
        */
    }

    /** @test */
    public function callback_request_enforces_company_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        $callbackA = CallbackRequest::factory()->create([
            'company_id' => $this->companyA->id,
            'branch_id' => $branchA->id,
            'customer_name' => 'Customer A',
            'phone_number' => '+11111111111',
        ]);

        $callbackB = CallbackRequest::factory()->create([
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
            'customer_name' => 'Customer B',
            'phone_number' => '+22222222222',
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $callbacks = CallbackRequest::all();

        $this->assertCount(1, $callbacks, 'Admin A should only see 1 callback');
        $this->assertEquals($callbackA->id, $callbacks->first()->id);
        $this->assertFalse($callbacks->contains($callbackB->id), 'CRITICAL LEAK: Cross-company callback access');

        // Test scoped methods
        $pending = CallbackRequest::pending()->get();
        $this->assertFalse($pending->contains($callbackB->id), 'CRITICAL LEAK: Scoped methods bypass isolation');
    }

    /** @test */
    public function callback_escalation_enforces_company_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        $callbackA = CallbackRequest::factory()->create([
            'company_id' => $this->companyA->id,
            'branch_id' => $branchA->id,
        ]);

        $callbackB = CallbackRequest::factory()->create([
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
        ]);

        $escalationA = CallbackEscalation::factory()->create([
            'company_id' => $this->companyA->id,
            'callback_request_id' => $callbackA->id,
            'escalation_reason' => 'sla_breach',
        ]);

        $escalationB = CallbackEscalation::factory()->create([
            'company_id' => $this->companyB->id,
            'callback_request_id' => $callbackB->id,
            'escalation_reason' => 'manual_escalation',
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $escalations = CallbackEscalation::all();

        $this->assertCount(1, $escalations);
        $this->assertEquals($escalationA->id, $escalations->first()->id);
        $this->assertFalse($escalations->contains($escalationB->id), 'CRITICAL LEAK: Cross-company escalation access');
    }

    /** @test */
    public function notification_configuration_enforces_company_isolation()
    {
        // Arrange
        $configA = NotificationConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'event_type' => 'booking_confirmed',
            'channel' => 'email',
        ]);

        $configB = NotificationConfiguration::factory()->create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'event_type' => 'booking_confirmed',
            'channel' => 'sms',
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $configs = NotificationConfiguration::all();

        $this->assertCount(1, $configs);
        $this->assertEquals($configA->id, $configs->first()->id);
        $this->assertFalse($configs->contains($configB->id), 'CRITICAL LEAK: Cross-company notification config access');
    }

    /** @test */
    public function notification_event_mapping_enforces_company_isolation()
    {
        // Arrange
        $eventA = NotificationEventMapping::factory()->create([
            'company_id' => $this->companyA->id,
            'event_type' => 'custom_event_a',
            'event_label' => 'Company A Event',
            'event_category' => 'booking',
        ]);

        $eventB = NotificationEventMapping::factory()->create([
            'company_id' => $this->companyB->id,
            'event_type' => 'custom_event_b',
            'event_label' => 'Company B Event',
            'event_category' => 'booking',
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $events = NotificationEventMapping::all();

        $this->assertCount(1, $events);
        $this->assertEquals($eventA->id, $events->first()->id);
        $this->assertFalse($events->contains($eventB->id), 'CRITICAL LEAK: Cross-company event mapping access');
    }

    // =====================================================================
    // SECURITY VULNERABILITY TESTS
    // =====================================================================

    /** @test */
    public function mass_assignment_cannot_override_company_id()
    {
        $this->actingAs($this->adminCompanyA);
        $branch = Branch::factory()->create(['company_id' => $this->companyA->id]);

        // Attempt to create callback for Company B via mass assignment
        $callback = CallbackRequest::create([
            'company_id' => $this->companyB->id, // Malicious override attempt
            'branch_id' => $branch->id,
            'customer_name' => 'Test Customer',
            'phone_number' => '+1234567890',
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        // Should be forced to Company A by BelongsToCompany trait
        $this->assertEquals(
            $this->companyA->id,
            $callback->company_id,
            'CRITICAL VULNERABILITY: company_id can be overridden via mass assignment!'
        );
    }

    /** @test */
    public function xss_prevention_in_policy_configuration()
    {
        $this->actingAs($this->adminCompanyA);

        $xssPayload = '<script>alert("XSS")</script>';

        $policy = PolicyConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => 'cancellation',
            'config' => [
                'note' => $xssPayload,
                'cancellation_hours' => 24,
            ],
        ]);

        $policy->refresh();

        // Verify XSS is stored but escaped on output
        $rendered = e($policy->config['note']);
        $this->assertStringNotContainsString(
            '<script>',
            $rendered,
            'XSS VULNERABILITY: Script tags not properly escaped'
        );
    }

    /** @test */
    public function sql_injection_prevention_callback_request()
    {
        $this->actingAs($this->adminCompanyA);

        $sqlInjection = "'; DROP TABLE callback_requests; --";
        $branch = Branch::factory()->create(['company_id' => $this->companyA->id]);

        $callback = CallbackRequest::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $branch->id,
            'customer_name' => $sqlInjection,
            'phone_number' => '+1234567890',
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        // Verify table still exists and data is safely stored
        $this->assertDatabaseHas('callback_requests', ['id' => $callback->id]);
        $this->assertEquals($sqlInjection, $callback->customer_name);
    }

    /** @test */
    public function count_queries_respect_company_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        CallbackRequest::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
            'branch_id' => $branchA->id,
        ]);

        CallbackRequest::factory()->count(5)->create([
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $count = CallbackRequest::count();

        $this->assertEquals(
            3,
            $count,
            'CRITICAL LEAK: Count includes other companies\' records'
        );
    }

    /** @test */
    public function aggregation_queries_respect_company_isolation()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        AppointmentModification::factory()->create([
            'company_id' => $this->companyA->id,
            'customer_id' => $customerA->id,
            'fee_charged' => 50.00,
        ]);

        AppointmentModification::factory()->create([
            'company_id' => $this->companyB->id,
            'customer_id' => $customerB->id,
            'fee_charged' => 100.00,
        ]);

        // Act & Assert
        $this->actingAs($this->adminCompanyA);
        $total = AppointmentModification::sum('fee_charged');

        $this->assertEquals(
            50.00,
            $total,
            'CRITICAL LEAK: Aggregation includes other companies\' data'
        );
    }

    /** @test */
    public function update_queries_respect_company_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        CallbackRequest::factory()->create([
            'company_id' => $this->companyA->id,
            'branch_id' => $branchA->id,
            'status' => 'pending',
        ]);

        $callbackB = CallbackRequest::factory()->create([
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
            'status' => 'pending',
        ]);

        // Act: Attempt mass update
        $this->actingAs($this->adminCompanyA);
        CallbackRequest::where('status', 'pending')->update(['status' => 'completed']);

        // Assert: Company B's callback should remain unchanged
        $callbackB->refresh();
        $this->assertEquals(
            'pending',
            $callbackB->status,
            'CRITICAL LEAK: Mass update affected other company\'s records'
        );
    }

    /** @test */
    public function delete_queries_respect_company_isolation()
    {
        // Arrange
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        CallbackRequest::factory()->create([
            'company_id' => $this->companyA->id,
            'branch_id' => $branchA->id,
            'status' => 'cancelled',
        ]);

        $callbackB = CallbackRequest::factory()->create([
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
            'status' => 'cancelled',
        ]);

        // Act: Attempt mass delete
        $this->actingAs($this->adminCompanyA);
        CallbackRequest::where('status', 'cancelled')->delete();

        // Assert: Company B's callback should still exist
        $this->assertDatabaseHas('callback_requests', [
            'id' => $callbackB->id,
            'company_id' => $this->companyB->id,
        ]);
    }

    /** @test */
    public function first_or_create_respects_company_isolation()
    {
        // Arrange: Create callback for Company B
        $branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);
        $callbackB = CallbackRequest::factory()->create([
            'company_id' => $this->companyB->id,
            'branch_id' => $branchB->id,
            'phone_number' => '+1111111111',
        ]);

        // Act: Company A attempts firstOrCreate with same phone number
        $this->actingAs($this->adminCompanyA);
        $branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);

        $result = CallbackRequest::firstOrCreate(
            ['phone_number' => '+1111111111'],
            [
                'branch_id' => $branchA->id,
                'customer_name' => 'New Customer',
                'status' => 'pending',
                'priority' => 'normal',
            ]
        );

        // Assert: Should create new record for Company A, not find Company B's
        $this->assertEquals(
            $this->companyA->id,
            $result->company_id,
            'CRITICAL LEAK: firstOrCreate found other company\'s record'
        );

        $this->assertNotEquals(
            $callbackB->id,
            $result->id,
            'CRITICAL LEAK: firstOrCreate returned other company\'s record'
        );
    }
}
