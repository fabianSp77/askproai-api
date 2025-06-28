<?php

namespace Tests\E2E;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentRescheduled;
use App\Mail\AppointmentCancellationConfirmation;
use App\Mail\AppointmentRescheduledConfirmation;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Notifications\AppointmentCancelledNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppointmentManagementE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected Staff $staff;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test environment
        $this->company = Company::factory()->create([
            'name' => 'Test Clinic',
            'settings' => [
                'customer_portal' => true,
                'portal_features' => [
                    'appointments' => true,
                    'cancel_appointments' => true,
                    'reschedule_appointments' => true,
                ],
                'cancellation_policy' => [
                    'enabled' => true,
                    'minimum_hours' => 24,
                    'fee_percentage' => 0,
                ],
                'timezone' => 'Europe/Berlin',
            ],
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Clinic',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Smith',
            'email' => 'dr.smith@clinic.com',
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Consultation',
            'duration' => 60,
            'price' => 100.00,
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Patient Test',
            'email' => 'patient@example.com',
            'phone' => '+4915123456789',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'portal_access_enabled' => true,
        ]);

        // Login customer
        $this->actingAs($this->customer, 'customer');
    }

    /** @test */

    #[Test]
    public function customer_can_view_appointment_list_with_filters()
    {
        // Create various appointments
        $appointments = [
            // Upcoming
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => now()->addDays(3)->setTime(10, 0),
                'end_time' => now()->addDays(3)->setTime(11, 0),
                'status' => 'confirmed',
                'confirmation_code' => 'APT001',
            ]),
            // Past completed
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => now()->subWeek()->setTime(14, 0),
                'end_time' => now()->subWeek()->setTime(15, 0),
                'status' => 'completed',
                'notes' => 'Follow-up recommended',
            ]),
            // Cancelled
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => now()->subMonth()->setTime(9, 0),
                'end_time' => now()->subMonth()->setTime(10, 0),
                'status' => 'cancelled',
                'cancelled_at' => now()->subMonth()->subDay(),
                'cancellation_reason' => 'Personal emergency',
            ]),
        ];

        // Visit appointments page
        $response = $this->get('/customer/appointments');
        
        $response->assertStatus(200);
        $response->assertSee('My Appointments');
        
        // Check tabs/filters
        $response->assertSee('Upcoming');
        $response->assertSee('Past');
        $response->assertSee('Cancelled');
        $response->assertSee('All');
        
        // Default view shows upcoming appointments
        $response->assertSee('APT001');
        $response->assertSee('Consultation with Dr. Smith');
        $response->assertSee($appointments[0]->start_time->format('l, F j, Y'));
        $response->assertSee('10:00 AM - 11:00 AM');
        $response->assertSee('Main Clinic');
        
        // Action buttons for upcoming appointment
        $response->assertSee('View Details');
        $response->assertSee('Cancel Appointment');
        $response->assertSee('Add to Calendar');
        
        // Filter by past appointments
        $response = $this->get('/customer/appointments?filter=past');
        
        $response->assertStatus(200);
        $response->assertSee('Follow-up recommended');
        $response->assertSee('Completed');
        $response->assertDontSee('Cancel Appointment'); // Can't cancel past appointments
        
        // Filter by cancelled
        $response = $this->get('/customer/appointments?filter=cancelled');
        
        $response->assertStatus(200);
        $response->assertSee('Personal emergency');
        $response->assertSee('Cancelled on');
        
        // Search functionality
        $response = $this->get('/customer/appointments?search=Smith');
        
        $response->assertStatus(200);
        $response->assertSee('Dr. Smith');
        $this->assertCount(3, $response->viewData('appointments'));
    }

    /** @test */

    #[Test]
    public function customer_can_view_appointment_details()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addDays(5)->setTime(14, 30),
            'end_time' => now()->addDays(5)->setTime(15, 30),
            'status' => 'confirmed',
            'price' => 100.00,
            'notes' => 'Please bring previous medical records',
            'confirmation_code' => 'CONF123',
            'booking_source' => 'phone',
        ]);

        $response = $this->get("/customer/appointments/{$appointment->id}");
        
        $response->assertStatus(200);
        
        // Header information
        $response->assertSee('Appointment Details');
        $response->assertSee('CONF123');
        $response->assertSee('Confirmed');
        
        // Appointment information
        $response->assertSee('Consultation');
        $response->assertSee('Dr. Smith');
        $response->assertSee($appointment->start_time->format('l, F j, Y'));
        $response->assertSee('2:30 PM - 3:30 PM');
        $response->assertSee('Duration: 60 minutes');
        
        // Location details
        $response->assertSee('Main Clinic');
        $response->assertSee($this->branch->address);
        $response->assertSee('Get Directions');
        
        // Special instructions
        $response->assertSee('Special Instructions');
        $response->assertSee('Please bring previous medical records');
        
        // Price information
        $response->assertSee('Service Cost');
        $response->assertSee('€100.00');
        
        // Actions
        $response->assertSee('Cancel Appointment');
        $response->assertSee('Reschedule');
        $response->assertSee('Add to Calendar');
        $response->assertSee('Print');
        
        // Cancellation policy notice
        $response->assertSee('Cancellation Policy');
        $response->assertSee('Free cancellation up to 24 hours before appointment');
    }

    /** @test */

    #[Test]
    public function customer_can_cancel_appointment_with_confirmation()
    {
        Mail::fake();
        Event::fake();
        Notification::fake();

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addDays(3)->setTime(10, 0),
            'end_time' => now()->addDays(3)->setTime(11, 0),
            'status' => 'confirmed',
            'confirmation_code' => 'CANCEL001',
        ]);

        // Visit appointment details
        $response = $this->get("/customer/appointments/{$appointment->id}");
        $response->assertSee('Cancel Appointment');
        
        // Click cancel - should show confirmation modal
        $response = $this->get("/customer/appointments/{$appointment->id}/cancel");
        
        $response->assertStatus(200);
        $response->assertSee('Cancel Appointment?');
        $response->assertSee('Are you sure you want to cancel this appointment?');
        $response->assertSee('Consultation with Dr. Smith');
        $response->assertSee($appointment->start_time->format('l, F j at g:i A'));
        
        // Cancellation reason form
        $response->assertSee('Reason for cancellation');
        $response->assertSee('option value="schedule_conflict"');
        $response->assertSee('option value="feeling_better"');
        $response->assertSee('option value="personal_emergency"');
        $response->assertSee('option value="other"');
        
        // Submit cancellation
        $response = $this->post("/customer/appointments/{$appointment->id}/cancel", [
            'reason' => 'schedule_conflict',
            'notes' => 'Work meeting came up',
        ]);
        
        $response->assertRedirect('/customer/appointments');
        $response->assertSessionHas('success', 'Appointment cancelled successfully.');
        
        // Verify appointment was cancelled
        $appointment->refresh();
        $this->assertEquals('cancelled', $appointment->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertEquals('schedule_conflict', $appointment->cancellation_reason);
        $this->assertEquals('Work meeting came up', $appointment->cancellation_notes);
        $this->assertEquals($this->customer->id, $appointment->cancelled_by);
        
        // Verify confirmation email was sent
        Mail::assertQueued(AppointmentCancellationConfirmation::class, function ($mail) use ($appointment) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->appointment->id === $appointment->id;
        });
        
        // Verify staff was notified
        Notification::assertSentTo($this->staff, AppointmentCancelledNotification::class);
        
        // Verify event was fired
        Event::assertDispatched(AppointmentCancelled::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id &&
                   $event->cancelledBy === 'customer';
        });
        
        // Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'description' => 'cancelled',
            'causer_type' => Customer::class,
            'causer_id' => $this->customer->id,
        ]);
    }

    /** @test */

    #[Test]
    public function customer_cannot_cancel_appointment_within_policy_window()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addHours(12), // Within 24-hour policy
            'status' => 'confirmed',
        ]);

        // Try to access cancel page
        $response = $this->get("/customer/appointments/{$appointment->id}/cancel");
        
        $response->assertStatus(200);
        $response->assertSee('Late Cancellation Notice');
        $response->assertSee('This appointment is within 24 hours and cannot be cancelled online.');
        $response->assertSee('Please call us at');
        $response->assertSee($this->branch->phone);
        
        // Try to force cancellation
        $response = $this->post("/customer/appointments/{$appointment->id}/cancel", [
            'reason' => 'emergency',
        ]);
        
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'This appointment cannot be cancelled online due to cancellation policy.',
        ]);
        
        // Appointment should remain unchanged
        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
    }

    /** @test */

    #[Test]
    public function customer_can_cancel_recurring_appointments()
    {
        Mail::fake();

        // Create recurring appointment series
        $recurringAppointments = [];
        for ($i = 0; $i < 4; $i++) {
            $recurringAppointments[] = Appointment::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'service_id' => $this->service->id,
                'start_time' => now()->addWeeks($i + 1)->setTime(10, 0),
                'status' => 'confirmed',
                'recurring_group_id' => 'REC001',
                'is_recurring' => true,
            ]);
        }

        $firstAppointment = $recurringAppointments[0];

        // Visit cancel page for recurring appointment
        $response = $this->get("/customer/appointments/{$firstAppointment->id}/cancel");
        
        $response->assertStatus(200);
        $response->assertSee('Cancel Recurring Appointment');
        $response->assertSee('This is part of a recurring series');
        
        // Options for recurring cancellation
        $response->assertSee('Cancel only this appointment');
        $response->assertSee('Cancel this and all future appointments');
        $response->assertSee('Cancel entire series');
        
        // Cancel only this appointment
        $response = $this->post("/customer/appointments/{$firstAppointment->id}/cancel", [
            'reason' => 'schedule_conflict',
            'recurring_action' => 'single',
        ]);
        
        $response->assertRedirect('/customer/appointments');
        
        // Verify only first appointment was cancelled
        $firstAppointment->refresh();
        $this->assertEquals('cancelled', $firstAppointment->status);
        
        // Others should remain confirmed
        foreach (array_slice($recurringAppointments, 1) as $apt) {
            $apt->refresh();
            $this->assertEquals('confirmed', $apt->status);
        }
        
        // Cancel future appointments
        $secondAppointment = $recurringAppointments[1];
        $response = $this->post("/customer/appointments/{$secondAppointment->id}/cancel", [
            'reason' => 'no_longer_needed',
            'recurring_action' => 'future',
        ]);
        
        // Verify current and future appointments cancelled
        foreach (array_slice($recurringAppointments, 1) as $apt) {
            $apt->refresh();
            $this->assertEquals('cancelled', $apt->status);
        }
        
        // Verify appropriate emails were sent
        Mail::assertQueued(AppointmentCancellationConfirmation::class, 4); // All 4 appointments
    }

    /** @test */

    #[Test]
    public function customer_can_request_appointment_reschedule()
    {
        Event::fake();
        Mail::fake();

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addDays(5)->setTime(14, 0),
            'status' => 'confirmed',
        ]);

        // Visit reschedule page
        $response = $this->get("/customer/appointments/{$appointment->id}/reschedule");
        
        $response->assertStatus(200);
        $response->assertSee('Reschedule Appointment');
        $response->assertSee('Current appointment:');
        $response->assertSee($appointment->start_time->format('l, F j at g:i A'));
        
        // Show available slots
        $response->assertSee('Select new date and time');
        $response->assertSee('Available times with Dr. Smith');
        
        // Select new time slot
        $newTime = now()->addDays(7)->setTime(15, 0);
        
        $response = $this->post("/customer/appointments/{$appointment->id}/reschedule", [
            'new_date' => $newTime->format('Y-m-d'),
            'new_time' => $newTime->format('H:i'),
            'reason' => 'schedule_conflict',
            'notes' => 'Previous appointment time no longer works',
        ]);
        
        $response->assertRedirect("/customer/appointments/{$appointment->id}");
        $response->assertSessionHas('success', 'Appointment rescheduled successfully.');
        
        // Verify appointment was updated
        $appointment->refresh();
        $this->assertEquals($newTime->format('Y-m-d H:i:00'), $appointment->start_time->format('Y-m-d H:i:s'));
        $this->assertEquals('confirmed', $appointment->status);
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals(1, $appointment->reschedule_count);
        
        // Verify confirmation email
        Mail::assertQueued(AppointmentRescheduledConfirmation::class, function ($mail) use ($appointment) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->appointment->id === $appointment->id;
        });
        
        // Verify event
        Event::assertDispatched(AppointmentRescheduled::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id &&
                   $event->rescheduledBy === 'customer';
        });
    }

    /** @test */

    #[Test]
    public function customer_can_download_appointment_calendar_file()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addDays(7)->setTime(10, 0),
            'end_time' => now()->addDays(7)->setTime(11, 0),
            'status' => 'confirmed',
            'confirmation_code' => 'ICS123',
        ]);

        // Download ICS file
        $response = $this->get("/customer/appointments/{$appointment->id}/calendar");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="appointment-ICS123.ics"');
        
        $icsContent = $response->getContent();
        
        // Verify ICS content
        $this->assertStringContainsString('BEGIN:VCALENDAR', $icsContent);
        $this->assertStringContainsString('VERSION:2.0', $icsContent);
        $this->assertStringContainsString('BEGIN:VEVENT', $icsContent);
        $this->assertStringContainsString('SUMMARY:Consultation with Dr. Smith', $icsContent);
        $this->assertStringContainsString('LOCATION:Main Clinic', $icsContent);
        $this->assertStringContainsString('DTSTART:', $icsContent);
        $this->assertStringContainsString('DTEND:', $icsContent);
        $this->assertStringContainsString('UID:ICS123@' . config('app.url'), $icsContent);
        $this->assertStringContainsString('END:VEVENT', $icsContent);
        $this->assertStringContainsString('END:VCALENDAR', $icsContent);
        
        // Test Google Calendar link
        $response = $this->get("/customer/appointments/{$appointment->id}/calendar/google");
        
        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('calendar.google.com/calendar/render', $redirectUrl);
        $this->assertStringContainsString('text=Consultation+with+Dr.+Smith', $redirectUrl);
    }

    /** @test */

    #[Test]
    public function customer_can_view_appointment_history_with_pagination()
    {
        // Create 25 appointments for pagination testing
        $appointments = Appointment::factory()->count(25)->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'start_time' => now()->subMonths(2),
        ]);

        $response = $this->get('/customer/appointments?filter=past');
        
        $response->assertStatus(200);
        
        // Should see pagination
        $response->assertSee('Showing 1 to 20 of 25 results');
        $response->assertSee('Next');
        $response->assertSee('page=2');
        
        // Visit page 2
        $response = $this->get('/customer/appointments?filter=past&page=2');
        
        $response->assertStatus(200);
        $response->assertSee('Showing 21 to 25 of 25 results');
        $response->assertSee('Previous');
    }

    /** @test */

    #[Test]
    public function customer_can_print_appointment_details()
    {
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'start_time' => now()->addWeek(),
            'status' => 'confirmed',
            'confirmation_code' => 'PRINT123',
        ]);

        $response = $this->get("/customer/appointments/{$appointment->id}/print");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        
        // Should have print-specific styling
        $response->assertSee('@media print');
        $response->assertSee('window.print()');
        
        // Should contain all appointment details
        $response->assertSee('Appointment Confirmation');
        $response->assertSee('PRINT123');
        $response->assertSee($this->company->name);
        $response->assertSee($appointment->start_time->format('l, F j, Y'));
        $response->assertSee('Please bring this confirmation to your appointment');
    }

    /** @test */

    #[Test]
    public function customer_receives_appointment_reminders()
    {
        Notification::fake();

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDay()->setTime(10, 0),
            'status' => 'confirmed',
            'reminder_sent' => false,
        ]);

        // Visit appointments page - should show reminder status
        $response = $this->get('/customer/appointments');
        
        $response->assertStatus(200);
        $response->assertSee('Reminder will be sent 24 hours before');
        
        // Simulate reminder cron job
        $this->artisan('appointments:send-reminders')->assertSuccessful();
        
        // Verify reminder was sent
        Notification::assertSentTo(
            $this->customer,
            \App\Notifications\AppointmentReminder::class,
            function ($notification) use ($appointment) {
                return $notification->appointment->id === $appointment->id;
            }
        );
        
        $appointment->refresh();
        $this->assertTrue($appointment->reminder_sent);
    }

    /** @test */

    #[Test]
    public function appointment_access_is_restricted_to_owner()
    {
        // Create appointment for different customer
        $otherCustomer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        $otherAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $otherCustomer->id,
        ]);

        // Try to access other customer's appointment
        $response = $this->get("/customer/appointments/{$otherAppointment->id}");
        
        $response->assertStatus(403);
        $response->assertSee('You do not have permission to view this appointment');
        
        // Try to cancel other customer's appointment
        $response = $this->post("/customer/appointments/{$otherAppointment->id}/cancel", [
            'reason' => 'test',
        ]);
        
        $response->assertStatus(403);
    }
}