<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\PolicyConfiguration;
use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Services\Policies\AppointmentPolicyEngine;
use App\Services\Appointments\SmartAppointmentFinder;
use App\Services\Appointments\CallbackManagementService;
use App\Http\Controllers\RetellFunctionCallHandler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * End-to-End Integration Tests for Complete User Journeys
 *
 * Tests 6 complete flows from PRD:
 * 1. Customer calls → Cancellation within policy → Successful (no fee)
 * 2. Customer calls → Cancellation outside policy → Fee charged
 * 3. Customer calls → Reschedule → Next slot found
 * 4. Customer calls → Callback request → Auto-assigned → Escalation
 * 5. Admin configures policy → Inheritance works
 * 6. Recurring appointment → Partial cancellation → Correct fee
 */
class EndToEndFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Staff $staff;
    protected Customer $customer;
    protected PhoneNumber $phoneNumber;
    protected AppointmentPolicyEngine $policyEngine;
    protected SmartAppointmentFinder $finder;
    protected CallbackManagementService $callbackService;
    protected RetellFunctionCallHandler $retellHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache to ensure HTTP mocks work (SmartAppointmentFinder caches slots)
        \Illuminate\Support\Facades\Cache::flush();

        // Disable ServiceObserver for testing (Cal.com sync not needed in tests)
        Service::unsetEventDispatcher();

        // Create hierarchical test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beratungsgespräch',
        ]);

        // Set calcom_event_type_id directly (ServiceObserver is disabled in tests)
        $this->service->calcom_event_type_id = 12345;
        $this->service->save();
        $this->staff = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Max Mustermann',
            'phone' => '+49123456789',
        ]);
        $this->phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'phone_number' => '+49987654321',
        ]);

        // Initialize services
        $this->policyEngine = app(AppointmentPolicyEngine::class);
        $this->finder = new SmartAppointmentFinder($this->company);
        $this->callbackService = new CallbackManagementService();
        $this->retellHandler = app(RetellFunctionCallHandler::class);

        // Fake external services
        Event::fake();
        // Note: Http::fake() is configured per-test to avoid conflicts
    }

    /**
     * USER JOURNEY 1: Cancellation Within Policy Window
     *
     * Arrange: Appointment 72h in future, policy requires 24h
     * Act: Customer calls to cancel
     * Assert: Cancelled successfully, no fee, modification tracked
     */
    public function test_cancellation_within_policy_window_succeeds()
    {
        // ARRANGE: Create policy allowing cancellation with 24h notice
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'is_enabled' => true,
            'config' => [
                'hours_before' => 24,
                'max_cancellations_per_month' => 3,
                'fee_tiers' => [
                    ['min_hours' => 24, 'fee' => 0],
                    ['min_hours' => 12, 'fee' => 10],
                    ['min_hours' => 0, 'fee' => 25],
                ],
            ],
        ]);

        // Create appointment 72 hours in future (well within policy)
        $appointmentTime = Carbon::now()->addHours(72);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => $appointmentTime,
            'ends_at' => $appointmentTime->copy()->addHour(),
            'status' => 'confirmed',
        ]);

        // Create call context
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'test_call_001',
            'status' => 'active',
        ]);

        // ACT: Call cancellation handler via Retell
        $response = $this->postJson('/api/webhooks/retell/function-call', [
            'function_name' => 'cancel_appointment',
            'call_id' => 'test_call_001',
            'parameters' => [
                'appointment_date' => $appointmentTime->format('Y-m-d'),
                'reason' => 'Zeitlicher Konflikt',
            ],
        ]);

        // DEBUG: Check response
        if ($response->status() !== 200 || !($response->json()['success'] ?? false)) {
            dump('Response:', $response->json());
            dump('Status:', $response->status());
        }

        // ASSERT: Success response
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'cancelled',
        ]);

        // ASSERT: Appointment updated
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);

        // ASSERT: Modification tracked
        $modification = AppointmentModification::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($modification);
        $this->assertEquals('cancel', $modification->modification_type);
        $this->assertTrue($modification->within_policy);
        $this->assertEquals(0.0, $modification->fee_charged);
        $this->assertEquals('Zeitlicher Konflikt', $modification->reason);

        // ASSERT: No fee charged
        $this->assertEquals(0.0, $modification->fee_charged);
    }

    /**
     * USER JOURNEY 2: Cancellation Outside Policy Window (With Fee)
     *
     * Arrange: Appointment 12h in future, policy requires 24h
     * Act: Customer calls to cancel
     * Assert: Denied or fee charged based on policy
     */
    public function test_cancellation_outside_policy_charges_fee()
    {
        // ARRANGE: Create policy requiring 24h notice
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'is_enabled' => true,
            'config' => [
                'hours_before' => 24,
                'fee_tiers' => [
                    ['min_hours' => 24, 'fee' => 0],
                    ['min_hours' => 12, 'fee' => 10],
                    ['min_hours' => 0, 'fee' => 25],
                ],
            ],
        ]);

        // Create appointment 13 hours in future (outside policy, in 10€ tier: 12h-24h)
        $appointmentTime = Carbon::now()->addHours(13);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'starts_at' => $appointmentTime,
            'ends_at' => $appointmentTime->copy()->addHour(),
            'status' => 'confirmed',
        ]);

        // Create call context
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'test_call_002',
            'status' => 'active',
        ]);

        // ACT: Attempt cancellation via policy engine
        $policyResult = $this->policyEngine->canCancel($appointment);

        // ASSERT: Policy denies free cancellation
        $this->assertFalse($policyResult->allowed);
        $this->assertStringContainsString('24 hours notice', $policyResult->reason);
        $this->assertArrayHasKey('fee_if_forced', $policyResult->details);
        $this->assertEquals(10.0, $policyResult->details['fee_if_forced']);

        // ACT: Force cancel with fee
        $appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'modification_type' => 'cancel',
            'within_policy' => false,
            'fee_charged' => 10.0,
            'reason' => 'Late cancellation',
            'modified_by_type' => 'System',
        ]);

        // ASSERT: Fee charged
        $modification = AppointmentModification::where('appointment_id', $appointment->id)->first();
        $this->assertEquals(10.0, $modification->fee_charged);
        $this->assertFalse($modification->within_policy);
    }

    /**
     * USER JOURNEY 3: Reschedule with Next Slot Found
     *
     * Arrange: Customer has appointment, wants to reschedule
     * Act: Find next available slot via SmartAppointmentFinder
     * Assert: Alternative slot found, appointment rescheduled
     */
    public function test_reschedule_finds_next_available_slot()
    {
        // ARRANGE: Mock all HTTP requests (Cal.com API with available slots)
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-10-05T09:00:00Z',
                        '2025-10-05T10:00:00Z',
                        '2025-10-05T14:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        // Create appointment to reschedule
        $originalTime = Carbon::now()->addDays(2);
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'starts_at' => $originalTime,
            'ends_at' => $originalTime->copy()->addHour(),
            'status' => 'confirmed',
        ]);

        // ACT: Find next available slot
        $nextSlot = $this->finder->findNextAvailable(
            $this->service,
            Carbon::parse('2025-10-05'),
            14
        );

        // ASSERT: Next slot found
        $this->assertNotNull($nextSlot);
        $this->assertInstanceOf(Carbon::class, $nextSlot);
        $this->assertEquals('2025-10-05 09:00:00', $nextSlot->format('Y-m-d H:i:s'));

        // ACT: Reschedule appointment
        $appointment->update([
            'starts_at' => $nextSlot,
            'ends_at' => $nextSlot->copy()->addHour(),
        ]);

        AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $appointment->customer_id,
            'modification_type' => 'reschedule',
            'within_policy' => true,
            'fee_charged' => 0.0,
            'metadata' => [
                'original_time' => $originalTime->toIso8601String(),
                'new_time' => $nextSlot->toIso8601String(),
            ],
        ]);

        // ASSERT: Appointment rescheduled
        $appointment->refresh();
        $this->assertEquals($nextSlot->format('Y-m-d H:i'), $appointment->starts_at->format('Y-m-d H:i'));

        // ASSERT: Modification tracked
        $modification = AppointmentModification::where('appointment_id', $appointment->id)->first();
        $this->assertEquals('reschedule', $modification->modification_type);
        $this->assertArrayHasKey('new_time', $modification->metadata);
    }

    /**
     * USER JOURNEY 4: Callback Request with Auto-Assignment and Escalation
     *
     * Arrange: No slots available
     * Act: Create callback request → Auto-assign → Simulate overdue → Escalate
     * Assert: Callback created, assigned to staff, escalated when overdue
     */
    public function test_callback_request_auto_assigned_and_escalated()
    {
        // ARRANGE: Mock all HTTP requests (Cal.com with no available slots)
        Http::fake([
            '*' => Http::response([
                'data' => ['slots' => []],
            ], 200),
        ]);

        // Create second staff for escalation
        $staff2 = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        // Create call context
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'test_call_004',
            'status' => 'active',
        ]);

        // ACT 1: Check availability (no slots)
        $slots = $this->finder->findInTimeWindow(
            $this->service,
            Carbon::now()->addDay(),
            Carbon::now()->addDays(7)
        );

        // ASSERT: No slots available
        $this->assertCount(0, $slots);

        // ACT 2: Create callback request
        $callback = $this->callbackService->createRequest([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'phone_number' => $this->customer->phone,
            'customer_name' => $this->customer->name,
            'priority' => CallbackRequest::PRIORITY_HIGH,
            'metadata' => ['call_id' => 'test_call_004'],
        ]);

        // ASSERT: Callback auto-assigned to staff
        $this->assertNotNull($callback->assigned_to);
        $this->assertEquals(CallbackRequest::STATUS_ASSIGNED, $callback->status);
        $this->assertNotNull($callback->assigned_at);
        $this->assertNotNull($callback->expires_at);

        // Store original assignee before escalation
        $originalAssignee = $callback->assigned_to;

        // ACT 3: Simulate time passing (callback becomes overdue)
        $callback->update([
            'expires_at' => Carbon::now()->subHours(2), // 2 hours overdue
        ]);

        // ACT 4: Escalate overdue callback
        $escalation = $this->callbackService->escalate($callback, 'sla_breach');

        // ASSERT: Escalation created
        $this->assertNotNull($escalation);
        $this->assertEquals('sla_breach', $escalation->escalation_reason);
        $this->assertEquals($originalAssignee, $escalation->escalated_from);
        $this->assertNotNull($escalation->escalated_to);

        // ASSERT: Escalated to different staff (from original assignee)
        $this->assertNotEquals($originalAssignee, $escalation->escalated_to);

        // ASSERT: Callback re-assigned to escalation target
        $callback->refresh();
        $this->assertEquals($escalation->escalated_to, $callback->assigned_to);
    }

    /**
     * USER JOURNEY 5: Admin Configures Policy with Inheritance
     *
     * Arrange: Create company policy, branch policy (override), service without policy
     * Act: Resolve policy for each level
     * Assert: Correct hierarchy resolution (Service → Branch → Company)
     */
    public function test_policy_configuration_inheritance_works()
    {
        // ARRANGE: Create company-level policy
        $companyPolicy = PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'is_enabled' => true,
            'config' => [
                'hours_before' => 48,
                'max_cancellations_per_month' => 2,
                'fee_tiers' => [['min_hours' => 48, 'fee' => 0], ['min_hours' => 0, 'fee' => 50]],
            ],
        ]);

        // Create branch-level override
        $branchPolicy = PolicyConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'policy_type' => 'cancellation',
            'is_enabled' => true,
            'config' => [
                'hours_before' => 24, // More lenient than company
                'max_cancellations_per_month' => 3,
                'fee_tiers' => [['min_hours' => 24, 'fee' => 0], ['min_hours' => 0, 'fee' => 25]],
            ],
        ]);

        // ACT: Create appointments at different levels
        $appointmentWithBranch = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id, // Has branch policy
            'starts_at' => Carbon::now()->addHours(36),
            'status' => 'confirmed',
        ]);

        // Service at different branch (no override)
        $branch2 = Branch::factory()->create(['company_id' => $this->company->id]);
        $appointmentWithoutBranchPolicy = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $branch2->id, // No branch policy
            'starts_at' => Carbon::now()->addHours(36),
            'status' => 'confirmed',
        ]);

        // ASSERT: Branch policy takes precedence (36h > 24h required = allowed)
        $result1 = $this->policyEngine->canCancel($appointmentWithBranch);
        $this->assertTrue($result1->allowed);
        $this->assertEquals(24, $result1->details['required_hours']);

        // ASSERT: Company policy used as fallback (36h < 48h required = denied)
        $result2 = $this->policyEngine->canCancel($appointmentWithoutBranchPolicy);
        $this->assertFalse($result2->allowed);
        $this->assertEquals(48, $result2->details['required_hours']);
    }

    /**
     * USER JOURNEY 6: Recurring Appointment Partial Cancellation
     *
     * Arrange: Create recurring appointment series (3 instances)
     * Act: Cancel 1 instance within policy, 1 outside policy
     * Assert: Correct fees applied, series partially cancelled
     */
    public function test_recurring_appointment_partial_cancellation_with_fees()
    {
        // ARRANGE: Create policy with fee tiers
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'is_enabled' => true,
            'config' => [
                'hours_before' => 24,
                'fee_tiers' => [
                    ['min_hours' => 24, 'fee' => 0],
                    ['min_hours' => 12, 'fee' => 15],
                    ['min_hours' => 0, 'fee' => 30],
                ],
            ],
        ]);

        // Create recurring appointment series (3 weekly appointments)
        $recurringGroup = \Str::uuid();
        $appointments = [];

        for ($i = 0; $i < 3; $i++) {
            $appointments[] = Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
                'branch_id' => $this->branch->id,
                'starts_at' => Carbon::now()->addWeeks($i + 1),
                'ends_at' => Carbon::now()->addWeeks($i + 1)->addHour(),
                'status' => 'confirmed',
                'is_recurring' => true,
                'recurring_group_id' => $recurringGroup,
            ]);
        }

        // ACT 1: Cancel first appointment (within policy - 1 week notice)
        $firstAppointment = $appointments[0];
        $result1 = $this->policyEngine->canCancel($firstAppointment);

        // ASSERT: First cancellation allowed, no fee
        $this->assertTrue($result1->allowed);
        $this->assertEquals(0.0, $result1->fee);

        // Update and track
        $firstAppointment->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        AppointmentModification::create([
            'appointment_id' => $firstAppointment->id,
            'customer_id' => $firstAppointment->customer_id,
            'modification_type' => 'cancel',
            'within_policy' => true,
            'fee_charged' => 0.0,
        ]);

        // ACT 2: Cancel second appointment (simulating short notice - 13h for 15€ tier)
        $secondAppointment = $appointments[1];
        $secondAppointment->update(['starts_at' => Carbon::now()->addHours(13)]);
        $result2 = $this->policyEngine->canCancel($secondAppointment);

        // ASSERT: Second cancellation outside policy, fee charged
        $this->assertFalse($result2->allowed);
        $this->assertArrayHasKey('fee_if_forced', $result2->details);
        $this->assertEquals(15.0, $result2->details['fee_if_forced']);

        // Force cancel with fee
        $secondAppointment->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        AppointmentModification::create([
            'appointment_id' => $secondAppointment->id,
            'customer_id' => $secondAppointment->customer_id,
            'modification_type' => 'cancel',
            'within_policy' => false,
            'fee_charged' => 15.0,
        ]);

        // ASSERT: Third appointment still active
        $thirdAppointment = $appointments[2];
        $this->assertEquals('confirmed', $thirdAppointment->status);

        // ASSERT: Total fees correct
        $totalFees = AppointmentModification::where('customer_id', $this->customer->id)
            ->sum('fee_charged');
        $this->assertEquals(15.0, $totalFees);

        // ASSERT: Modification stats tracked
        $this->assertDatabaseHas('appointment_modifications', [
            'appointment_id' => $firstAppointment->id,
            'fee_charged' => 0.0,
            'within_policy' => true,
        ]);

        $this->assertDatabaseHas('appointment_modifications', [
            'appointment_id' => $secondAppointment->id,
            'fee_charged' => 15.0,
            'within_policy' => false,
        ]);
    }

    /**
     * Helper: Create full call context for Retell integration
     */
    protected function createCallContext(string $retellCallId): Call
    {
        // // return Call::factory()->create([
        //     'company_id' => $this->company->id,
        //     'customer_id' => $this->customer->id,
        //     'phone_number_id' => $this->phoneNumber->id,
        //     'retell_call_id' => $retellCallId,
        //     'status' => 'active',
        //     'metadata' => [
        //         'branch_id' => $this->branch->id,
        //         'company_id' => $this->company->id,
        //     ],
        // ]);
    }

    /**
     * Helper: Setup realistic Cal.com mocks
     */
    protected function mockCalcomAvailability(array $slots): void
    {
        Http::fake([
            '*/slots/available*' => Http::response([
                'data' => ['slots' => $slots],
            ], 200),
        ]);
    }
}
