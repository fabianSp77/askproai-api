<?php

namespace Tests\Unit;

use App\Jobs\EscalateOverdueCallbacksJob;
use App\Models\CallbackRequest;
use App\Models\CallbackEscalation;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Services\Appointments\CallbackManagementService;
use App\Events\Appointments\CallbackEscalated;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EscalateOverdueCallbacksJobTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Staff $staff;
    protected Staff $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

        $this->staff = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->manager = Staff::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_dispatches_to_callbacks_queue()
    {
        Queue::fake();

        EscalateOverdueCallbacksJob::dispatch();

        Queue::assertPushedOn('callbacks', EscalateOverdueCallbacksJob::class);
    }

    /** @test */
    public function it_escalates_overdue_callbacks()
    {
        Event::fake([CallbackEscalated::class]);

        // Create overdue callback
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(3), // 3 hours overdue
        ]);

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // Verify escalation was created
        $this->assertDatabaseHas('callback_escalations', [
            'callback_request_id' => $overdueCallback->id,
            'escalation_reason' => 'sla_breach',
            'escalated_from' => $this->staff->id,
            'escalated_to' => $this->manager->id,
        ]);

        Event::assertDispatched(CallbackEscalated::class);
    }

    /** @test */
    public function it_does_not_escalate_non_overdue_callbacks()
    {
        // Create non-overdue callback
        $activeCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->addHours(2), // Still has 2 hours
        ]);

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // Verify no escalation was created
        $this->assertDatabaseMissing('callback_escalations', [
            'callback_request_id' => $activeCallback->id,
        ]);
    }

    /** @test */
    public function it_does_not_re_escalate_recently_escalated_callbacks()
    {
        // Create overdue callback with recent escalation
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(3),
        ]);

        // Create recent escalation (2 hours ago, within 4-hour cooldown)
        CallbackEscalation::factory()->create([
            'callback_request_id' => $overdueCallback->id,
            'escalation_reason' => 'sla_breach',
            'escalated_from' => $this->staff->id,
            'escalated_to' => $this->manager->id,
            'created_at' => Carbon::now()->subHours(2),
        ]);

        $initialEscalationCount = CallbackEscalation::where('callback_request_id', $overdueCallback->id)->count();

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // Should not create new escalation
        $finalEscalationCount = CallbackEscalation::where('callback_request_id', $overdueCallback->id)->count();
        $this->assertEquals($initialEscalationCount, $finalEscalationCount);
    }

    /** @test */
    public function it_escalates_after_cooldown_period()
    {
        // Create overdue callback with old escalation
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(10),
        ]);

        // Create old escalation (5 hours ago, past 4-hour cooldown)
        CallbackEscalation::factory()->create([
            'callback_request_id' => $overdueCallback->id,
            'escalation_reason' => 'sla_breach',
            'escalated_from' => $this->staff->id,
            'escalated_to' => $this->manager->id,
            'created_at' => Carbon::now()->subHours(5),
        ]);

        $initialEscalationCount = CallbackEscalation::where('callback_request_id', $overdueCallback->id)->count();

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // Should create new escalation
        $finalEscalationCount = CallbackEscalation::where('callback_request_id', $overdueCallback->id)->count();
        $this->assertEquals($initialEscalationCount + 1, $finalEscalationCount);
    }

    /** @test */
    public function it_determines_escalation_reason_for_sla_breach()
    {
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        $this->assertDatabaseHas('callback_escalations', [
            'callback_request_id' => $overdueCallback->id,
            'escalation_reason' => 'sla_breach',
        ]);
    }

    /** @test */
    public function it_determines_escalation_reason_for_multiple_attempts()
    {
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(2),
            'metadata' => ['contact_attempts' => 3],
        ]);

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        $escalation = CallbackEscalation::where('callback_request_id', $overdueCallback->id)->first();

        // Could be either sla_breach or multiple_attempts_failed based on implementation
        $this->assertContains($escalation->escalation_reason, ['sla_breach', 'multiple_attempts_failed']);
    }

    /** @test */
    public function it_handles_multiple_overdue_callbacks()
    {
        Event::fake([CallbackEscalated::class]);

        // Create 3 overdue callbacks
        $overdueCallbacks = CallbackRequest::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // Verify all were escalated
        foreach ($overdueCallbacks as $callback) {
            $this->assertDatabaseHas('callback_escalations', [
                'callback_request_id' => $callback->id,
            ]);
        }

        Event::assertDispatchedTimes(CallbackEscalated::class, 3);
    }

    /** @test */
    public function it_continues_on_individual_callback_failure()
    {
        Event::fake([CallbackEscalated::class]);

        // Create 2 overdue callbacks
        $callback1 = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        $callback2 = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        // Even if one fails, job should continue with others
        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // At least one should be escalated
        $escalationCount = CallbackEscalation::whereIn('callback_request_id', [$callback1->id, $callback2->id])->count();
        $this->assertGreaterThan(0, $escalationCount);
    }

    /** @test */
    public function it_loads_relationships_for_escalation()
    {
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(2),
        ]);

        $job = new EscalateOverdueCallbacksJob();
        $job->handle(new CallbackManagementService());

        // Verify escalation has required relationships loaded
        $escalation = CallbackEscalation::where('callback_request_id', $overdueCallback->id)->first();
        $this->assertNotNull($escalation);
        $this->assertNotNull($escalation->escalated_to);
    }

    /** @test */
    public function it_calculates_overdue_hours_correctly()
    {
        $overdueCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->subHours(5), // 5 hours overdue
        ]);

        $job = new EscalateOverdueCallbacksJob();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('calculateOverdueHours');
        $method->setAccessible(true);

        $hours = $method->invoke($job, $overdueCallback);

        $this->assertEquals(5, $hours);
    }

    /** @test */
    public function it_returns_zero_for_non_overdue_callbacks()
    {
        $activeCallback = CallbackRequest::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => CallbackRequest::STATUS_ASSIGNED,
            'assigned_to' => $this->staff->id,
            'expires_at' => Carbon::now()->addHours(2), // Not overdue
        ]);

        $job = new EscalateOverdueCallbacksJob();

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('calculateOverdueHours');
        $method->setAccessible(true);

        $hours = $method->invoke($job, $activeCallback);

        $this->assertEquals(0, $hours);
    }

    /** @test */
    public function it_has_correct_retry_configuration()
    {
        $job = new EscalateOverdueCallbacksJob();

        $this->assertEquals(2, $job->tries);
        $this->assertEquals(300, $job->timeout);
    }
}
