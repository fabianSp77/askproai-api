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
 * ASK-010: End-to-End Appointment Journey Tests
 *
 * Tests complete user journeys: Book → Reschedule → Cancel
 */
class AppointmentJourneyE2ETest extends TestCase
{
    use RefreshDatabase;

    /**
     * ASK-010: E2E Test - Customer books appointment via phone
     */
    public function test_e2e_customer_books_appointment_via_phone()
    {
        // Arrange: Customer exists in system
        $customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max@test.com',
            'phone' => '+49 123 456789',
        ]);

        $agent = User::factory()->create([
            'name' => 'Service Agent',
        ]);

        // Act: Customer calls and books appointment
        $response = $this->postJson('/api/retell/webhook', [
            'event' => 'call_started',
            'call_id' => 'call_' . uniqid(),
            'customer_phone' => '+49 123 456789',
            'agent_id' => $agent->id,
        ]);

        $callId = $response->json('call_id');

        // Book appointment during call
        $bookingResponse = $this->postJson('/api/appointments', [
            'customer_id' => $customer->id,
            'call_id' => $callId,
            'service_id' => 1,
            'scheduled_at' => Carbon::now()->addDays(3)->toDateTimeString(),
            'duration_minutes' => 60,
        ]);

        // Assert: Appointment created with complete metadata
        $bookingResponse->assertStatus(201);
        $appointmentId = $bookingResponse->json('appointment.id');

        $appointment = Appointment::find($appointmentId);
        $this->assertNotNull($appointment);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals('retell_agent', $appointment->created_by);
        $this->assertEquals('phone_call', $appointment->booking_source);
        $this->assertEquals('scheduled', $appointment->status);

        // Verify relationship integrity
        $this->assertEquals($customer->full_name, $appointment->customer->full_name);
        $this->assertNotNull($appointment->call);
    }

    /**
     * ASK-010: E2E Test - Complete journey: Book → Reschedule → Cancel
     */
    public function test_e2e_complete_appointment_lifecycle()
    {
        // Step 1: Initial booking via phone
        $customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);

        $call = Call::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $originalTime = Carbon::now()->addDays(3);
        $bookingResponse = $this->postJson('/api/appointments', [
            'customer_id' => $customer->id,
            'call_id' => $call->id,
            'service_id' => 1,
            'scheduled_at' => $originalTime->toDateTimeString(),
            'duration_minutes' => 60,
        ]);

        $bookingResponse->assertStatus(201);
        $appointmentId = $bookingResponse->json('appointment.id');

        // Step 2: Customer reschedules via portal
        $newTime = Carbon::now()->addDays(5);
        $rescheduleResponse = $this->putJson("/api/appointments/{$appointmentId}/reschedule", [
            'scheduled_at' => $newTime->toDateTimeString(),
            'reason' => 'Schedule conflict',
            'modified_by' => 'customer_portal',
        ]);

        $rescheduleResponse->assertStatus(200);

        // Verify reschedule metadata
        $appointment = Appointment::find($appointmentId);
        $this->assertEquals($newTime->toDateTimeString(), $appointment->scheduled_at->toDateTimeString());
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals('customer_portal', $appointment->rescheduled_by);

        // Verify modification record created
        $modification = AppointmentModification::where('appointment_id', $appointmentId)
            ->where('modification_type', 'reschedule')
            ->latest()
            ->first();

        $this->assertNotNull($modification);
        $this->assertEquals('customer_portal', $modification->modified_by);
        $this->assertEquals($originalTime->toDateTimeString(), $modification->previous_scheduled_at->toDateTimeString());

        // Step 3: Customer cancels appointment
        $cancelResponse = $this->deleteJson("/api/appointments/{$appointmentId}", [
            'reason' => 'No longer needed',
            'cancelled_by' => 'customer_portal',
        ]);

        $cancelResponse->assertStatus(200);

        // Verify cancellation metadata
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertEquals('customer_portal', $appointment->cancelled_by);

        // Verify cancellation modification record
        $cancelMod = AppointmentModification::where('appointment_id', $appointmentId)
            ->where('modification_type', 'cancellation')
            ->latest()
            ->first();

        $this->assertNotNull($cancelMod);
        $this->assertEquals('customer_portal', $cancelMod->modified_by);

        // Step 4: Verify complete audit trail
        $auditTrail = AppointmentModification::where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(2, $auditTrail);
        $this->assertEquals('reschedule', $auditTrail[0]->modification_type);
        $this->assertEquals('cancellation', $auditTrail[1]->modification_type);
    }

    /**
     * ASK-010: E2E Test - Multiple reschedules with name consistency
     */
    public function test_e2e_multiple_reschedules_maintain_name_consistency()
    {
        // Initial setup
        $customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);

        $call = Call::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'call_id' => $call->id,
            'scheduled_at' => Carbon::now()->addDays(2),
        ]);

        // Perform multiple reschedules
        $times = [
            Carbon::now()->addDays(3),
            Carbon::now()->addDays(5),
            Carbon::now()->addDays(7),
        ];

        foreach ($times as $newTime) {
            $this->putJson("/api/appointments/{$appointment->id}/reschedule", [
                'scheduled_at' => $newTime->toDateTimeString(),
                'modified_by' => 'customer_portal',
            ])->assertStatus(200);
        }

        // Verify name consistency throughout all modifications
        $appointment->refresh();
        $this->assertEquals('Max Mustermann', $appointment->customer->full_name);
        $this->assertEquals('Max Mustermann', $appointment->call->customer->full_name);

        // Verify all modification records reference correct customer
        $modifications = AppointmentModification::where('appointment_id', $appointment->id)->get();
        foreach ($modifications as $mod) {
            $appointment->refresh();
            $this->assertEquals($customer->id, $appointment->customer_id);
            $this->assertEquals('Max Mustermann', $appointment->customer->full_name);
        }
    }

    /**
     * ASK-010: E2E Test - Portal lookup verifies data consistency
     */
    public function test_e2e_portal_lookup_shows_consistent_data()
    {
        // Create appointment
        $customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'max@test.com',
        ]);

        $call = Call::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'call_id' => $call->id,
            'scheduled_at' => Carbon::now()->addDays(3),
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
        ]);

        // Customer looks up appointment via portal
        $lookupResponse = $this->getJson("/api/portal/appointments/lookup?email=max@test.com");

        $lookupResponse->assertStatus(200);
        $appointmentData = $lookupResponse->json('appointment');

        // Verify all data consistent
        $this->assertEquals('Max Mustermann', $appointmentData['customer_name']);
        $this->assertEquals($customer->email, $appointmentData['customer_email']);
        $this->assertEquals('retell_agent', $appointmentData['created_by']);
        $this->assertEquals('phone_call', $appointmentData['booking_source']);
        $this->assertNotNull($appointmentData['scheduled_at']);
    }

    /**
     * ASK-010: E2E Test - Retell agent workflow with metadata capture
     */
    public function test_e2e_retell_agent_workflow_captures_complete_metadata()
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'phone' => '+49 123 456789',
        ]);

        // Simulate Retell webhook call start
        $callStartResponse = $this->postJson('/api/retell/webhook', [
            'event' => 'call_started',
            'call_id' => 'retell_call_123',
            'from_number' => '+49 123 456789',
            'metadata' => [
                'retell_agent_id' => 'agent_456',
                'call_direction' => 'inbound',
            ],
        ]);

        $callStartResponse->assertStatus(200);
        $callId = $callStartResponse->json('call_id');

        // Agent books appointment during call
        $bookingResponse = $this->postJson('/api/retell/book-appointment', [
            'call_id' => $callId,
            'customer_phone' => '+49 123 456789',
            'scheduled_at' => Carbon::now()->addDays(3)->toDateTimeString(),
            'service_id' => 1,
            'duration_minutes' => 60,
        ]);

        $bookingResponse->assertStatus(201);
        $appointmentId = $bookingResponse->json('appointment.id');

        // Verify complete metadata captured
        $appointment = Appointment::find($appointmentId);
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->booking_source);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertNotNull($appointment->call_id);

        // Verify call metadata
        $call = Call::find($appointment->call_id);
        $this->assertNotNull($call->metadata);
        $this->assertArrayHasKey('retell_agent_id', $call->metadata);
    }
}
