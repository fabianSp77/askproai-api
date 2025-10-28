<?php

namespace Tests\Feature\Services;

use App\Models\{Appointment, AppointmentPhase, Branch, Company, Customer, Service, Staff};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Processing Time Integration Test
 *
 * End-to-end testing of Processing Time feature with automatic phase management
 */

beforeEach(function () {
    // Enable Processing Time feature globally
    config(['features.processing_time_enabled' => true]);
    config(['features.processing_time_auto_create_phases' => true]);

    // Create test data
    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
    $this->staff = Staff::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
    ]);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
});

// ============================================================================
// END-TO-END WORKFLOW TESTS
// ============================================================================

test('complete workflow: appointment creation with automatic phase generation', function () {
    // GIVEN: Service with Processing Time
    $service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);

    // WHEN: Create appointment
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'service_id' => $service->id,
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    // THEN: Phases automatically created by observer
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(3);

    $phases = AppointmentPhase::where('appointment_id', $appointment->id)
        ->orderBy('start_offset_minutes')
        ->get();

    expect($phases[0]->phase_type)->toBe('initial');
    expect($phases[0]->staff_required)->toBeTrue();
    expect($phases[0]->duration_minutes)->toBe(15);

    expect($phases[1]->phase_type)->toBe('processing');
    expect($phases[1]->staff_required)->toBeFalse(); // Staff AVAILABLE
    expect($phases[1]->duration_minutes)->toBe(30);

    expect($phases[2]->phase_type)->toBe('final');
    expect($phases[2]->staff_required)->toBeTrue();
    expect($phases[2]->duration_minutes)->toBe(15);
});

test('rescheduling automatically updates phase times', function () {
    // GIVEN: Appointment with phases at 09:00
    $service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'service_id' => $service->id,
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    $initialPhases = $appointment->phases()->orderBy('start_offset_minutes')->get();
    expect($initialPhases[0]->start_time->format('H:i'))->toBe('09:00');

    // WHEN: Reschedule to 14:00
    $appointment->update([
        'starts_at' => Carbon::parse('2025-10-28 14:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 15:00:00'),
    ]);

    // THEN: Phases automatically updated by observer
    $appointment->refresh();
    $updatedPhases = $appointment->phases()->orderBy('start_offset_minutes')->get();

    expect($updatedPhases)->toHaveCount(3);
    expect($updatedPhases[0]->start_time->format('H:i'))->toBe('14:00');
    expect($updatedPhases[1]->start_time->format('H:i'))->toBe('14:15');
    expect($updatedPhases[2]->start_time->format('H:i'))->toBe('14:45');
});

test('changing service to regular automatically removes phases', function () {
    // GIVEN: Appointment with Processing Time service
    $processingService = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);

    $regularService = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => false,
        'duration_minutes' => 30,
    ]);

    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'service_id' => $processingService->id,
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(3);

    // WHEN: Change to regular service
    $appointment->update(['service_id' => $regularService->id]);

    // THEN: Phases automatically removed by observer
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(0);
});

// ============================================================================
// FEATURE FLAG TESTS
// ============================================================================

test('feature disabled: no phases created for processing time services', function () {
    // GIVEN: Feature disabled
    config(['features.processing_time_enabled' => false]);

    $service = Service::factory()->create([
        'company_id' => $this->company->id,
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);

    // WHEN: Create appointment
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'service_id' => $service->id,
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    // THEN: No phases created
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(0);
});

test('company whitelist: only whitelisted companies can use feature', function () {
    // GIVEN: Feature enabled but only for company ID 999
    config([
        'features.processing_time_enabled' => true,
        'features.processing_time_company_whitelist' => [999],
    ]);

    $service = Service::factory()->create([
        'company_id' => $this->company->id, // Our company is NOT 999
        'has_processing_time' => true,
        'initial_duration' => 15,
        'processing_duration' => 30,
        'final_duration' => 15,
        'duration_minutes' => 60,
    ]);

    // WHEN: Create appointment
    $appointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'service_id' => $service->id,
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff->id,
        'starts_at' => Carbon::parse('2025-10-28 09:00:00'),
        'ends_at' => Carbon::parse('2025-10-28 10:00:00'),
    ]);

    // THEN: No phases created (company not in whitelist)
    expect(AppointmentPhase::where('appointment_id', $appointment->id)->count())->toBe(0);
});
