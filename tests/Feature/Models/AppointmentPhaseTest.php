<?php

namespace Tests\Feature\Models;

use App\Models\AppointmentPhase;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Service;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AppointmentPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Service $service;
    protected Customer $customer;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal test data bypassing observers
        $companyId = \DB::table('companies')->insertGetId([
            'name' => 'Test Company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = \DB::table('services')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Test Service',
            'duration_minutes' => 60,
            'price' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = \DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = \DB::table('appointments')->insertGetId([
            'company_id' => $companyId,
            'service_id' => $serviceId,
            'customer_id' => $customerId,
            'starts_at' => now(),
            'ends_at' => now()->addMinutes(60),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Load as models for tests
        $this->company = Company::find($companyId);
        $this->service = Service::find($serviceId);
        $this->customer = Customer::find($customerId);
        $this->appointment = Appointment::find($appointmentId);
    }

    /** @test */
    public function it_can_create_appointment_phase()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertDatabaseHas('appointment_phases', [
            'id' => $phase->id,
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
        ]);
    }

    /** @test */
    public function it_belongs_to_appointment()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertInstanceOf(Appointment::class, $phase->appointment);
        $this->assertEquals($this->appointment->id, $phase->appointment->id);
    }

    /** @test */
    public function it_casts_staff_required_to_boolean()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => 0, // Integer
            'start_time' => now(),
            'end_time' => now()->addMinutes(30),
        ]);

        $this->assertIsBool($phase->staff_required);
        $this->assertFalse($phase->staff_required);
    }

    /** @test */
    public function it_casts_timestamps_to_carbon()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'final',
            'start_offset_minutes' => 45,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertInstanceOf(Carbon::class, $phase->start_time);
        $this->assertInstanceOf(Carbon::class, $phase->end_time);
    }

    /** @test */
    public function scope_staff_required_filters_busy_phases()
    {
        // Create busy phase
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        // Create available phase
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now()->addMinutes(15),
            'end_time' => now()->addMinutes(45),
        ]);

        $busyPhases = AppointmentPhase::staffRequired()->get();

        $this->assertCount(1, $busyPhases);
        $this->assertTrue($busyPhases->first()->staff_required);
    }

    /** @test */
    public function scope_staff_available_filters_processing_phases()
    {
        // Create busy phase
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        // Create available phase
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now()->addMinutes(15),
            'end_time' => now()->addMinutes(45),
        ]);

        $availablePhases = AppointmentPhase::staffAvailable()->get();

        $this->assertCount(1, $availablePhases);
        $this->assertFalse($availablePhases->first()->staff_required);
    }

    /** @test */
    public function scope_in_time_range_finds_overlapping_phases()
    {
        $startTime = Carbon::parse('2025-10-28 10:00:00');

        // Phase 1: 10:00 - 10:15 (within range)
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes(15),
        ]);

        // Phase 2: 10:15 - 10:45 (overlaps range)
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => $startTime->copy()->addMinutes(15),
            'end_time' => $startTime->copy()->addMinutes(45),
        ]);

        // Phase 3: 11:00 - 11:15 (outside range)
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'final',
            'start_offset_minutes' => 60,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => $startTime->copy()->addMinutes(60),
            'end_time' => $startTime->copy()->addMinutes(75),
        ]);

        $rangeStart = $startTime->copy();
        $rangeEnd = $startTime->copy()->addMinutes(30);

        $phasesInRange = AppointmentPhase::inTimeRange($rangeStart, $rangeEnd)->get();

        $this->assertCount(2, $phasesInRange);
    }

    /** @test */
    public function scope_of_type_filters_by_phase_type()
    {
        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now()->addMinutes(15),
            'end_time' => now()->addMinutes(45),
        ]);

        $initialPhases = AppointmentPhase::ofType('initial')->get();

        $this->assertCount(1, $initialPhases);
        $this->assertEquals('initial', $initialPhases->first()->phase_type);
    }

    /** @test */
    public function is_initial_returns_true_for_initial_phase()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertTrue($phase->isInitial());
        $this->assertFalse($phase->isProcessing());
        $this->assertFalse($phase->isFinal());
    }

    /** @test */
    public function is_processing_returns_true_for_processing_phase()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now(),
            'end_time' => now()->addMinutes(30),
        ]);

        $this->assertFalse($phase->isInitial());
        $this->assertTrue($phase->isProcessing());
        $this->assertFalse($phase->isFinal());
    }

    /** @test */
    public function is_final_returns_true_for_final_phase()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'final',
            'start_offset_minutes' => 45,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertFalse($phase->isInitial());
        $this->assertFalse($phase->isProcessing());
        $this->assertTrue($phase->isFinal());
    }

    /** @test */
    public function is_staff_busy_returns_correct_value()
    {
        $busyPhase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertTrue($busyPhase->isStaffBusy());
        $this->assertFalse($busyPhase->isStaffAvailable());
    }

    /** @test */
    public function is_staff_available_returns_correct_value()
    {
        $availablePhase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now(),
            'end_time' => now()->addMinutes(30),
        ]);

        $this->assertTrue($availablePhase->isStaffAvailable());
        $this->assertFalse($availablePhase->isStaffBusy());
    }

    /** @test */
    public function get_duration_returns_duration_in_minutes()
    {
        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now(),
            'end_time' => now()->addMinutes(30),
        ]);

        $this->assertEquals(30, $phase->getDuration());
    }

    /** @test */
    public function overlaps_detects_time_range_overlap()
    {
        $startTime = Carbon::parse('2025-10-28 10:00:00');

        $phase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 30,
            'staff_required' => true,
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes(30),
        ]);

        // Overlapping range
        $this->assertTrue($phase->overlaps(
            $startTime->copy()->addMinutes(15),
            $startTime->copy()->addMinutes(45)
        ));

        // Non-overlapping range (after)
        $this->assertFalse($phase->overlaps(
            $startTime->copy()->addMinutes(40),
            $startTime->copy()->addMinutes(60)
        ));

        // Non-overlapping range (before)
        $this->assertFalse($phase->overlaps(
            $startTime->copy()->subMinutes(30),
            $startTime->copy()->subMinutes(10)
        ));
    }

    /** @test */
    public function get_phase_type_label_returns_human_readable_label()
    {
        $initialPhase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'initial',
            'start_offset_minutes' => 0,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $processingPhase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'processing',
            'start_offset_minutes' => 15,
            'duration_minutes' => 30,
            'staff_required' => false,
            'start_time' => now(),
            'end_time' => now()->addMinutes(30),
        ]);

        $finalPhase = AppointmentPhase::create([
            'appointment_id' => $this->appointment->id,
            'phase_type' => 'final',
            'start_offset_minutes' => 45,
            'duration_minutes' => 15,
            'staff_required' => true,
            'start_time' => now(),
            'end_time' => now()->addMinutes(15),
        ]);

        $this->assertEquals('Initial Phase (Staff Busy)', $initialPhase->getPhaseTypeLabel());
        $this->assertEquals('Processing Phase (Staff Available)', $processingPhase->getPhaseTypeLabel());
        $this->assertEquals('Final Phase (Staff Busy)', $finalPhase->getPhaseTypeLabel());
    }
}
