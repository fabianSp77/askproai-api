<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Services\ProcessingTimeAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ProcessingTimeAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProcessingTimeAvailabilityService $service;
    protected Company $company;
    protected Staff $staff;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProcessingTimeAvailabilityService();

        // Create test data bypassing observers
        $companyId = \DB::table('companies')->insertGetId([
            'name' => 'Test Company',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $staffId = \Illuminate\Support\Str::uuid()->toString();
        \DB::table('staff')->insert([
            'id' => $staffId,
            'company_id' => $companyId,
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = \DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->company = Company::find($companyId);
        $this->staff = Staff::find($staffId);
        $this->customer = Customer::find($customerId);
    }

    protected function createService(array $attributes = []): Service
    {
        $serviceId = \DB::table('services')->insertGetId(array_merge([
            'company_id' => $this->company->id,
            'name' => 'Test Service',
            'duration_minutes' => 60,
            'price' => 100.00,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Service::find($serviceId);
    }

    protected function createAppointment(Service $service, Carbon $startTime): Appointment
    {
        $appointmentId = \DB::table('appointments')->insertGetId([
            'company_id' => $this->company->id,
            'service_id' => $service->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes($service->getTotalDuration()),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Appointment::find($appointmentId);
    }

    protected function createPhases(Appointment $appointment, Service $service): void
    {
        $phases = $service->generatePhases($appointment->starts_at);

        foreach ($phases as $phase) {
            AppointmentPhase::create(array_merge($phase, [
                'appointment_id' => $appointment->id,
            ]));
        }
    }

    /** @test */
    public function staff_is_available_when_no_appointments_exist()
    {
        $service = $this->createService();
        $startTime = Carbon::parse('2025-10-28 10:00:00');

        $available = $this->service->isStaffAvailable($this->staff->id, $startTime, $service);

        $this->assertTrue($available);
    }

    /** @test */
    public function staff_is_not_available_for_regular_service_when_overlapping()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => false,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);

        // Try to book at 10:30 (overlaps with existing 10:00-11:00)
        $newStart = Carbon::parse('2025-10-28 10:30:00');
        $available = $this->service->isStaffAvailable($this->staff->id, $newStart, $service);

        $this->assertFalse($available);
    }

    /** @test */
    public function staff_is_available_for_regular_service_when_not_overlapping()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => false,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);

        // Try to book at 11:00 (after existing 10:00-11:00)
        $newStart = Carbon::parse('2025-10-28 11:00:00');
        $available = $this->service->isStaffAvailable($this->staff->id, $newStart, $service);

        $this->assertTrue($available);
    }

    /** @test */
    public function staff_is_available_during_processing_phase_of_another_appointment()
    {
        // Create processing time service
        $service1 = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        // Create appointment at 10:00
        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service1, $existingStart);
        $this->createPhases($appointment, $service1);

        // Create short service (15 min)
        $service2 = $this->createService([
            'duration_minutes' => 15,
            'has_processing_time' => false,
        ]);

        // Try to book at 10:15 (during processing phase 10:15-10:45)
        $newStart = Carbon::parse('2025-10-28 10:15:00');
        $available = $this->service->isStaffAvailable($this->staff->id, $newStart, $service2);

        $this->assertTrue($available, 'Staff should be available during processing phase');
    }

    /** @test */
    public function staff_is_not_available_during_busy_phase()
    {
        // Create processing time service
        $service1 = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        // Create appointment at 10:00
        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service1, $existingStart);
        $this->createPhases($appointment, $service1);

        // Create short service (15 min)
        $service2 = $this->createService([
            'duration_minutes' => 15,
            'has_processing_time' => false,
        ]);

        // Try to book at 10:05 (during initial busy phase 10:00-10:15)
        $newStart = Carbon::parse('2025-10-28 10:05:00');
        $available = $this->service->isStaffAvailable($this->staff->id, $newStart, $service2);

        $this->assertFalse($available, 'Staff should NOT be available during busy phase');
    }

    /** @test */
    public function has_overlapping_busy_phases_returns_true_when_overlap_exists()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);
        $this->createPhases($appointment, $service);

        // Check overlap with initial phase (10:00-10:15)
        $start = Carbon::parse('2025-10-28 10:05:00');
        $end = Carbon::parse('2025-10-28 10:20:00');

        $hasOverlap = $this->service->hasOverlappingBusyPhases($this->staff->id, $start, $end);

        $this->assertTrue($hasOverlap);
    }

    /** @test */
    public function has_overlapping_busy_phases_returns_false_during_processing_phase()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);
        $this->createPhases($appointment, $service);

        // Check during processing phase (10:15-10:45)
        $start = Carbon::parse('2025-10-28 10:20:00');
        $end = Carbon::parse('2025-10-28 10:30:00');

        $hasOverlap = $this->service->hasOverlappingBusyPhases($this->staff->id, $start, $end);

        $this->assertFalse($hasOverlap, 'Should not have overlap during processing phase');
    }

    /** @test */
    public function get_staff_busy_phases_returns_only_busy_phases()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);
        $this->createPhases($appointment, $service);

        $start = Carbon::parse('2025-10-28 09:00:00');
        $end = Carbon::parse('2025-10-28 12:00:00');

        $busyPhases = $this->service->getStaffBusyPhases($this->staff->id, $start, $end);

        // Should have 2 busy phases (initial + final)
        $this->assertCount(2, $busyPhases);
        $this->assertTrue($busyPhases[0]->staff_required);
        $this->assertTrue($busyPhases[1]->staff_required);
    }

    /** @test */
    public function get_staff_available_phases_returns_only_processing_phases()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);
        $this->createPhases($appointment, $service);

        $start = Carbon::parse('2025-10-28 09:00:00');
        $end = Carbon::parse('2025-10-28 12:00:00');

        $availablePhases = $this->service->getStaffAvailablePhases($this->staff->id, $start, $end);

        // Should have 1 processing phase
        $this->assertCount(1, $availablePhases);
        $this->assertFalse($availablePhases[0]->staff_required);
        $this->assertEquals('processing', $availablePhases[0]->phase_type);
    }

    /** @test */
    public function find_available_slots_returns_correct_slots()
    {
        $service = $this->createService([
            'duration_minutes' => 30,
            'has_processing_time' => false,
        ]);

        $date = Carbon::parse('2025-10-28');
        $slots = $this->service->findAvailableSlots($this->staff->id, $date, $service, 30);

        $this->assertNotEmpty($slots);
        $this->assertTrue($slots[0]['available']);
        $this->assertEquals(30, $slots[0]['start']->diffInMinutes($slots[0]['end']));
    }

    /** @test */
    public function find_available_slots_marks_busy_slots_as_unavailable()
    {
        $service = $this->createService([
            'duration_minutes' => 30,
            'has_processing_time' => false,
        ]);

        // Create appointment at 10:00
        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);

        $date = Carbon::parse('2025-10-28');
        $slots = $this->service->findAvailableSlots($this->staff->id, $date, $service, 30);

        // Find slot at 10:00
        $slot10am = collect($slots)->first(fn($slot) =>
            $slot['start']->format('H:i') === '10:00'
        );

        $this->assertFalse($slot10am['available'], 'Slot at 10:00 should be unavailable');
    }

    /** @test */
    public function calculate_staff_utilization_returns_correct_metrics()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);
        $this->createPhases($appointment, $service);

        $start = Carbon::parse('2025-10-28 10:00:00');
        $end = Carbon::parse('2025-10-28 11:00:00');

        $utilization = $this->service->calculateStaffUtilization($this->staff->id, $start, $end);

        $this->assertEquals(30, $utilization['busy_minutes']); // 15 + 15
        $this->assertEquals(30, $utilization['available_minutes']); // 30
        $this->assertEquals(60, $utilization['total_minutes']);
        $this->assertEquals(50.0, $utilization['utilization_rate']); // 30/60 = 50%
    }

    /** @test */
    public function get_availability_breakdown_returns_complete_structure()
    {
        $service = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $existingStart = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $existingStart);
        $this->createPhases($appointment, $service);

        $start = Carbon::parse('2025-10-28 09:00:00');
        $end = Carbon::parse('2025-10-28 12:00:00');

        $breakdown = $this->service->getAvailabilityBreakdown($this->staff->id, $start, $end);

        $this->assertEquals($this->staff->id, $breakdown['staff_id']);
        $this->assertArrayHasKey('busy_phases', $breakdown);
        $this->assertArrayHasKey('available_phases', $breakdown);
        $this->assertArrayHasKey('utilization', $breakdown);
        $this->assertCount(2, $breakdown['busy_phases']);
        $this->assertCount(1, $breakdown['available_phases']);
    }

    /** @test */
    public function can_interleave_appointments_returns_true_when_compatible()
    {
        $service1 = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $service2 = $this->createService([
            'duration_minutes' => 15,
            'has_processing_time' => false,
        ]);

        $appt1Start = Carbon::parse('2025-10-28 10:00:00');
        $appt2Start = Carbon::parse('2025-10-28 10:15:00'); // During processing phase

        $canInterleave = $this->service->canInterleaveAppointments(
            $appt1Start,
            $service1,
            $appt2Start,
            $service2
        );

        $this->assertTrue($canInterleave);
    }

    /** @test */
    public function can_interleave_appointments_returns_false_when_busy_phases_overlap()
    {
        $service1 = $this->createService([
            'duration_minutes' => 60,
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $service2 = $this->createService([
            'duration_minutes' => 15,
            'has_processing_time' => false,
        ]);

        $appt1Start = Carbon::parse('2025-10-28 10:00:00');
        $appt2Start = Carbon::parse('2025-10-28 10:05:00'); // During initial busy phase

        $canInterleave = $this->service->canInterleaveAppointments(
            $appt1Start,
            $service1,
            $appt2Start,
            $service2
        );

        $this->assertFalse($canInterleave);
    }
}
