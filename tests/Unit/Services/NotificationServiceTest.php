<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\NotificationService;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use App\Models\Service;
use App\Mail\AppointmentConfirmationMail;
use App\Mail\AppointmentCancellationMail;
use App\Mail\AppointmentRescheduledMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mockery;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;
    private NotificationService $notificationService;
    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Staff $staff;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = new NotificationService();
        
        // Create test data
        $this->company = Company::factory()->create([
            'email_notifications_enabled' => true,
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'notification_email' => 'branch@example.com',
        ]);
        
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'customer@example.com',
            'phone' => '+491234567890',
        ]);
        
        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'staff@example.com',
        ]);
        
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Service',
            'duration' => 60,
        ]);
    }

    /** @test */
    public function it_sends_appointment_confirmation_email()
    {
        // Arrange
        Mail::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'appointment_datetime' => Carbon::now()->addDays(2),
            'status' => 'scheduled',
        ]);

        // Act
        $this->notificationService->sendAppointmentConfirmation($appointment);

        // Assert
        Mail::assertSent(AppointmentConfirmationMail::class, function ($mail) use ($appointment) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->appointment->id === $appointment->id;
        });
    }

    /** @test */
    public function it_does_not_send_email_when_notifications_are_disabled()
    {
        // Arrange
        Mail::fake();
        
        $this->company->update(['email_notifications_enabled' => false]);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => Carbon::now()->addDays(2),
        ]);

        // Act
        $this->notificationService->sendAppointmentConfirmation($appointment);

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_sends_appointment_reminders_at_correct_intervals()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        
        // Create appointments for different reminder intervals
        $appointment24h = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => Carbon::now()->addHours(24),
            'reminder_24h_sent' => false,
        ]);
        
        $appointment2h = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => Carbon::now()->addHours(2),
            'reminder_2h_sent' => false,
        ]);
        
        $appointment30min = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => Carbon::now()->addMinutes(30),
            'reminder_30min_sent' => false,
        ]);

        // Act
        $this->notificationService->sendAppointmentReminders();

        // Assert
        // Check that each appointment received the correct reminder
        $this->assertTrue($appointment24h->fresh()->reminder_24h_sent);
        $this->assertTrue($appointment2h->fresh()->reminder_2h_sent);
        $this->assertTrue($appointment30min->fresh()->reminder_30min_sent);
    }

    /** @test */
    public function it_sends_cancellation_notification()
    {
        // Arrange
        Mail::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'cancelled',
        ]);

        // Act
        $this->notificationService->sendAppointmentCancelledNotification($appointment);

        // Assert
        Mail::assertSent(AppointmentCancellationMail::class, function ($mail) use ($appointment) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->appointment->id === $appointment->id;
        });
    }

    /** @test */
    public function it_sends_rescheduled_notification_with_old_and_new_times()
    {
        // Arrange
        Mail::fake();
        
        $oldDatetime = Carbon::now()->addDays(2);
        $newDatetime = Carbon::now()->addDays(3);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => $newDatetime,
        ]);

        // Act
        $this->notificationService->sendAppointmentRescheduledNotification($appointment, $oldDatetime);

        // Assert
        Mail::assertSent(AppointmentRescheduledMail::class, function ($mail) use ($appointment, $oldDatetime) {
            return $mail->hasTo($this->customer->email) &&
                   $mail->appointment->id === $appointment->id &&
                   $mail->oldDatetime->equalTo($oldDatetime);
        });
    }

    /** @test */
    public function it_respects_customer_notification_preferences()
    {
        // Arrange
        Mail::fake();
        
        $this->customer->update([
            'email_notifications_enabled' => false,
            'sms_notifications_enabled' => true,
        ]);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
        ]);

        // Act
        $this->notificationService->sendAppointmentConfirmation($appointment);

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_handles_email_sending_failures_gracefully()
    {
        // Arrange
        Mail::shouldReceive('send')->andThrow(new \Exception('Mail server error'));
        Log::shouldReceive('error')->once();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
        ]);

        // Act & Assert (should not throw exception)
        $result = $this->notificationService->sendAppointmentConfirmation($appointment);
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_includes_ics_calendar_attachment_in_confirmation_email()
    {
        // Arrange
        Mail::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => Carbon::now()->addDays(2),
        ]);

        // Act
        $this->notificationService->sendAppointmentConfirmation($appointment);

        // Assert
        Mail::assertSent(AppointmentConfirmationMail::class, function ($mail) {
            $hasIcsAttachment = false;
            foreach ($mail->attachments as $attachment) {
                if (str_contains($attachment['name'], '.ics')) {
                    $hasIcsAttachment = true;
                    break;
                }
            }
            return $hasIcsAttachment;
        });
    }

    /** @test */
    public function it_does_not_send_duplicate_reminders()
    {
        // Arrange
        Mail::fake();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'appointment_datetime' => Carbon::now()->addHours(24),
            'reminder_24h_sent' => true, // Already sent
        ]);

        // Act
        $this->notificationService->sendAppointmentReminders();

        // Assert
        Mail::assertNothingSent();
    }

    /** @test */
    public function it_sends_notifications_in_customer_preferred_language()
    {
        // Arrange
        Mail::fake();
        
        $this->customer->update(['language' => 'de']);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
        ]);

        // Act
        $this->notificationService->sendAppointmentConfirmation($appointment);

        // Assert
        Mail::assertSent(AppointmentConfirmationMail::class, function ($mail) {
            return $mail->locale === 'de';
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}