<?php

namespace Tests\Unit\Services\Appointments;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AppointmentCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $service;
    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AppointmentCreationService::class);

        $this->user = User::factory()->create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com',
        ]);

        $this->customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max.mustermann@test.com',
        ]);
    }

    /**
     * ASK-010: Test metadata completeness on appointment creation
     */
    public function test_creates_appointment_with_complete_metadata()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'agent_id' => $this->user->id,
        ]);

        $appointmentData = [
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'service_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(2),
            'duration_minutes' => 60,
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
        ];

        $appointment = $this->service->createAppointment($appointmentData);

        $this->assertNotNull($appointment->id);
        $this->assertEquals('retell_agent', $appointment->created_by);
        $this->assertEquals('phone_call', $appointment->booking_source);
        $this->assertEquals($this->customer->id, $appointment->customer_id);
        $this->assertEquals($call->id, $appointment->call_id);
        $this->assertNotNull($appointment->created_at);
    }

    /**
     * ASK-010: Test name consistency between customer and appointment
     */
    public function test_appointment_references_correct_customer_name()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
        ]);

        // Reload relationships
        $appointment->load('customer', 'call.customer');

        // Verify all names match the same customer
        $this->assertEquals('Max Mustermann', $appointment->customer->full_name);
        $this->assertEquals($this->customer->id, $appointment->customer_id);
        $this->assertEquals($this->customer->id, $call->customer_id);
        $this->assertEquals($appointment->customer_id, $call->customer_id);
    }

    /**
     * ASK-010: Test default values when metadata not provided
     */
    public function test_applies_default_metadata_when_not_provided()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $appointmentData = [
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'service_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(2),
            'duration_minutes' => 60,
            // No created_by or booking_source provided
        ];

        $appointment = $this->service->createAppointment($appointmentData);

        // Should have system defaults
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->booking_source);
    }

    /**
     * ASK-010: Test relationship integrity on creation
     */
    public function test_maintains_relationship_integrity_on_creation()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'created_by' => 'test_system',
            'booking_source' => 'api',
        ]);

        // Verify bidirectional relationships
        $this->assertTrue($this->customer->appointments->contains($appointment));
        $this->assertTrue($call->appointments->contains($appointment));

        // Verify relationship IDs
        $this->assertEquals($appointment->customer_id, $call->customer_id);
        $this->assertNotNull($appointment->call);
        $this->assertNotNull($appointment->customer);
    }

    /**
     * ASK-010: Test audit trail creation
     */
    public function test_creates_audit_trail_on_appointment_creation()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
        ]);

        // Verify audit fields
        $this->assertNotNull($appointment->created_at);
        $this->assertNotNull($appointment->updated_at);
        $this->assertEquals('retell_agent', $appointment->created_by);

        // If using Laravel Auditing
        if (method_exists($appointment, 'audits')) {
            $this->assertGreaterThan(0, $appointment->audits()->count());
        }
    }
}
