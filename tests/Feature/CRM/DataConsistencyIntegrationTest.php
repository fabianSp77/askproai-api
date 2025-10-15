<?php

namespace Tests\Feature\CRM;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\AppointmentModification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ASK-010: CRM Data Consistency Integration Tests
 *
 * Tests complete data flow: Customer → Call → Appointment → Modification
 */
class DataConsistencyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = User::factory()->create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com',
        ]);

        $this->customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max.mustermann@test.com',
            'phone' => '+49 123 456789',
        ]);
    }

    /**
     * ASK-010: Test complete booking flow data consistency
     */
    public function test_complete_booking_flow_maintains_data_consistency()
    {
        // Step 1: Customer calls
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'call_type' => 'inbound',
            'started_at' => now(),
            'metadata' => [
                'retell_call_id' => 'call_' . uniqid(),
                'duration_seconds' => 320,
            ],
        ]);

        // Step 2: Appointment booked during call
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'service_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(3),
            'duration_minutes' => 60,
            'status' => 'scheduled',
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
        ]);

        // Verify complete relationship chain
        $this->assertEquals($this->customer->id, $call->customer_id);
        $this->assertEquals($this->customer->id, $appointment->customer_id);
        $this->assertEquals($call->id, $appointment->call_id);

        // Verify metadata completeness
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->booking_source);
        $this->assertNotNull($call->metadata);

        // Verify name consistency across entities
        $this->assertEquals('Max Mustermann', $this->customer->full_name);
        $this->assertEquals($this->customer->full_name, $appointment->customer->full_name);
        $this->assertEquals($this->customer->full_name, $call->customer->full_name);
    }

    /**
     * ASK-010: Test name consistency throughout relationship chain
     */
    public function test_name_consistency_across_all_entities()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
        ]);

        // Load all relationships
        $customerFromCall = $call->customer;
        $customerFromAppointment = $appointment->customer;
        $customerFromCallThroughAppointment = $appointment->call->customer;

        // All should reference the same customer
        $this->assertEquals($this->customer->id, $customerFromCall->id);
        $this->assertEquals($this->customer->id, $customerFromAppointment->id);
        $this->assertEquals($this->customer->id, $customerFromCallThroughAppointment->id);

        // All names should match
        $expectedFullName = 'Max Mustermann';
        $this->assertEquals($expectedFullName, $customerFromCall->full_name);
        $this->assertEquals($expectedFullName, $customerFromAppointment->full_name);
        $this->assertEquals($expectedFullName, $customerFromCallThroughAppointment->full_name);
    }

    /**
     * ASK-010: Test complete reschedule flow with metadata preservation
     */
    public function test_reschedule_flow_preserves_complete_metadata()
    {
        // Initial booking
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
        ]);

        $originalScheduledAt = Carbon::now()->addDays(3);
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'scheduled_at' => $originalScheduledAt,
            'status' => 'scheduled',
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
        ]);

        // Customer reschedules via portal
        $newScheduledAt = Carbon::now()->addDays(5);
        $appointment->update([
            'scheduled_at' => $newScheduledAt,
            'rescheduled_at' => now(),
            'rescheduled_by' => 'customer_portal',
        ]);

        // Create modification record
        $modification = AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $originalScheduledAt,
            'new_scheduled_at' => $newScheduledAt,
            'modified_by' => 'customer_portal',
            'reason' => 'Customer requested different time',
            'metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Chrome/120.0',
                'notification_sent' => true,
            ],
        ]);

        // Verify appointment metadata
        $appointment->refresh();
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals('customer_portal', $appointment->rescheduled_by);
        $this->assertEquals($newScheduledAt->toDateTimeString(), $appointment->scheduled_at->toDateTimeString());

        // Verify modification metadata
        $this->assertEquals('reschedule', $modification->modification_type);
        $this->assertEquals('customer_portal', $modification->modified_by);
        $this->assertNotNull($modification->metadata);
        $this->assertArrayHasKey('ip_address', $modification->metadata);

        // Verify original booking metadata still intact
        $this->assertEquals('retell_agent', $appointment->created_by);
        $this->assertEquals('phone_call', $appointment->booking_source);
    }

    /**
     * ASK-010: Test audit trail completeness for multi-step journey
     */
    public function test_complete_audit_trail_for_complex_journey()
    {
        // Step 1: Initial booking
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'started_at' => now()->subDays(7),
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'scheduled_at' => Carbon::now()->addDays(3),
            'status' => 'scheduled',
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
            'created_at' => now()->subDays(7),
        ]);

        // Step 2: First reschedule
        $firstReschedule = Carbon::now()->addDays(5);
        AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $appointment->scheduled_at,
            'new_scheduled_at' => $firstReschedule,
            'modified_by' => 'customer_portal',
            'reason' => 'Schedule conflict',
            'created_at' => now()->subDays(5),
        ]);

        // Step 3: Second reschedule
        $secondReschedule = Carbon::now()->addDays(7);
        AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $firstReschedule,
            'new_scheduled_at' => $secondReschedule,
            'modified_by' => 'retell_agent',
            'reason' => 'Provider availability',
            'created_at' => now()->subDays(2),
        ]);

        // Step 4: Cancellation
        AppointmentModification::create([
            'appointment_id' => $appointment->id,
            'modification_type' => 'cancellation',
            'modified_by' => 'customer_portal',
            'reason' => 'No longer needed',
            'created_at' => now(),
        ]);

        // Verify complete audit trail
        $auditTrail = AppointmentModification::where('appointment_id', $appointment->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(3, $auditTrail);

        // Verify chronological order
        $this->assertEquals('reschedule', $auditTrail[0]->modification_type);
        $this->assertEquals('customer_portal', $auditTrail[0]->modified_by);

        $this->assertEquals('reschedule', $auditTrail[1]->modification_type);
        $this->assertEquals('retell_agent', $auditTrail[1]->modified_by);

        $this->assertEquals('cancellation', $auditTrail[2]->modification_type);
        $this->assertEquals('customer_portal', $auditTrail[2]->modified_by);

        // Verify all modifications reference same appointment
        foreach ($auditTrail as $mod) {
            $this->assertEquals($appointment->id, $mod->appointment_id);
        }
    }

    /**
     * ASK-010: Test relationship cascade and integrity constraints
     */
    public function test_relationship_integrity_constraints()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
        ]);

        // Verify cannot orphan appointment (customer_id required)
        $this->expectException(\Illuminate\Database\QueryException::class);
        $appointment->update(['customer_id' => null]);
    }

    /**
     * ASK-010: Test metadata completeness validation
     */
    public function test_validates_required_metadata_fields_present()
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

        // Verify required metadata fields
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->booking_source);
        $this->assertNotNull($appointment->created_at);
        $this->assertNotNull($appointment->updated_at);

        // Verify relationship IDs present
        $this->assertNotNull($appointment->customer_id);
        $this->assertNotNull($appointment->call_id);
    }

    /**
     * ASK-010: Test timeline reconstruction with multiple modifications
     */
    public function test_timeline_reconstruction_accuracy()
    {
        $call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'started_at' => now()->subDays(10),
        ]);

        $originalTime = Carbon::now()->addDays(2);
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
            'scheduled_at' => $originalTime,
            'created_at' => now()->subDays(10),
        ]);

        // Create modification timeline
        $timeline = [
            ['time' => $originalTime->copy()->addDays(1), 'when' => now()->subDays(8)],
            ['time' => $originalTime->copy()->addDays(2), 'when' => now()->subDays(5)],
            ['time' => $originalTime->copy()->addDays(4), 'when' => now()->subDays(2)],
        ];

        $previousTime = $originalTime;
        foreach ($timeline as $event) {
            AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'modification_type' => 'reschedule',
                'previous_scheduled_at' => $previousTime,
                'new_scheduled_at' => $event['time'],
                'modified_by' => 'system',
                'created_at' => $event['when'],
            ]);
            $previousTime = $event['time'];
        }

        // Reconstruct complete timeline
        $reconstructed = AppointmentModification::where('appointment_id', $appointment->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(3, $reconstructed);

        // Verify timeline continuity
        $this->assertEquals(
            $originalTime->toDateTimeString(),
            $reconstructed[0]->previous_scheduled_at->toDateTimeString()
        );

        $this->assertEquals(
            $reconstructed[0]->new_scheduled_at->toDateTimeString(),
            $reconstructed[1]->previous_scheduled_at->toDateTimeString()
        );

        $this->assertEquals(
            $reconstructed[1]->new_scheduled_at->toDateTimeString(),
            $reconstructed[2]->previous_scheduled_at->toDateTimeString()
        );
    }
}
