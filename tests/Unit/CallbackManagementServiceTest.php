<?php

namespace Tests\Unit;

use App\Models\CallbackRequest;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Services\Appointments\CallbackManagementService;
use App\Events\Appointments\CallbackRequested;
use App\Events\Appointments\CallbackEscalated;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CallbackManagementServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected CallbackManagementService $service;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Service $testService;
    protected Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CallbackManagementService();

        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->testService = Service::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_callback_request_with_required_fields()
    {
        Event::fake([CallbackRequested::class]);

        $data = [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'phone_number' => '+49123456789',
            'customer_name' => 'Test Customer',
        ];

        $callback = $this->service->createRequest($data);

        $this->assertInstanceOf(CallbackRequest::class, $callback);
        $this->assertEquals($data['phone_number'], $callback->phone_number);
        $this->assertEquals($data['customer_name'], $callback->customer_name);
        // Auto-assignment is enabled by default, so status should be 'assigned'
        $this->assertEquals(CallbackRequest::STATUS_ASSIGNED, $callback->status);
        $this->assertEquals(CallbackRequest::PRIORITY_NORMAL, $callback->priority);
        $this->assertNotNull($callback->expires_at);
        $this->assertNotNull($callback->assigned_to); // Should be auto-assigned

        Event::assertDispatched(CallbackRequested::class);
    }

    /** @test */
    public function it_auto_assigns_callback_to_staff()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'phone_number' => '+49123456789',
            'customer_name' => 'Test Customer',
            'priority' => CallbackRequest::PRIORITY_HIGH,
        ];

        $callback = $this->service->createRequest($data);

        $this->assertNotNull($callback->assigned_to);
        $this->assertEquals(CallbackRequest::STATUS_ASSIGNED, $callback->status);
        $this->assertNotNull($callback->assigned_at);
    }

    /** @test */
    public function it_assigns_callback_to_specific_staff()
    {
        $callback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => CallbackRequest::STATUS_PENDING,
        ]);

        $this->service->assignToStaff($callback, $this->staff);

        $callback->refresh();

        $this->assertEquals($this->staff->id, $callback->assigned_to);
        $this->assertEquals(CallbackRequest::STATUS_ASSIGNED, $callback->status);
        $this->assertNotNull($callback->assigned_at);
    }

    /** @test */
    public function it_marks_callback_as_contacted()
    {
        $callback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
        ]);

        $this->service->markContacted($callback);

        $callback->refresh();

        $this->assertEquals(CallbackRequest::STATUS_CONTACTED, $callback->status);
        $this->assertNotNull($callback->contacted_at);
    }

    /** @test */
    public function it_marks_callback_as_completed_with_notes()
    {
        $callback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => CallbackRequest::STATUS_CONTACTED,
            'assigned_to' => $this->staff->id,
        ]);

        $notes = 'Customer rebooked successfully';

        $this->service->markCompleted($callback, $notes);

        $callback->refresh();

        $this->assertEquals(CallbackRequest::STATUS_COMPLETED, $callback->status);
        $this->assertEquals($notes, $callback->notes);
        $this->assertNotNull($callback->completed_at);
    }

    /** @test */
    public function it_escalates_callback_and_fires_event()
    {
        Event::fake([CallbackEscalated::class]);

        // Create another staff member for escalation target
        $escalationTarget = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $callback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
        ]);

        $reason = 'sla_breach';

        $escalation = $this->service->escalate($callback, $reason);

        $this->assertNotNull($escalation);
        $this->assertEquals($reason, $escalation->escalation_reason);
        $this->assertEquals($this->staff->id, $escalation->escalated_from);
        $this->assertEquals($escalationTarget->id, $escalation->escalated_to);

        Event::assertDispatched(CallbackEscalated::class);
    }

    /** @test */
    public function it_gets_overdue_callbacks_for_branch()
    {
        // Create overdue callback
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'expires_at' => now()->subHours(2),
        ]);

        // Create non-overdue callback
        $activeCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'expires_at' => now()->addHours(2),
        ]);

        $overdueCallbacks = $this->service->getOverdueCallbacks($this->branch);

        $this->assertCount(1, $overdueCallbacks);
        $this->assertEquals($overdueCallback->id, $overdueCallbacks->first()->id);
    }

    /** @test */
    public function it_assigns_preferred_staff_when_available()
    {
        $preferredStaff = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $data = [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'staff_id' => $preferredStaff->id,
            'phone_number' => '+49123456789',
            'customer_name' => 'Test Customer',
            'priority' => CallbackRequest::PRIORITY_HIGH,
        ];

        $callback = $this->service->createRequest($data);

        $this->assertEquals($preferredStaff->id, $callback->assigned_to);
    }

    /** @test */
    public function it_sets_expiration_based_on_priority()
    {
        $urgentData = [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'phone_number' => '+49123456789',
            'customer_name' => 'Test Customer',
            'priority' => CallbackRequest::PRIORITY_URGENT,
        ];

        $urgentCallback = $this->service->createRequest($urgentData);

        $normalData = $urgentData;
        $normalData['priority'] = CallbackRequest::PRIORITY_NORMAL;

        $normalCallback = $this->service->createRequest($normalData);

        // Urgent callbacks should expire sooner
        $this->assertLessThan(
            $normalCallback->expires_at,
            $urgentCallback->expires_at
        );
    }
}
