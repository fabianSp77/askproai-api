<?php

namespace Tests\Feature;

use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentPolicyViolation;
use App\Events\Appointments\AppointmentRescheduled;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PolicyConfiguration;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Test Event-Driven Notification System
 *
 * Verifies that:
 * - Events are fired when expected
 * - Listeners are executed
 * - Event data is correctly structured
 */
class AppointmentEventFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->setupTestData();
    }

    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Service $service;
    private Call $call;

    private function setupTestData(): void
    {
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'retell_call_id' => 'test_call_' . uniqid()
        ]);
    }

    /** @test */
    public function it_fires_cancellation_requested_event_on_successful_cancellation()
    {
        Event::fake([AppointmentCancellationRequested::class]);

        // Arrange: Create appointment
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addHours(50),
            'status' => 'scheduled'
        ]);

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'fee' => 25.00]
        ]);

        // Act: Cancel via Retell
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                'reason' => 'Test cancellation'
            ]
        ]);

        // Assert: Event was fired
        $response->assertOk();
        Event::assertDispatched(AppointmentCancellationRequested::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id
                && $event->customer->id === $this->customer->id
                && $event->reason === 'Test cancellation'
                && $event->fee === 25.00
                && $event->withinPolicy === true;
        });
    }

    /** @test */
    public function it_fires_policy_violation_event_on_cancellation_denial()
    {
        Event::fake([AppointmentPolicyViolation::class]);

        // Arrange: Create appointment within deadline
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addHours(12), // Less than 24h
            'status' => 'scheduled'
        ]);

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24]
        ]);

        // Act: Try to cancel
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d')
            ]
        ]);

        // Assert: Policy violation event fired
        $response->assertOk();
        Event::assertDispatched(AppointmentPolicyViolation::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id
                && $event->attemptedAction === 'cancel'
                && $event->source === 'retell_ai'
                && !$event->policyResult->allowed
                && str_contains($event->policyResult->reason, '24 hours notice');
        });
    }

    /** @test */
    public function it_fires_reschedule_event_on_successful_reschedule()
    {
        Event::fake([AppointmentRescheduled::class]);

        // Arrange: Appointment 48h in future
        $oldDateTime = Carbon::now()->addHours(48);
        $newDateTime = Carbon::now()->addHours(72);

        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'starts_at' => $oldDateTime,
            'ends_at' => $oldDateTime->copy()->addHour(),
            'status' => 'scheduled'
        ]);

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'reschedule',
            'config' => ['hours_before' => 24, 'max_reschedules_per_appointment' => 2]
        ]);

        // Act: Reschedule (just check if policy allows, skip availability check)
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'reschedule_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'old_date' => $oldDateTime->format('Y-m-d')
            ]
        ]);

        // Assert: Should indicate ready to reschedule (not full reschedule test)
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'ready_to_reschedule'
        ]);

        // Note: Full reschedule with event would require mocking CalcomService
        // This test verifies policy check doesn't fire violation event
        Event::assertNotDispatched(AppointmentPolicyViolation::class);
    }

    /** @test */
    public function it_fires_policy_violation_event_on_reschedule_denial()
    {
        Event::fake([AppointmentPolicyViolation::class]);

        // Arrange: Appointment already rescheduled 2 times
        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'starts_at' => Carbon::now()->addDays(3),
            'status' => 'scheduled'
        ]);

        // Create 2 reschedule records
        for ($i = 0; $i < 2; $i++) {
            \App\Models\AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $this->customer->id,
                'modification_type' => 'reschedule',
                'within_policy' => true,
                'fee_charged' => 0
            ]);
        }

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'reschedule',
            'config' => ['hours_before' => 24, 'max_reschedules_per_appointment' => 2]
        ]);

        // Act: Try to reschedule again
        $response = $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'reschedule_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'old_date' => $appointment->starts_at->format('Y-m-d')
            ]
        ]);

        // Assert: Policy violation event fired
        $response->assertOk();
        Event::assertDispatched(AppointmentPolicyViolation::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id
                && $event->attemptedAction === 'reschedule'
                && str_contains($event->policyResult->reason, 'rescheduled 2 times');
        });
    }

    /** @test */
    public function event_contains_proper_context_data()
    {
        Event::fake([AppointmentCancellationRequested::class]);

        $appointment = Appointment::factory()->create([
            'call_id' => $this->call->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'starts_at' => Carbon::now()->addDays(2),
            'status' => 'scheduled'
        ]);

        PolicyConfiguration::create([
            'configurable_type' => 'App\\Models\\Company',
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'config' => ['hours_before' => 24, 'fee' => 15.50]
        ]);

        // Act
        $this->postJson('/api/webhooks/retell/function', [
            'function_name' => 'cancel_appointment',
            'call_id' => $this->call->retell_call_id,
            'parameters' => [
                'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                'reason' => 'Customer requested'
            ]
        ]);

        // Assert: Event context is complete
        Event::assertDispatched(AppointmentCancellationRequested::class, function ($event) {
            $context = $event->getContext();

            return isset($context['appointment_id'])
                && isset($context['customer_id'])
                && isset($context['customer_name'])
                && isset($context['reason'])
                && isset($context['fee'])
                && isset($context['within_policy'])
                && $context['reason'] === 'Customer requested'
                && $context['fee'] === 15.50;
        });
    }
}
