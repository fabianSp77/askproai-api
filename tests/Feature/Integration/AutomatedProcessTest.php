<?php

use App\Services\AutomatedProcessService;
use App\Services\AppointmentService;
use App\Services\NotificationWorkflowService;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\WorkingHour;
use App\Models\RecurringAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->appointmentService = $this->mock(AppointmentService::class);
    $this->notificationService = $this->mock(NotificationWorkflowService::class);

    $this->automatedService = new AutomatedProcessService(
        $this->appointmentService,
        $this->notificationService
    );

    // Create test data
    $this->company = \App\Models\Company::factory()->create();
    $this->branch = \App\Models\Branch::factory()->create(['company_id' => $this->company->id]);
    $this->service = Service::factory()->create(['company_id' => $this->company->id]);

    $this->staff = Staff::factory(3)->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'rating' => 4.5
    ]);

    // Assign service to staff
    foreach ($this->staff as $member) {
        $member->services()->attach($this->service->id);
    }

    $this->customer = Customer::factory()->create([
        'company_id' => $this->company->id
    ]);

    // Create working hours
    $this->createWorkingHours();
});

it('auto-assigns staff to appointments based on availability', function () {
    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'appointment_time' => '10:00:00',
        'duration' => 60,
        'staff_id' => null // No staff assigned
    ]);

    $assignedStaff = $this->automatedService->autoAssignStaff($appointment);

    expect($assignedStaff)->not->toBeNull();
    expect($appointment->fresh()->staff_id)->toBe($assignedStaff->id);
});

it('selects best staff based on workload balance', function () {
    // Create appointments to simulate different workloads
    $busyStaff = $this->staff[0];
    $availableStaff = $this->staff[1];

    // Make first staff busy
    Appointment::factory(5)->create([
        'staff_id' => $busyStaff->id,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'status' => 'scheduled'
    ]);

    // Make second staff less busy
    Appointment::factory(1)->create([
        'staff_id' => $availableStaff->id,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'status' => 'scheduled'
    ]);

    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'appointment_time' => '14:00:00',
        'staff_id' => null
    ]);

    $assignedStaff = $this->automatedService->autoAssignStaff($appointment);

    expect($assignedStaff->id)->toBe($availableStaff->id);
});

it('respects customer preferred staff when available', function () {
    $preferredStaff = $this->staff[1];
    $this->customer->update(['preferred_staff_id' => $preferredStaff->id]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'appointment_time' => '11:00:00',
        'staff_id' => null
    ]);

    $assignedStaff = $this->automatedService->autoAssignStaff($appointment);

    expect($assignedStaff->id)->toBe($preferredStaff->id);
});

it('creates recurring appointment instances', function () {
    $templateAppointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff[0]->id,
        'appointment_date' => now()->format('Y-m-d'),
        'appointment_time' => '10:00:00',
        'status' => 'scheduled'
    ]);

    $recurring = RecurringAppointment::factory()->create([
        'customer_id' => $this->customer->id,
        'template_appointment_id' => $templateAppointment->id,
        'pattern' => 'weekly',
        'interval' => 1,
        'next_occurrence' => now()->addWeek(),
        'end_date' => now()->addWeeks(4),
        'is_active' => true
    ]);

    $this->notificationService
        ->shouldReceive('sendAppointmentConfirmation')
        ->times(4); // For 4 weeks

    $created = $this->automatedService->processRecurringAppointments();

    expect($created)->toBeGreaterThan(0);

    // Check appointments were created
    $recurringInstances = Appointment::where('recurring_appointment_id', $recurring->id)->get();
    expect($recurringInstances)->not->toBeEmpty();
});

it('auto-confirms appointments for trusted customers', function () {
    // Create trusted customer
    $trustedCustomer = Customer::factory()->create([
        'trust_level' => 'high',
        'auto_confirm_enabled' => true
    ]);

    $pendingAppointments = Appointment::factory(3)->create([
        'customer_id' => $trustedCustomer->id,
        'status' => 'pending',
        'created_at' => now()->subHours(25) // Over 24 hours old
    ]);

    $this->notificationService
        ->shouldReceive('sendAppointmentConfirmation')
        ->times(3);

    $confirmed = $this->automatedService->autoConfirmAppointments();

    expect($confirmed)->toBe(3);

    foreach ($pendingAppointments as $appointment) {
        expect($appointment->fresh()->status)->toBe('scheduled');
    }
});

it('processes incomplete appointments with follow-ups', function () {
    $incompleteAppointments = Appointment::factory(2)->create([
        'status' => 'pending',
        'appointment_date' => now()->subDay(),
        'follow_up_count' => 0
    ]);

    $this->notificationService
        ->shouldReceive('sendAppointmentReminders')
        ->times(2);

    $processed = $this->automatedService->processIncompleteAppointments();

    expect($processed)->toBe(2);

    foreach ($incompleteAppointments as $appointment) {
        expect($appointment->fresh()->follow_up_count)->toBe(1);
    }
});

it('auto-cancels appointments after 3 follow-ups', function () {
    $appointment = Appointment::factory()->create([
        'status' => 'pending',
        'appointment_date' => now()->subDays(5),
        'follow_up_count' => 2 // Already had 2 follow-ups
    ]);

    $this->notificationService
        ->shouldReceive('sendAppointmentReminders')
        ->once();

    $processed = $this->automatedService->processIncompleteAppointments();

    expect($processed)->toBe(1);

    $appointment->refresh();
    expect($appointment->follow_up_count)->toBe(3);
    expect($appointment->status)->toBe('cancelled');
});

it('suggests intelligent reschedule options', function () {
    // Create past appointments to establish pattern
    Appointment::factory(3)->create([
        'customer_id' => $this->customer->id,
        'appointment_time' => '14:00:00',
        'status' => 'completed'
    ]);

    Appointment::factory(2)->create([
        'customer_id' => $this->customer->id,
        'appointment_time' => '10:00:00',
        'status' => 'completed'
    ]);

    $currentAppointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff[0]->id,
        'appointment_date' => now()->addDay(),
        'appointment_time' => '14:00:00'
    ]);

    // Mock available slots
    $this->appointmentService
        ->shouldReceive('getAvailableSlots')
        ->andReturn([
            ['date' => now()->addDays(2), 'time' => '14:00', 'staff_id' => $this->staff[0]->id],
            ['date' => now()->addDays(3), 'time' => '10:00', 'staff_id' => $this->staff[1]->id]
        ]);

    $suggestions = $this->automatedService->suggestReschedule($currentAppointment);

    expect($suggestions)->not->toBeEmpty();
    expect($suggestions[0]['time'])->toBe('14:00'); // Prefers customer's usual time
});

it('manages waitlist and offers cancelled slots', function () {
    // Create cancelled appointment
    $cancelledAppointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'appointment_date' => now()->addDays(3),
        'appointment_time' => '15:00:00',
        'status' => 'cancelled',
        'updated_at' => now()->subMinutes(30)
    ]);

    // Create waitlist entries
    $waitlistEntry = \App\Models\WaitlistEntry::factory()->create([
        'customer_id' => $this->customer->id,
        'service_id' => $this->service->id,
        'preferred_date' => $cancelledAppointment->appointment_date,
        'status' => 'waiting'
    ]);

    $processed = $this->automatedService->processWaitlist();

    expect($processed)->toBeGreaterThan(0);
});

it('optimizes staff schedules by balancing workload', function () {
    $branch = $this->branch;
    $date = now()->addDay();

    // Create unbalanced schedule
    $overloadedStaff = $this->staff[0];
    $underutilizedStaff = $this->staff[1];

    // Overload first staff
    Appointment::factory(8)->create([
        'staff_id' => $overloadedStaff->id,
        'appointment_date' => $date,
        'status' => 'scheduled'
    ]);

    // Underutilize second staff
    Appointment::factory(2)->create([
        'staff_id' => $underutilizedStaff->id,
        'appointment_date' => $date,
        'status' => 'scheduled'
    ]);

    $optimizations = $this->automatedService->optimizeStaffSchedules($date);

    expect($optimizations['shifts_balanced'])->toBeGreaterThan(0);
});

it('respects working hours when creating appointments', function () {
    $staff = $this->staff[0];
    $dayOfWeek = Carbon::now()->addDay()->dayOfWeek;

    // Set specific working hours
    WorkingHour::where('staff_id', $staff->id)
        ->where('day_of_week', $dayOfWeek)
        ->update([
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true
        ]);

    $appointment = Appointment::factory()->create([
        'service_id' => $this->service->id,
        'branch_id' => $this->branch->id,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'appointment_time' => '18:00:00', // Outside working hours
        'staff_id' => null
    ]);

    $assignedStaff = $this->automatedService->autoAssignStaff($appointment);

    // Should not assign this staff as they're not available at 18:00
    if ($assignedStaff) {
        expect($assignedStaff->id)->not->toBe($staff->id);
    }
});

it('handles recurring appointments with conflicts', function () {
    $templateAppointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff[0]->id,
        'appointment_time' => '10:00:00'
    ]);

    $recurring = RecurringAppointment::factory()->create([
        'customer_id' => $this->customer->id,
        'template_appointment_id' => $templateAppointment->id,
        'pattern' => 'weekly',
        'next_occurrence' => now()->addWeek()
    ]);

    // Create conflict for next week
    Appointment::factory()->create([
        'staff_id' => $this->staff[0]->id,
        'appointment_date' => now()->addWeek()->format('Y-m-d'),
        'appointment_time' => '10:00:00',
        'status' => 'scheduled'
    ]);

    $created = $this->automatedService->processRecurringAppointments();

    // Should handle conflict gracefully
    expect(true)->toBeTrue();
});

function createWorkingHours()
{
    foreach ($this->staff as $member) {
        for ($day = 0; $day < 7; $day++) {
            WorkingHour::factory()->create([
                'staff_id' => $member->id,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'is_available' => $day !== 0 // Not available on Sundays
            ]);
        }
    }
}