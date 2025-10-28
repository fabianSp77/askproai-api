<?php

namespace Tests\Feature\Services;

use App\Models\Company;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Services\AppointmentPhaseCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AppointmentPhaseCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentPhaseCreationService $service;
    protected Company $company;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AppointmentPhaseCreationService();

        // Create test data bypassing observers
        $companyId = \DB::table('companies')->insertGetId([
            'name' => 'Test Company',
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
            'starts_at' => $startTime,
            'ends_at' => $startTime->copy()->addMinutes($service->getTotalDuration()),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment = Appointment::find($appointmentId);
        $appointment->load('service');

        return $appointment;
    }

    /** @test */
    public function it_creates_phases_for_appointment_with_processing_time()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));

        $phases = $this->service->createPhasesForAppointment($appointment);

        $this->assertCount(3, $phases);
        $this->assertInstanceOf(AppointmentPhase::class, $phases[0]);

        // Verify in database
        $this->assertDatabaseCount('appointment_phases', 3);
    }

    /** @test */
    public function it_returns_empty_array_for_regular_service()
    {
        $service = $this->createService([
            'has_processing_time' => false,
            'duration_minutes' => 60,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));

        $phases = $this->service->createPhasesForAppointment($appointment);

        $this->assertEmpty($phases);
        $this->assertDatabaseCount('appointment_phases', 0);
    }

    /** @test */
    public function it_creates_correct_phase_data()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $startTime = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $startTime);

        $phases = $this->service->createPhasesForAppointment($appointment);

        // Check initial phase
        $this->assertEquals('initial', $phases[0]->phase_type);
        $this->assertTrue($phases[0]->staff_required);
        $this->assertEquals(0, $phases[0]->start_offset_minutes);
        $this->assertEquals(15, $phases[0]->duration_minutes);

        // Check processing phase
        $this->assertEquals('processing', $phases[1]->phase_type);
        $this->assertFalse($phases[1]->staff_required);
        $this->assertEquals(15, $phases[1]->start_offset_minutes);
        $this->assertEquals(30, $phases[1]->duration_minutes);

        // Check final phase
        $this->assertEquals('final', $phases[2]->phase_type);
        $this->assertTrue($phases[2]->staff_required);
        $this->assertEquals(45, $phases[2]->start_offset_minutes);
        $this->assertEquals(15, $phases[2]->duration_minutes);
    }

    /** @test */
    public function it_updates_phases_for_rescheduled_appointment()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $originalTime = Carbon::parse('2025-10-28 10:00:00');
        $appointment = $this->createAppointment($service, $originalTime);

        // Create initial phases
        $this->service->createPhasesForAppointment($appointment);
        $this->assertDatabaseCount('appointment_phases', 3);

        // Update appointment time
        $newTime = Carbon::parse('2025-10-28 14:00:00');
        $appointment->starts_at = $newTime;
        $appointment->ends_at = $newTime->copy()->addMinutes(60);
        $appointment->save();

        // Update phases
        $newPhases = $this->service->updatePhasesForRescheduledAppointment($appointment);

        $this->assertCount(3, $newPhases);
        $this->assertDatabaseCount('appointment_phases', 3);

        // Verify new times
        $firstPhase = AppointmentPhase::where('appointment_id', $appointment->id)
            ->where('phase_type', 'initial')
            ->first();

        $this->assertEquals('2025-10-28 14:00:00', $firstPhase->start_time->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_deletes_phases()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));
        $this->service->createPhasesForAppointment($appointment);

        $this->assertDatabaseCount('appointment_phases', 3);

        $deletedCount = $this->service->deletePhases($appointment);

        $this->assertEquals(3, $deletedCount);
        $this->assertDatabaseCount('appointment_phases', 0);
    }

    /** @test */
    public function it_recreates_phases_if_needed_when_service_has_processing_time()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));

        // No phases exist yet
        $phases = $this->service->recreatePhasesIfNeeded($appointment);

        $this->assertCount(3, $phases);
        $this->assertDatabaseCount('appointment_phases', 3);
    }

    /** @test */
    public function it_deletes_phases_if_service_no_longer_has_processing_time()
    {
        // Start with processing time service
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));
        $this->service->createPhasesForAppointment($appointment);
        $this->assertDatabaseCount('appointment_phases', 3);

        // Change service to regular (no processing time)
        \DB::table('services')
            ->where('id', $service->id)
            ->update(['has_processing_time' => false]);

        $service = Service::find($service->id); // Reload
        $appointment->service = $service; // Update relationship

        // Recreate phases
        $phases = $this->service->recreatePhasesIfNeeded($appointment);

        $this->assertEmpty($phases);
        $this->assertDatabaseCount('appointment_phases', 0);
    }

    /** @test */
    public function it_bulk_creates_phases_for_multiple_appointments()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment1 = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));
        $appointment2 = $this->createAppointment($service, Carbon::parse('2025-10-28 11:00:00'));
        $appointment3 = $this->createAppointment($service, Carbon::parse('2025-10-28 12:00:00'));

        $results = $this->service->bulkCreatePhases([$appointment1, $appointment2, $appointment3]);

        $this->assertCount(3, $results);
        $this->assertCount(3, $results[$appointment1->id]);
        $this->assertCount(3, $results[$appointment2->id]);
        $this->assertCount(3, $results[$appointment3->id]);

        $this->assertDatabaseCount('appointment_phases', 9); // 3 appointments * 3 phases
    }

    /** @test */
    public function it_checks_if_appointment_has_phases()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));

        $this->assertFalse($this->service->hasPhases($appointment));

        $this->service->createPhasesForAppointment($appointment);

        $this->assertTrue($this->service->hasPhases($appointment));
    }

    /** @test */
    public function it_gets_phase_statistics()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 15,
            'processing_duration' => 30,
            'final_duration' => 15,
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));
        $this->service->createPhasesForAppointment($appointment);

        $stats = $this->service->getPhaseStats($appointment);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['busy']); // initial + final
        $this->assertEquals(1, $stats['available']); // processing
        $this->assertEquals(60, $stats['total_duration']);
        $this->assertEquals(30, $stats['busy_duration']); // 15 + 15
        $this->assertEquals(30, $stats['available_duration']); // 30
    }

    /** @test */
    public function it_handles_service_with_only_two_phases()
    {
        $service = $this->createService([
            'has_processing_time' => true,
            'initial_duration' => 20,
            'processing_duration' => 30,
            'final_duration' => 0, // No final phase
        ]);

        $appointment = $this->createAppointment($service, Carbon::parse('2025-10-28 10:00:00'));

        $phases = $this->service->createPhasesForAppointment($appointment);

        $this->assertCount(2, $phases); // Only initial + processing
        $this->assertEquals('initial', $phases[0]->phase_type);
        $this->assertEquals('processing', $phases[1]->phase_type);
    }
}
