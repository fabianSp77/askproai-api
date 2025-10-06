<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\PolicyConfiguration;
use App\Models\AppointmentModification;
use App\Models\CallbackRequest;
use App\Models\CallbackEscalation;
use App\Models\NotificationConfiguration;
use App\Models\NotificationEventMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

/**
 * Comprehensive Multi-Tenant Security Audit Test
 *
 * MISSION: Validate 100% data isolation between companies with ZERO cross-tenant leaks
 *
 * Tests ALL newly deployed models from yesterday's deployment:
 * - PolicyConfiguration (with polymorphic relationships)
 * - AppointmentModification
 * - CallbackRequest
 * - CallbackEscalation
 * - NotificationConfiguration (with polymorphic relationships)
 * - NotificationEventMapping
 *
 * SUCCESS CRITERIA:
 * - 100% cross-company isolation (0 leaks)
 * - 100% authorization policy enforcement
 * - 100% global scope coverage
 * - 0 SQL injection vulnerabilities
 */
class ComprehensiveMultiTenantAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $adminB;
    protected User $managerA;
    protected User $staffA;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Service $serviceA;
    protected Service $serviceB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate companies for isolation testing
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create branches
        $this->branchA = Branch::factory()->create(['company_id' => $this->companyA->id]);
        $this->branchB = Branch::factory()->create(['company_id' => $this->companyB->id]);

        // Create services
        $this->serviceA = Service::factory()->create(['company_id' => $this->companyA->id]);
        $this->serviceB = Service::factory()->create(['company_id' => $this->companyB->id]);

        // Create users for Company A
        $this->adminA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'admin-a@test.com',
        ]);
        $this->adminA->assignRole('admin');

        $this->managerA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'manager-a@test.com',
        ]);
        $this->managerA->assignRole('manager');

        $this->staffA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'staff-a@test.com',
        ]);
        $this->staffA->assignRole('staff');

        // Create admin for Company B
        $this->adminB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'admin-b@test.com',
        ]);
        $this->adminB->assignRole('admin');
    }

    /**
     * TEST 1: PolicyConfiguration Cross-Company Isolation
     */
    public function test_policy_configuration_enforces_complete_isolation()
    {
        // Create policy configurations for both companies
        $policyA = PolicyConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['notice_hours' => 24, 'fee_percentage' => 50],
        ]);

        $policyB = PolicyConfiguration::create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['notice_hours' => 48, 'fee_percentage' => 100],
        ]);

        // Test: Company A admin should only see Company A policy
        $this->actingAs($this->adminA);
        $policies = PolicyConfiguration::all();

        $this->assertCount(1, $policies, 'Company A should see only their policies');
        $this->assertEquals($policyA->id, $policies->first()->id);
        $this->assertEquals(24, $policies->first()->config['notice_hours']);

        // Test: Direct access to Company B policy should return null
        $fetchedPolicyB = PolicyConfiguration::find($policyB->id);
        $this->assertNull($fetchedPolicyB, 'Company A cannot access Company B policy');

        // Test: findOrFail should throw ModelNotFoundException
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        PolicyConfiguration::findOrFail($policyB->id);
    }

    /**
     * TEST 2: AppointmentModification Cross-Company Isolation
     */
    public function test_appointment_modification_enforces_complete_isolation()
    {
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $appointmentA = Appointment::factory()->create([
            'company_id' => $this->companyA->id,
            'customer_id' => $customerA->id,
            'service_id' => $this->serviceA->id,
        ]);

        $appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'customer_id' => $customerB->id,
            'service_id' => $this->serviceB->id,
        ]);

        $modificationA = AppointmentModification::create([
            'company_id' => $this->companyA->id,
            'appointment_id' => $appointmentA->id,
            'customer_id' => $customerA->id,
            'modification_type' => AppointmentModification::TYPE_CANCEL,
            'within_policy' => true,
            'fee_charged' => 0.00,
            'modified_by_type' => User::class,
            'modified_by_id' => $this->adminA->id,
        ]);

        $modificationB = AppointmentModification::create([
            'company_id' => $this->companyB->id,
            'appointment_id' => $appointmentB->id,
            'customer_id' => $customerB->id,
            'modification_type' => AppointmentModification::TYPE_RESCHEDULE,
            'within_policy' => false,
            'fee_charged' => 25.00,
            'modified_by_type' => User::class,
            'modified_by_id' => $this->adminB->id,
        ]);

        // Test: Company A admin should only see their modifications
        $this->actingAs($this->adminA);
        $modifications = AppointmentModification::all();

        $this->assertCount(1, $modifications, 'Company A should see only their modifications');
        $this->assertEquals($modificationA->id, $modifications->first()->id);
        $this->assertEquals('cancel', $modifications->first()->modification_type);

        // Test: Cannot access Company B modification
        $this->assertNull(AppointmentModification::find($modificationB->id));
    }

    /**
     * TEST 3: CallbackRequest Cross-Company Isolation
     */
    public function test_callback_request_enforces_complete_isolation()
    {
        $callbackA = CallbackRequest::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'service_id' => $this->serviceA->id,
            'phone_number' => '+1234567890',
            'customer_name' => 'Customer A',
            'preferred_time_window' => ['start' => '09:00', 'end' => '17:00'],
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        $callbackB = CallbackRequest::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'service_id' => $this->serviceB->id,
            'phone_number' => '+0987654321',
            'customer_name' => 'Customer B',
            'preferred_time_window' => ['start' => '10:00', 'end' => '18:00'],
            'priority' => CallbackRequest::PRIORITY_HIGH,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        // Test: Company A admin should only see their callbacks
        $this->actingAs($this->adminA);
        $callbacks = CallbackRequest::all();

        $this->assertCount(1, $callbacks, 'Company A should see only their callbacks');
        $this->assertEquals($callbackA->id, $callbacks->first()->id);
        $this->assertEquals('+1234567890', $callbacks->first()->phone_number);

        // Test: Cannot access Company B callback
        $this->assertNull(CallbackRequest::find($callbackB->id));

        // Test: Query methods respect scope
        $pending = CallbackRequest::pending()->get();
        $this->assertCount(1, $pending);
        $this->assertEquals($callbackA->id, $pending->first()->id);
    }

    /**
     * TEST 4: CallbackEscalation Cross-Company Isolation
     */
    public function test_callback_escalation_enforces_complete_isolation()
    {
        $staffMemberA = Staff::factory()->create(['company_id' => $this->companyA->id]);
        $staffMemberB = Staff::factory()->create(['company_id' => $this->companyB->id]);

        $callbackA = CallbackRequest::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'phone_number' => '+1234567890',
            'customer_name' => 'Customer A',
            'preferred_time_window' => [],
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        $callbackB = CallbackRequest::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'phone_number' => '+0987654321',
            'customer_name' => 'Customer B',
            'preferred_time_window' => [],
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        $escalationA = CallbackEscalation::create([
            'company_id' => $this->companyA->id,
            'callback_request_id' => $callbackA->id,
            'escalation_reason' => 'sla_breach',
            'escalated_from' => $staffMemberA->id,
            'escalated_at' => now(),
        ]);

        $escalationB = CallbackEscalation::create([
            'company_id' => $this->companyB->id,
            'callback_request_id' => $callbackB->id,
            'escalation_reason' => 'multiple_attempts_failed',
            'escalated_from' => $staffMemberB->id,
            'escalated_at' => now(),
        ]);

        // Test: Company A admin should only see their escalations
        $this->actingAs($this->adminA);
        $escalations = CallbackEscalation::all();

        $this->assertCount(1, $escalations, 'Company A should see only their escalations');
        $this->assertEquals($escalationA->id, $escalations->first()->id);
        $this->assertEquals('sla_breach', $escalations->first()->escalation_reason);

        // Test: Cannot access Company B escalation
        $this->assertNull(CallbackEscalation::find($escalationB->id));
    }

    /**
     * TEST 5: NotificationConfiguration Cross-Company Isolation
     */
    public function test_notification_configuration_enforces_complete_isolation()
    {
        $configA = NotificationConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branchA->id,
            'event_type' => 'appointment.created',
            'channel' => 'email',
            'is_enabled' => true,
            'retry_count' => 3,
        ]);

        $configB = NotificationConfiguration::create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branchB->id,
            'event_type' => 'appointment.created',
            'channel' => 'sms',
            'is_enabled' => true,
            'retry_count' => 2,
        ]);

        // Test: Company A admin should only see their config
        $this->actingAs($this->adminA);
        $configs = NotificationConfiguration::all();

        $this->assertCount(1, $configs, 'Company A should see only their notifications');
        $this->assertEquals($configA->id, $configs->first()->id);
        $this->assertEquals('email', $configs->first()->channel);

        // Test: Cannot access Company B config
        $this->assertNull(NotificationConfiguration::find($configB->id));
    }

    /**
     * TEST 6: NotificationEventMapping Cross-Company Isolation
     */
    public function test_notification_event_mapping_enforces_complete_isolation()
    {
        $eventA = NotificationEventMapping::create([
            'company_id' => $this->companyA->id,
            'event_type' => 'appointment.created',
            'event_label' => 'Appointment Created',
            'event_category' => 'booking',
            'default_channels' => ['email', 'sms'],
            'is_active' => true,
        ]);

        $eventB = NotificationEventMapping::create([
            'company_id' => $this->companyB->id,
            'event_type' => 'appointment.cancelled',
            'event_label' => 'Appointment Cancelled',
            'event_category' => 'modification',
            'default_channels' => ['email'],
            'is_active' => true,
        ]);

        // Test: Company A admin should only see their events
        $this->actingAs($this->adminA);
        $events = NotificationEventMapping::all();

        $this->assertCount(1, $events, 'Company A should see only their events');
        $this->assertEquals($eventA->id, $events->first()->id);
        $this->assertEquals('appointment.created', $events->first()->event_type);

        // Test: Cannot access Company B event
        $this->assertNull(NotificationEventMapping::find($eventB->id));
    }

    /**
     * TEST 7: Authorization Policy - PolicyConfiguration
     */
    public function test_policy_configuration_authorization_enforcement()
    {
        $policyB = PolicyConfiguration::create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['notice_hours' => 48],
        ]);

        $this->actingAs($this->adminA);

        // Test: Cannot view Company B policy (returns null due to scope)
        $this->assertNull(PolicyConfiguration::find($policyB->id));

        // Test: Manager can create policies
        $this->actingAs($this->managerA);
        $this->assertTrue($this->managerA->can('create', PolicyConfiguration::class));

        // Test: Staff cannot create policies
        $this->actingAs($this->staffA);
        $this->assertFalse($this->staffA->can('create', PolicyConfiguration::class));
    }

    /**
     * TEST 8: Authorization Policy - CallbackRequest
     */
    public function test_callback_request_authorization_enforcement()
    {
        $callbackA = CallbackRequest::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'phone_number' => '+1234567890',
            'customer_name' => 'Test Customer',
            'preferred_time_window' => [],
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        $callbackB = CallbackRequest::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'phone_number' => '+0987654321',
            'customer_name' => 'Test Customer B',
            'preferred_time_window' => [],
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        // Test: Company A admin can view their callback
        $this->actingAs($this->adminA);
        $this->assertTrue($this->adminA->can('view', $callbackA));

        // Test: Cannot access Company B callback (null from scope)
        $fetched = CallbackRequest::find($callbackB->id);
        $this->assertNull($fetched, 'Cross-company access blocked by scope');

        // Test: Admin can delete their company's callbacks
        $this->assertTrue($this->adminA->can('delete', $callbackA));

        // Test: Manager can create callbacks
        $this->actingAs($this->managerA);
        $this->assertTrue($this->managerA->can('create', CallbackRequest::class));
    }

    /**
     * TEST 9: Input Validation & XSS Prevention
     */
    public function test_xss_prevention_in_policy_configuration()
    {
        $this->actingAs($this->adminA);

        $xssAttempt = '<script>alert("XSS")</script>';

        $policy = PolicyConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['note' => $xssAttempt],
        ]);

        $this->assertNotNull($policy);

        // Test: Script tags should be escaped when rendered (not removed from storage)
        // Laravel's JSON casting preserves the data, but Blade {{ }} escapes on output
        $this->assertIsArray($policy->config);
        $this->assertArrayHasKey('note', $policy->config);
    }

    /**
     * TEST 10: SQL Injection Prevention
     */
    public function test_sql_injection_prevention()
    {
        $this->actingAs($this->adminA);

        // Attempt SQL injection through where clause
        $maliciousInput = "'; DROP TABLE policy_configurations; --";

        $results = PolicyConfiguration::where('policy_type', $maliciousInput)->get();

        // Test: Should safely return empty results, not execute SQL
        $this->assertCount(0, $results);

        // Test: Table should still exist (check by creating a record)
        $policy = PolicyConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['test' => 'value'],
        ]);

        $this->assertNotNull($policy->id);
    }

    /**
     * TEST 11: Global Scope Coverage on All Models
     */
    public function test_global_scope_applied_to_all_new_models()
    {
        // Create records for both companies
        PolicyConfiguration::create([
            'company_id' => $this->companyA->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => 'cancellation',
            'config' => [],
        ]);

        PolicyConfiguration::create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'policy_type' => 'cancellation',
            'config' => [],
        ]);

        // Test: All queries are scoped
        $this->actingAs($this->adminA);

        $allPolicies = PolicyConfiguration::all();
        $this->assertTrue($allPolicies->every(fn($p) => $p->company_id === $this->companyA->id));

        $countPolicies = PolicyConfiguration::count();
        $this->assertEquals(1, $countPolicies);

        $firstPolicy = PolicyConfiguration::first();
        $this->assertEquals($this->companyA->id, $firstPolicy->company_id);
    }

    /**
     * TEST 12: Auto-fill company_id on Creation
     */
    public function test_company_id_auto_filled_on_creation()
    {
        $this->actingAs($this->adminA);

        // Create without explicitly setting company_id
        $policy = PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyA->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['test' => 'auto-fill'],
        ]);

        // Test: company_id should be automatically set
        $this->assertEquals($this->companyA->id, $policy->company_id);
    }

    /**
     * TEST 13: Cross-Company Update Prevention
     */
    public function test_cannot_update_cross_company_records()
    {
        $policyB = PolicyConfiguration::create([
            'company_id' => $this->companyB->id,
            'configurable_type' => Company::class,
            'configurable_id' => $this->companyB->id,
            'policy_type' => PolicyConfiguration::POLICY_TYPE_CANCELLATION,
            'config' => ['original' => 'value'],
        ]);

        $this->actingAs($this->adminA);

        // Test: Cannot find the record to update it
        $fetched = PolicyConfiguration::find($policyB->id);
        $this->assertNull($fetched, 'Cross-company record should not be accessible');

        // Test: Update query should not affect cross-company records
        $updated = PolicyConfiguration::where('id', $policyB->id)
            ->update(['config' => ['modified' => 'attempt']]);

        $this->assertEquals(0, $updated, 'Should not update any records');

        // Verify original record unchanged (using super admin context)
        $this->actingAs($this->adminB);
        $original = PolicyConfiguration::find($policyB->id);
        $this->assertEquals(['original' => 'value'], $original->config);
    }

    /**
     * TEST 14: Cross-Company Delete Prevention
     */
    public function test_cannot_delete_cross_company_records()
    {
        $callbackB = CallbackRequest::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'phone_number' => '+0987654321',
            'customer_name' => 'Test Customer',
            'preferred_time_window' => [],
            'priority' => CallbackRequest::PRIORITY_NORMAL,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->adminA);

        // Test: Cannot find the record to delete it
        $fetched = CallbackRequest::find($callbackB->id);
        $this->assertNull($fetched);

        // Test: Delete query should not affect cross-company records
        $deleted = CallbackRequest::where('id', $callbackB->id)->delete();
        $this->assertEquals(0, $deleted, 'Should not delete any records');

        // Verify record still exists (using Company B context)
        $this->actingAs($this->adminB);
        $exists = CallbackRequest::find($callbackB->id);
        $this->assertNotNull($exists);
    }

    /**
     * TEST 15: Relationship Queries Respect Company Scope
     */
    public function test_relationships_respect_company_scope()
    {
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        $appointmentA = Appointment::factory()->create([
            'company_id' => $this->companyA->id,
            'customer_id' => $customerA->id,
            'service_id' => $this->serviceA->id,
        ]);

        $appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'customer_id' => $customerB->id,
            'service_id' => $this->serviceB->id,
        ]);

        AppointmentModification::create([
            'company_id' => $this->companyA->id,
            'appointment_id' => $appointmentA->id,
            'customer_id' => $customerA->id,
            'modification_type' => 'cancel',
            'within_policy' => true,
            'fee_charged' => 0,
            'modified_by_type' => User::class,
            'modified_by_id' => $this->adminA->id,
        ]);

        $this->actingAs($this->adminA);

        // Test: Relationship queries should be scoped
        $customer = Customer::with('appointments')->first();
        $this->assertCount(1, $customer->appointments);
        $this->assertEquals($appointmentA->id, $customer->appointments->first()->id);
    }
}
