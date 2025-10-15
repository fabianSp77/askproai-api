<?php

namespace Tests\Unit\Services\Appointments;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\AppointmentModification;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AppointmentModificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Call $call;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);

        $this->call = Call::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $this->appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'call_id' => $this->call->id,
            'scheduled_at' => Carbon::now()->addDays(2),
            'status' => 'scheduled',
            'created_by' => 'retell_agent',
            'booking_source' => 'phone_call',
        ]);
    }

    /**
     * ASK-010: Test reschedule metadata on both entities
     */
    public function test_reschedule_updates_both_appointment_and_creates_modification()
    {
        $originalScheduledAt = $this->appointment->scheduled_at;
        $newScheduledAt = Carbon::now()->addDays(5);

        // Reschedule appointment
        $this->appointment->update([
            'scheduled_at' => $newScheduledAt,
            'rescheduled_at' => now(),
            'rescheduled_by' => 'customer_portal',
        ]);

        // Create modification record
        $modification = AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $originalScheduledAt,
            'new_scheduled_at' => $newScheduledAt,
            'modified_by' => 'customer_portal',
            'reason' => 'Customer requested different time',
            'metadata' => [
                'previous_time' => $originalScheduledAt->toDateTimeString(),
                'new_time' => $newScheduledAt->toDateTimeString(),
            ],
        ]);

        // Verify appointment metadata
        $this->appointment->refresh();
        $this->assertEquals($newScheduledAt->toDateTimeString(), $this->appointment->scheduled_at->toDateTimeString());
        $this->assertNotNull($this->appointment->rescheduled_at);
        $this->assertEquals('customer_portal', $this->appointment->rescheduled_by);

        // Verify modification record
        $this->assertEquals('reschedule', $modification->modification_type);
        $this->assertEquals('customer_portal', $modification->modified_by);
        $this->assertNotNull($modification->metadata);
    }

    /**
     * ASK-010: Test cancellation metadata completeness
     */
    public function test_cancellation_creates_complete_audit_trail()
    {
        // Cancel appointment
        $this->appointment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 'retell_agent',
            'cancellation_reason' => 'Customer no longer needs service',
        ]);

        // Create modification record
        $modification = AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'cancellation',
            'modified_by' => 'retell_agent',
            'reason' => 'Customer no longer needs service',
            'metadata' => [
                'original_status' => 'scheduled',
                'cancelled_at' => now()->toDateTimeString(),
            ],
        ]);

        // Verify appointment cancellation metadata
        $this->appointment->refresh();
        $this->assertEquals('cancelled', $this->appointment->status);
        $this->assertNotNull($this->appointment->cancelled_at);
        $this->assertEquals('retell_agent', $this->appointment->cancelled_by);
        $this->assertNotNull($this->appointment->cancellation_reason);

        // Verify modification record
        $this->assertEquals('cancellation', $modification->modification_type);
        $this->assertEquals('retell_agent', $modification->modified_by);
        $this->assertArrayHasKey('cancelled_at', $modification->metadata);
    }

    /**
     * ASK-010: Test modification history ordering
     */
    public function test_modification_history_maintains_chronological_order()
    {
        $modifications = [];

        // First reschedule
        $modifications[] = AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $this->appointment->scheduled_at,
            'new_scheduled_at' => Carbon::now()->addDays(3),
            'modified_by' => 'customer_portal',
            'created_at' => now()->subDays(5),
        ]);

        sleep(1); // Ensure different timestamps

        // Second reschedule
        $modifications[] = AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => Carbon::now()->addDays(3),
            'new_scheduled_at' => Carbon::now()->addDays(4),
            'modified_by' => 'retell_agent',
            'created_at' => now()->subDays(2),
        ]);

        sleep(1);

        // Cancellation
        $modifications[] = AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'cancellation',
            'modified_by' => 'customer_portal',
            'created_at' => now(),
        ]);

        // Retrieve history in chronological order
        $history = AppointmentModification::where('appointment_id', $this->appointment->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(3, $history);
        $this->assertEquals('reschedule', $history[0]->modification_type);
        $this->assertEquals('reschedule', $history[1]->modification_type);
        $this->assertEquals('cancellation', $history[2]->modification_type);
    }

    /**
     * ASK-010: Test timeline reconstruction accuracy
     */
    public function test_can_reconstruct_complete_appointment_timeline()
    {
        $originalScheduledAt = $this->appointment->scheduled_at;

        // Create modification history
        $firstReschedule = Carbon::now()->addDays(3);
        AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $originalScheduledAt,
            'new_scheduled_at' => $firstReschedule,
            'modified_by' => 'customer_portal',
            'created_at' => now()->subDays(3),
        ]);

        $secondReschedule = Carbon::now()->addDays(5);
        AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $firstReschedule,
            'new_scheduled_at' => $secondReschedule,
            'modified_by' => 'retell_agent',
            'created_at' => now()->subDays(1),
        ]);

        // Reconstruct timeline
        $timeline = $this->appointment->modifications()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($mod) {
                return [
                    'type' => $mod->modification_type,
                    'when' => $mod->created_at,
                    'by' => $mod->modified_by,
                    'from' => $mod->previous_scheduled_at?->toDateTimeString(),
                    'to' => $mod->new_scheduled_at?->toDateTimeString(),
                ];
            });

        $this->assertCount(2, $timeline);
        $this->assertEquals($originalScheduledAt->toDateTimeString(), $timeline[0]['from']);
        $this->assertEquals($firstReschedule->toDateTimeString(), $timeline[0]['to']);
        $this->assertEquals($secondReschedule->toDateTimeString(), $timeline[1]['to']);
    }

    /**
     * ASK-010: Test metadata JSON structure completeness
     */
    public function test_modification_metadata_contains_all_required_fields()
    {
        $modification = AppointmentModification::create([
            'appointment_id' => $this->appointment->id,
            'modification_type' => 'reschedule',
            'previous_scheduled_at' => $this->appointment->scheduled_at,
            'new_scheduled_at' => Carbon::now()->addDays(5),
            'modified_by' => 'customer_portal',
            'reason' => 'Schedule conflict',
            'metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'previous_time' => $this->appointment->scheduled_at->toDateTimeString(),
                'new_time' => Carbon::now()->addDays(5)->toDateTimeString(),
                'notification_sent' => true,
                'confirmation_email_sent' => true,
            ],
        ]);

        $metadata = $modification->metadata;

        // Verify required fields present
        $this->assertArrayHasKey('ip_address', $metadata);
        $this->assertArrayHasKey('user_agent', $metadata);
        $this->assertArrayHasKey('previous_time', $metadata);
        $this->assertArrayHasKey('new_time', $metadata);
        $this->assertArrayHasKey('notification_sent', $metadata);
        $this->assertArrayHasKey('confirmation_email_sent', $metadata);

        // Verify data types
        $this->assertIsString($metadata['ip_address']);
        $this->assertIsBool($metadata['notification_sent']);
    }
}
