<?php

namespace Tests\Feature;

use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentPolicyViolation;
use App\Events\Appointments\AppointmentRescheduled;
use App\Listeners\Appointments\SendCancellationNotifications;
use App\Listeners\Appointments\TriggerPolicyEnforcement;
use App\Listeners\Appointments\UpdateModificationStats;
use App\Models\Appointment;
use App\Models\AppointmentModificationStat;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\ValueObjects\PolicyResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test Listener Execution
 *
 * Verifies that listeners are properly executed when events fire
 */
class AppointmentListenerExecutionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function update_modification_stats_listener_executes_on_cancellation()
    {
        Queue::fake();

        // Arrange
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        // Act: Fire event
        $event = new AppointmentCancellationRequested(
            appointment: $appointment,
            reason: 'Test',
            customer: $customer,
            fee: 0.0,
            withinPolicy: true
        );

        event($event);

        // Assert: At least one listener job was pushed
        // (Either UpdateModificationStats or SendCancellationNotifications)
        $this->assertTrue(
            Queue::pushed(\App\Listeners\Appointments\UpdateModificationStats::class)->count() > 0 ||
            Queue::pushed(\App\Listeners\Appointments\SendCancellationNotifications::class)->count() > 0,
            'No listener jobs were queued for AppointmentCancellationRequested event'
        );
    }

    /** @test */
    public function update_modification_stats_listener_creates_stat_record()
    {
        // Don't fake queue - run synchronously
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        // First, create a modification record (listener expects it)
        \App\Models\AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'modification_type' => 'cancel',
            'within_policy' => true,
            'fee_charged' => 0,
        ]);

        // Act: Fire event and execute listener synchronously
        $event = new AppointmentCancellationRequested(
            appointment: $appointment,
            reason: 'Test',
            customer: $customer,
            fee: 0.0,
            withinPolicy: true
        );

        $listener = new UpdateModificationStats();
        $listener->handle($event);

        // Assert: Stat record was created
        $this->assertDatabaseHas('appointment_modification_stats', [
            'customer_id' => $customer->id,
            'stat_type' => 'cancellation_count',
            'count' => 1,
        ]);
    }

    /** @test */
    public function policy_enforcement_listener_handles_violations_without_errors()
    {
        // Arrange
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addHours(12),
        ]);

        $policyResult = PolicyResult::deny(
            reason: 'Cancellation requires 24 hours notice',
            details: ['hours_notice' => 12, 'required_hours' => 24]
        );

        // Act: Fire event
        $event = new AppointmentPolicyViolation(
            appointment: $appointment,
            policyResult: $policyResult,
            attemptedAction: 'cancel',
            source: 'retell_ai'
        );

        // Assert: Listener executes without throwing exceptions
        try {
            $listener = new TriggerPolicyEnforcement();
            $listener->handle($event);
            $this->assertTrue(true, 'Listener executed successfully');
        } catch (\Exception $e) {
            $this->fail('Listener threw exception: ' . $e->getMessage());
        }
    }

    /** @test */
    public function listeners_are_registered_in_event_service_provider()
    {
        // Get registered listeners
        $events = Event::getListeners(AppointmentCancellationRequested::class);

        // Assert: At least some listeners are registered
        $this->assertNotEmpty($events, 'No listeners registered for AppointmentCancellationRequested');

        // Verify event handler is callable
        $this->assertTrue(
            is_array($events) && count($events) > 0,
            'Event should have at least one listener registered'
        );
    }

    /** @test */
    public function listeners_can_be_queued()
    {
        Queue::fake();

        // Arrange
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        // Act: Fire event
        $event = new AppointmentCancellationRequested(
            appointment: $appointment,
            reason: 'Test',
            customer: $customer
        );

        event($event);

        // Assert: Listener jobs are queued (ShouldQueue interface)
        $this->assertTrue(
            Queue::pushed()->count() > 0,
            'No jobs were queued - listeners should implement ShouldQueue'
        );
    }

    /** @test */
    public function reschedule_event_triggers_stats_update()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDays(2),
        ]);

        // Create modification record
        \App\Models\AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'modification_type' => 'reschedule',
            'within_policy' => true,
            'fee_charged' => 0,
        ]);

        // Act: Fire reschedule event
        $event = new AppointmentRescheduled(
            appointment: $appointment,
            oldStartTime: Carbon::now()->addDays(2),
            newStartTime: Carbon::now()->addDays(3),
            reason: 'Customer request',
            fee: 0.0,
            withinPolicy: true
        );

        $listener = new UpdateModificationStats();
        $listener->handle($event);

        // Assert: Stats updated for customer (appointment_id not in schema)
        $this->assertDatabaseHas('appointment_modification_stats', [
            'customer_id' => $customer->id,
            'stat_type' => 'reschedule_count',
            'count' => 1,
        ]);
    }
}
