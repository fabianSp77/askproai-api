<?php

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Company;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->company = Company::factory()->create();
    $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
    $this->service = Service::factory()->create(['company_id' => $this->company->id]);
    $this->staff = Staff::factory()->create([
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id
    ]);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
});

it('can list appointments', function () {
    actingAsAdmin();

    Appointment::factory(3)->create([
        'company_id' => $this->company->id,
        'customer_id' => $this->customer->id
    ]);

    Livewire::test(AppointmentResource\Pages\ListAppointments::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3);
});

it('can create appointment with valid data', function () {
    actingAsCompanyOwner($this->company);

    $appointmentData = [
        'company_id' => $this->company->id,
        'branch_id' => $this->branch->id,
        'service_id' => $this->service->id,
        'staff_id' => $this->staff->id,
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->addDays(2)->format('Y-m-d'),
        'appointment_time' => '14:30:00',
        'duration' => 60,
        'status' => 'scheduled',
        'price' => 75.00,
        'notes' => 'First appointment',
    ];

    Livewire::test(AppointmentResource\Pages\CreateAppointment::class)
        ->fillForm($appointmentData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('appointments', [
        'customer_id' => $this->customer->id,
        'staff_id' => $this->staff->id,
        'service_id' => $this->service->id,
        'status' => 'scheduled'
    ]);
});

it('validates appointment conflicts', function () {
    actingAsCompanyOwner($this->company);

    // Create existing appointment
    $existingAppointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'staff_id' => $this->staff->id,
        'appointment_date' => '2024-12-20',
        'appointment_time' => '14:00:00',
        'duration' => 60,
        'status' => 'scheduled'
    ]);

    // Try to create conflicting appointment
    Livewire::test(AppointmentResource\Pages\CreateAppointment::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2024-12-20',
            'appointment_time' => '14:30:00', // Conflicts with existing appointment
            'duration' => 60,
            'status' => 'scheduled',
        ])
        ->call('create')
        ->assertHasFormErrors(['appointment_time']);
});

it('can update appointment status', function () {
    actingAsAdmin();

    $appointment = Appointment::factory()->create([
        'status' => 'scheduled'
    ]);

    Livewire::test(AppointmentResource\Pages\EditAppointment::class, [
        'record' => $appointment->getRouteKey()
    ])
        ->fillForm([
            'status' => 'completed'
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($appointment->refresh()->status)->toBe('completed');
});

it('can filter appointments by date range', function () {
    actingAsAdmin();

    // Create appointments across different dates
    Appointment::factory()->create([
        'appointment_date' => now()->subDays(5)
    ]);

    Appointment::factory(2)->create([
        'appointment_date' => now()
    ]);

    Appointment::factory()->create([
        'appointment_date' => now()->addDays(5)
    ]);

    Livewire::test(AppointmentResource\Pages\ListAppointments::class)
        ->assertCountTableRecords(4)
        ->filterTable('appointment_date', [
            'from' => now()->startOfDay()->format('Y-m-d'),
            'until' => now()->endOfDay()->format('Y-m-d')
        ])
        ->assertCountTableRecords(2);
});

it('can filter appointments by status', function () {
    actingAsAdmin();

    Appointment::factory(2)->create(['status' => 'scheduled']);
    Appointment::factory(3)->create(['status' => 'completed']);
    Appointment::factory(1)->create(['status' => 'cancelled']);

    Livewire::test(AppointmentResource\Pages\ListAppointments::class)
        ->assertCountTableRecords(6)
        ->filterTable('status', 'scheduled')
        ->assertCountTableRecords(2)
        ->filterTable('status', 'completed')
        ->assertCountTableRecords(3);
});

it('shows appointment calendar view', function () {
    actingAsAdmin();

    $appointments = Appointment::factory(5)->create([
        'appointment_date' => now()->format('Y-m-d')
    ]);

    Livewire::test(AppointmentResource\Pages\CalendarAppointments::class)
        ->assertSuccessful()
        ->assertSee('calendar');
});

it('can reschedule appointment', function () {
    actingAsAdmin();

    $appointment = Appointment::factory()->create([
        'appointment_date' => '2024-12-20',
        'appointment_time' => '10:00:00'
    ]);

    $newDate = '2024-12-25';
    $newTime = '14:00:00';

    Livewire::test(AppointmentResource\Pages\EditAppointment::class, [
        'record' => $appointment->getRouteKey()
    ])
        ->fillForm([
            'appointment_date' => $newDate,
            'appointment_time' => $newTime,
            'rescheduled_reason' => 'Customer request'
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $appointment->refresh();
    expect($appointment->appointment_date->format('Y-m-d'))->toBe($newDate);
    expect($appointment->appointment_time)->toBe($newTime);
    expect($appointment->rescheduled_count)->toBe(1);
});

it('sends notification on appointment confirmation', function () {
    \Illuminate\Support\Facades\Notification::fake();

    actingAsAdmin();

    $appointment = Appointment::factory()->create([
        'status' => 'pending',
        'customer_id' => $this->customer->id
    ]);

    Livewire::test(AppointmentResource\Pages\ListAppointments::class)
        ->callTableAction('confirm', $appointment)
        ->assertSuccessful();

    expect($appointment->refresh()->status)->toBe('scheduled');

    \Illuminate\Support\Facades\Notification::assertSentTo(
        $this->customer,
        \App\Notifications\AppointmentConfirmed::class
    );
});

it('calculates appointment revenue correctly', function () {
    actingAsAdmin();

    Appointment::factory()->create([
        'price' => 100.00,
        'status' => 'completed',
        'appointment_date' => now()
    ]);

    Appointment::factory()->create([
        'price' => 150.00,
        'status' => 'completed',
        'appointment_date' => now()
    ]);

    Appointment::factory()->create([
        'price' => 200.00,
        'status' => 'cancelled', // Should not be counted
        'appointment_date' => now()
    ]);

    $todayRevenue = Appointment::whereDate('appointment_date', now())
        ->where('status', 'completed')
        ->sum('price');

    expect($todayRevenue)->toBe(250.00);
});

it('validates working hours when creating appointment', function () {
    actingAsCompanyOwner($this->company);

    // Create working hours for staff
    \App\Models\WorkingHour::factory()->create([
        'staff_id' => $this->staff->id,
        'day_of_week' => Carbon::parse('2024-12-20')->dayOfWeek, // Friday
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'is_available' => true
    ]);

    // Try to create appointment outside working hours
    Livewire::test(AppointmentResource\Pages\CreateAppointment::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'customer_id' => $this->customer->id,
            'appointment_date' => '2024-12-20',
            'appointment_time' => '18:00:00', // Outside working hours
            'duration' => 60,
        ])
        ->call('create')
        ->assertHasFormErrors(['appointment_time']);
});

it('can bulk update appointment status', function () {
    actingAsAdmin();

    $appointments = Appointment::factory(3)->create(['status' => 'scheduled']);

    Livewire::test(AppointmentResource\Pages\ListAppointments::class)
        ->callTableBulkAction('updateStatus', $appointments->pluck('id')->toArray(), [
            'status' => 'completed'
        ])
        ->assertSuccessful();

    foreach ($appointments as $appointment) {
        expect($appointment->refresh()->status)->toBe('completed');
    }
});