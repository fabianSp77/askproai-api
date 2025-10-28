<?php

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\AppointmentPhase;
use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use App\Models\Customer;
use App\Services\AppointmentPhaseCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * AppointmentPhaseCreationService Tests
 *
 * Tests phase creation, updating, and deletion logic
 */

beforeEach(function () {
    // Disable automatic phase creation in tests (we test the service directly)
    config(['features.processing_time_auto_create_phases' => false]);

    // Create test data structure
    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
    $this->staff = Staff::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
    ]);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

    // Service with Processing Time
    $this->service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);

    // Service without Processing Time
    $this->regularService = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => false,
        'duration_minutes' => 30,
    ]);

    $this->phaseService = app(AppointmentPhaseCreationService::class);
});

// ============================================================================
// PHASE CREATION TESTS
// ============================================================================

test('creates phases for appointment with processing time service', function () {
    // Enable feature
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // Note: branch_id is tracked via staff->branch_id, not directly on appointments
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    $phases = $this->phaseService->createPhasesForAppointment($appointment);

    expect($phases)->toHaveCount(3);

    // Check Initial Phase
    expect($phases[0])->toBeInstanceOf(AppointmentPhase::class);
    expect($phases[0]->phase_type)->toBe('initial');
    expect($phases[0]->start_offset_minutes)->toBe(0);
    expect($phases[0]->duration_minutes)->toBe(15);
    expect($phases[0]->staff_required)->toBeTrue();

    // Check Processing Phase
    expect($phases[1]->phase_type)->toBe('processing');
    expect($phases[1]->start_offset_minutes)->toBe(15);
    expect($phases[1]->duration_minutes)->toBe(30);
    expect($phases[1]->staff_required)->toBeFalse(); // Staff AVAILABLE

    // Check Final Phase
    expect($phases[2]->phase_type)->toBe('final');
    expect($phases[2]->start_offset_minutes)->toBe(45);
    expect($phases[2]->duration_minutes)->toBe(15);
    expect($phases[2]->staff_required)->toBeTrue();
});

test('returns empty array for regular service without processing time', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // branch_id removed - tracked via staff relationship
        'customer_id' => $this->customer->id,
        'service_id' => $this->regularService->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 09:30:00'),
    ]);

    $phases = $this->phaseService->createPhasesForAppointment($appointment);

    expect($phases)->toBeEmpty();
});

test('returns empty array when appointment has no starts_at', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // branch_id removed - tracked via staff relationship
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => null, // No start time
        'ends_at' => null,
    ]);

    $phases = $this->phaseService->createPhasesForAppointment($appointment);

    expect($phases)->toBeEmpty();
});

test('phase times are calculated correctly from starts_at', function () {
    config(['features.processing_time_enabled' => true]);

    $startTime = Carbon::parse('2025-10-28 14:30:00');

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // branch_id removed - tracked via staff relationship
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => $startTime,
        'ends_at' => $startTime->copy()->addHour(),
    ]);

    $phases = $this->phaseService->createPhasesForAppointment($appointment);

    // Initial: 14:30 - 14:45
    expect($phases[0]->start_time->format('H:i'))->toBe('14:30');
    expect($phases[0]->end_time->format('H:i'))->toBe('14:45');

    // Processing: 14:45 - 15:15
    expect($phases[1]->start_time->format('H:i'))->toBe('14:45');
    expect($phases[1]->end_time->format('H:i'))->toBe('15:15');

    // Final: 15:15 - 15:30
    expect($phases[2]->start_time->format('H:i'))->toBe('15:15');
    expect($phases[2]->end_time->format('H:i'))->toBe('15:30');
});

// ============================================================================
// UPDATE TESTS (Rescheduling)
// ============================================================================

test('updates phases when appointment is rescheduled', function () {
    config(['features.processing_time_enabled' => true]);

    $originalStart = Carbon::parse('2025-10-28 09:00:00');
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // branch_id removed - tracked via staff relationship
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => $originalStart,
        'ends_at' => $originalStart->copy()->addHour(),
    ]);

    // Create initial phases
    $initialPhases = $this->phaseService->createPhasesForAppointment($appointment);
    expect($initialPhases)->toHaveCount(3);
    expect($initialPhases[0]->start_time->format('H:i'))->toBe('09:00');

    // Reschedule to 14:00
    $appointment->starts_at = Carbon::parse('2025-10-28 14:00:00');
    $appointment->save();

    // Update phases
    $updatedPhases = $this->phaseService->updatePhasesForRescheduledAppointment($appointment);

    expect($updatedPhases)->toHaveCount(3);
    expect($updatedPhases[0]->start_time->format('H:i'))->toBe('14:00');
    expect($updatedPhases[1]->start_time->format('H:i'))->toBe('14:15');
    expect($updatedPhases[2]->start_time->format('H:i'))->toBe('14:45');
});

test('delete phases removes all phases for appointment', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // Note: branch_id is tracked via staff->branch_id, not directly on appointments
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    // Create phases
    $this->phaseService->createPhasesForAppointment($appointment);
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(3);

    // Delete phases
    $deletedCount = $this->phaseService->deletePhases($appointment);

    expect($deletedCount)->toBe(3);
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(0);
});

// ============================================================================
// RECREATE PHASES TESTS
// ============================================================================

test('recreate phases if needed creates phases when none exist', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // Note: branch_id is tracked via staff->branch_id, not directly on appointments
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    // No phases initially
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(0);

    // Recreate if needed
    $phases = $this->phaseService->recreatePhasesIfNeeded($appointment);

    expect($phases)->toHaveCount(3);
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(3);
});

test('recreate phases deletes phases when service no longer has processing time', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // Note: branch_id is tracked via staff->branch_id, not directly on appointments
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    // Create phases
    $this->phaseService->createPhasesForAppointment($appointment);
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(3);

    // Change service to regular (no processing time)
    $appointment->service_id = $this->regularService->id;
    $appointment->save();
    $appointment->load('service');

    // Recreate phases
    $phases = $this->phaseService->recreatePhasesIfNeeded($appointment);

    expect($phases)->toBeEmpty();
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(0);
});

// ============================================================================
// BULK OPERATIONS TESTS
// ============================================================================

test('bulk create phases for multiple appointments', function () {
    config(['features.processing_time_enabled' => true]);

    $appointments = [];
    for ($i = 0; $i < 5; $i++) {
        $appointments[] = Appointment::factory()->create([
            'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
            // branch_id removed - tracked via staff relationship
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'starts_at' => Carbon::parse('2025-10-28 09:00:00')->addHours($i),
            'ends_at' => Carbon::parse('2025-10-28 10:00:00')->addHours($i),
        ]);
    }

    $results = $this->phaseService->bulkCreatePhases($appointments);

    expect($results)->toHaveCount(5);
    foreach ($appointments as $appointment) {
        expect($results[$appointment->id])->toHaveCount(3);
    }

    // Verify in database
    expect(AppointmentPhase::count())->toBe(15); // 5 appointments × 3 phases
});

// ============================================================================
// STATISTICS TESTS
// ============================================================================

test('get phase stats returns correct statistics', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // Note: branch_id is tracked via staff->branch_id, not directly on appointments
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    $this->phaseService->createPhasesForAppointment($appointment);

    $stats = $this->phaseService->getPhaseStats($appointment);

    expect($stats)->toMatchArray([
        'total' => 3,
        'busy' => 2, // initial + final
        'available' => 1, // processing
        'total_duration' => 60,
        'busy_duration' => 30, // 15 + 15
        'available_duration' => 30, // processing
    ]);
});

test('has phases returns true when phases exist', function () {
    config(['features.processing_time_enabled' => true]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        // Note: branch_id is tracked via staff->branch_id, not directly on appointments
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    expect($this->phaseService->hasPhases($appointment))->toBeFalse();

    $this->phaseService->createPhasesForAppointment($appointment);

    expect($this->phaseService->hasPhases($appointment))->toBeTrue();
});
