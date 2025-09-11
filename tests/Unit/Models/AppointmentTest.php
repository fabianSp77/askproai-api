<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        // Arrange & Act
        $appointment = new Appointment();
        $expectedFillable = [
            'tenant_id',
            'customer_id',
            'call_id',
            'staff_id',
            'service_id',
            'branch_id',
            'start_time',
            'end_time',
            'status',
            'notes',
            'calcom_booking_id'
        ];

        // Assert
        $this->assertEquals($expectedFillable, $appointment->getFillable());
    }

    /** @test */
    public function it_casts_dates_correctly()
    {
        // Arrange & Act
        $appointment = Appointment::factory()->create([
            'start_time' => '2025-09-06 14:00:00',
            'end_time' => '2025-09-06 15:00:00'
        ]);

        // Assert
        $this->assertInstanceOf(Carbon::class, $appointment->start_time);
        $this->assertInstanceOf(Carbon::class, $appointment->end_time);
    }

    /** @test */
    public function it_belongs_to_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $appointment = Appointment::factory()->create(['tenant_id' => $tenant->id]);

        // Act
        $appointmentTenant = $appointment->tenant;

        // Assert
        $this->assertInstanceOf(Tenant::class, $appointmentTenant);
        $this->assertEquals($tenant->id, $appointmentTenant->id);
    }

    /** @test */
    public function it_belongs_to_customer()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $appointmentCustomer = $appointment->customer;

        // Assert
        $this->assertInstanceOf(Customer::class, $appointmentCustomer);
        $this->assertEquals($customer->id, $appointmentCustomer->id);
    }

    /** @test */
    public function it_belongs_to_call()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $call = Call::factory()->create(['tenant_id' => $tenant->id]);
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'call_id' => $call->id
        ]);

        // Act
        $appointmentCall = $appointment->call;

        // Assert
        $this->assertInstanceOf(Call::class, $appointmentCall);
        $this->assertEquals($call->id, $appointmentCall->id);
    }

    /** @test */
    public function it_belongs_to_staff()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $staff = Staff::factory()->create(['tenant_id' => $tenant->id]);
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id
        ]);

        // Act
        $appointmentStaff = $appointment->staff;

        // Assert
        $this->assertInstanceOf(Staff::class, $appointmentStaff);
        $this->assertEquals($staff->id, $appointmentStaff->id);
    }

    /** @test */
    public function it_belongs_to_service()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'service_id' => $service->id
        ]);

        // Act
        $appointmentService = $appointment->service;

        // Assert
        $this->assertInstanceOf(Service::class, $appointmentService);
        $this->assertEquals($service->id, $appointmentService->id);
    }

    /** @test */
    public function it_can_determine_if_appointment_is_upcoming()
    {
        // Arrange
        $upcomingAppointment = Appointment::factory()->create([
            'start_time' => now()->addHours(2)
        ]);
        $pastAppointment = Appointment::factory()->create([
            'start_time' => now()->subHours(2)
        ]);

        // Act & Assert
        $this->assertTrue($upcomingAppointment->isUpcoming());
        $this->assertFalse($pastAppointment->isUpcoming());
    }

    /** @test */
    public function it_can_determine_if_appointment_is_today()
    {
        // Arrange
        $todayAppointment = Appointment::factory()->create([
            'start_time' => now()->setTime(14, 0, 0)
        ]);
        $tomorrowAppointment = Appointment::factory()->create([
            'start_time' => now()->addDay()->setTime(14, 0, 0)
        ]);

        // Act & Assert
        $this->assertTrue($todayAppointment->isToday());
        $this->assertFalse($tomorrowAppointment->isToday());
    }

    /** @test */
    public function it_can_calculate_duration_in_minutes()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'start_time' => now()->setTime(14, 0, 0),
            'end_time' => now()->setTime(15, 30, 0)
        ]);

        // Act
        $duration = $appointment->getDurationInMinutes();

        // Assert
        $this->assertEquals(90, $duration); // 1.5 hours = 90 minutes
    }

    /** @test */
    public function it_can_determine_appointment_status()
    {
        // Arrange
        $scheduledAppointment = Appointment::factory()->create(['status' => 'scheduled']);
        $completedAppointment = Appointment::factory()->create(['status' => 'completed']);
        $cancelledAppointment = Appointment::factory()->create(['status' => 'cancelled']);

        // Act & Assert
        $this->assertTrue($scheduledAppointment->isScheduled());
        $this->assertTrue($completedAppointment->isCompleted());
        $this->assertTrue($cancelledAppointment->isCancelled());
    }

    /** @test */
    public function it_can_scope_scheduled_appointments()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Appointment::factory(3)->create([
            'tenant_id' => $tenant->id,
            'status' => 'scheduled'
        ]);
        Appointment::factory(2)->create([
            'tenant_id' => $tenant->id,
            'status' => 'completed'
        ]);

        // Act
        $scheduledAppointments = Appointment::where('tenant_id', $tenant->id)
            ->scheduled()->get();

        // Assert
        $this->assertCount(3, $scheduledAppointments);
        foreach ($scheduledAppointments as $appointment) {
            $this->assertEquals('scheduled', $appointment->status);
        }
    }

    /** @test */
    public function it_can_scope_appointments_for_today()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        // Today's appointments
        Appointment::factory(2)->create([
            'tenant_id' => $tenant->id,
            'start_time' => now()->setTime(10, 0, 0)
        ]);
        
        // Tomorrow's appointments
        Appointment::factory(1)->create([
            'tenant_id' => $tenant->id,
            'start_time' => now()->addDay()->setTime(10, 0, 0)
        ]);

        // Act
        $todayAppointments = Appointment::where('tenant_id', $tenant->id)
            ->forDate(now()->toDateString())->get();

        // Assert
        $this->assertCount(2, $todayAppointments);
    }

    /** @test */
    public function it_can_scope_upcoming_appointments()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        // Upcoming appointments
        Appointment::factory(3)->create([
            'tenant_id' => $tenant->id,
            'start_time' => now()->addHours(2),
            'status' => 'scheduled'
        ]);
        
        // Past appointments
        Appointment::factory(2)->create([
            'tenant_id' => $tenant->id,
            'start_time' => now()->subHours(2),
            'status' => 'completed'
        ]);

        // Act
        $upcomingAppointments = Appointment::where('tenant_id', $tenant->id)
            ->upcoming()->get();

        // Assert
        $this->assertCount(3, $upcomingAppointments);
        foreach ($upcomingAppointments as $appointment) {
            $this->assertTrue($appointment->start_time->isFuture());
        }
    }

    /** @test */
    public function it_can_mark_appointment_as_completed()
    {
        // Arrange
        $appointment = Appointment::factory()->create(['status' => 'scheduled']);

        // Act
        $appointment->markAsCompleted();

        // Assert
        $this->assertEquals('completed', $appointment->status);
        $this->assertTrue($appointment->isCompleted());
    }

    /** @test */
    public function it_can_cancel_appointment()
    {
        // Arrange
        $appointment = Appointment::factory()->create(['status' => 'scheduled']);

        // Act
        $appointment->cancel('Patient requested cancellation');

        // Assert
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertTrue($appointment->isCancelled());
        $this->assertStringContains('Patient requested cancellation', $appointment->notes);
    }

    /** @test */
    public function it_can_reschedule_appointment()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'start_time' => now()->addDay()->setTime(10, 0, 0),
            'end_time' => now()->addDay()->setTime(11, 0, 0)
        ]);

        $newStartTime = now()->addDays(2)->setTime(14, 0, 0);
        $newEndTime = now()->addDays(2)->setTime(15, 0, 0);

        // Act
        $appointment->reschedule($newStartTime, $newEndTime, 'Patient requested different time');

        // Assert
        $this->assertTrue($appointment->start_time->equalTo($newStartTime));
        $this->assertTrue($appointment->end_time->equalTo($newEndTime));
        $this->assertStringContains('Patient requested different time', $appointment->notes);
    }

    /** @test */
    public function it_can_determine_if_appointment_conflicts_with_another()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $staff = Staff::factory()->create(['tenant_id' => $tenant->id]);
        
        $existingAppointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'start_time' => now()->addDay()->setTime(10, 0, 0),
            'end_time' => now()->addDay()->setTime(11, 0, 0)
        ]);
        
        // Overlapping appointment
        $conflictingAppointment = Appointment::factory()->make([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'start_time' => now()->addDay()->setTime(10, 30, 0),
            'end_time' => now()->addDay()->setTime(11, 30, 0)
        ]);
        
        // Non-overlapping appointment
        $nonConflictingAppointment = Appointment::factory()->make([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'start_time' => now()->addDay()->setTime(12, 0, 0),
            'end_time' => now()->addDay()->setTime(13, 0, 0)
        ]);

        // Act & Assert
        $this->assertTrue($conflictingAppointment->hasConflict());
        $this->assertFalse($nonConflictingAppointment->hasConflict());
    }

    /** @test */
    public function it_can_get_formatted_time_range()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'start_time' => Carbon::parse('2025-09-06 14:30:00'),
            'end_time' => Carbon::parse('2025-09-06 15:45:00')
        ]);

        // Act
        $timeRange = $appointment->getFormattedTimeRange();

        // Assert
        $this->assertEquals('14:30 - 15:45', $timeRange);
    }

    /** @test */
    public function it_can_get_time_until_appointment()
    {
        // Arrange
        $appointment = Appointment::factory()->create([
            'start_time' => now()->addHours(2)
        ]);

        // Act
        $timeUntil = $appointment->getTimeUntilAppointment();

        // Assert
        $this->assertStringContains('2 hours', $timeUntil);
    }

    /** @test */
    public function it_can_determine_if_appointment_needs_reminder()
    {
        // Arrange
        $soonAppointment = Appointment::factory()->create([
            'start_time' => now()->addHour(),
            'status' => 'scheduled'
        ]);
        
        $laterAppointment = Appointment::factory()->create([
            'start_time' => now()->addDays(2),
            'status' => 'scheduled'
        ]);
        
        $completedAppointment = Appointment::factory()->create([
            'start_time' => now()->addHour(),
            'status' => 'completed'
        ]);

        // Act & Assert
        $this->assertTrue($soonAppointment->needsReminder());
        $this->assertFalse($laterAppointment->needsReminder());
        $this->assertFalse($completedAppointment->needsReminder());
    }

    /** @test */
    public function it_can_calculate_no_show_rate_for_customer()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        // Create appointments with different statuses
        Appointment::factory(2)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'start_time' => now()->subDays(5)
        ]);
        
        Appointment::factory(1)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'status' => 'no_show',
            'start_time' => now()->subDays(3)
        ]);

        // Act
        $noShowRate = Appointment::getNoShowRateForCustomer($customer->id);

        // Assert
        $this->assertEquals(33.33, $noShowRate); // 1 out of 3 = 33.33%
    }

    /** @test */
    public function it_can_get_appointment_summary()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'name' => 'John Doe']);
        $service = Service::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Consultation']);
        $staff = Staff::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Dr. Smith']);
        
        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'start_time' => Carbon::parse('2025-09-06 14:30:00'),
            'end_time' => Carbon::parse('2025-09-06 15:30:00')
        ]);

        // Act
        $summary = $appointment->getSummary();

        // Assert
        $this->assertStringContains('John Doe', $summary);
        $this->assertStringContains('Consultation', $summary);
        $this->assertStringContains('Dr. Smith', $summary);
        $this->assertStringContains('14:30', $summary);
    }
}