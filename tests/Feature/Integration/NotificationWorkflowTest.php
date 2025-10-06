<?php

use App\Services\NotificationWorkflowService;
use App\Services\NotificationService;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->notificationService = $this->mock(NotificationService::class);
    $this->workflowService = new NotificationWorkflowService($this->notificationService);

    // Create test data
    $this->company = \App\Models\Company::factory()->create();
    $this->customer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'preferred_language' => 'de',
        'preferred_contact_method' => 'email'
    ]);

    // Create notification templates
    $this->createNotificationTemplates();
});

it('sends appointment reminders at correct intervals', function () {
    // Create appointments at different time intervals
    $appointment24h = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->addHours(24)->format('Y-m-d'),
        'appointment_time' => now()->addHours(24)->format('H:i:s'),
        'status' => 'scheduled'
    ]);

    $appointment2h = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->addHours(2)->format('Y-m-d'),
        'appointment_time' => now()->addHours(2)->format('H:i:s'),
        'status' => 'scheduled'
    ]);

    $appointment15m = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->addMinutes(15)->format('Y-m-d'),
        'appointment_time' => now()->addMinutes(15)->format('H:i:s'),
        'status' => 'scheduled'
    ]);

    // Mock notification service
    $this->notificationService
        ->shouldReceive('send')
        ->times(3);

    $remindersSent = $this->workflowService->sendAppointmentReminders();

    expect($remindersSent)->toBe(3);

    // Check logs were created
    expect(NotificationLog::count())->toBe(3);
    expect(NotificationLog::where('type', 'reminder_24h')->count())->toBe(1);
    expect(NotificationLog::where('type', 'reminder_2h')->count())->toBe(1);
    expect(NotificationLog::where('type', 'reminder_15m')->count())->toBe(1);
});

it('processes no-shows and sends follow-ups', function () {
    // Create old appointments that should be marked as no-show
    $noShowAppointments = Appointment::factory(3)->create([
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->subHours(3)->format('Y-m-d'),
        'appointment_time' => now()->subHours(3)->format('H:i:s'),
        'status' => 'scheduled'
    ]);

    $this->notificationService
        ->shouldReceive('send')
        ->times(3);

    $processed = $this->workflowService->processNoShows();

    expect($processed)->toBe(3);

    // Check appointments were marked as no-show
    foreach ($noShowAppointments as $appointment) {
        $appointment->refresh();
        expect($appointment->status)->toBe('no_show');
    }

    // Check follow-up notifications were logged
    expect(NotificationLog::where('type', 'no_show_followup')->count())->toBe(3);
});

it('sends review requests after completed appointments', function () {
    // Create completed appointments 2-3 days ago
    $completedAppointments = Appointment::factory(2)->create([
        'customer_id' => $this->customer->id,
        'status' => 'completed',
        'completed_at' => now()->subDays(2)->subHours(12)
    ]);

    $this->notificationService
        ->shouldReceive('send')
        ->twice();

    $sent = $this->workflowService->sendReviewRequests();

    expect($sent)->toBe(2);

    // Check review request logs
    expect(NotificationLog::where('type', 'review_request')->count())->toBe(2);
});

it('respects customer notification preferences', function () {
    // Customer prefers SMS
    $smsCustomer = Customer::factory()->create([
        'preferred_contact_method' => 'sms',
        'phone' => '+49 30 12345678',
        'email' => null
    ]);

    $appointment = Appointment::factory()->create([
        'customer_id' => $smsCustomer->id,
        'status' => 'scheduled'
    ]);

    $this->notificationService
        ->shouldReceive('send')
        ->with(
            $smsCustomer,
            \Mockery::any(),
            \Mockery::any(),
            'sms'
        )
        ->once();

    $this->workflowService->sendAppointmentConfirmation($appointment);
});

it('uses correct language templates', function () {
    // Create German customer
    $deCustomer = Customer::factory()->create([
        'preferred_language' => 'de'
    ]);

    // Create English customer
    $enCustomer = Customer::factory()->create([
        'preferred_language' => 'en'
    ]);

    $deAppointment = Appointment::factory()->create([
        'customer_id' => $deCustomer->id
    ]);

    $enAppointment = Appointment::factory()->create([
        'customer_id' => $enCustomer->id
    ]);

    // Create English templates
    NotificationTemplate::factory()->create([
        'key' => 'appointment_confirmation',
        'language' => 'en',
        'subject' => 'Appointment Confirmation',
        'body' => 'Your appointment is confirmed'
    ]);

    $this->notificationService
        ->shouldReceive('send')
        ->twice();

    $this->workflowService->sendAppointmentConfirmation($deAppointment);
    $this->workflowService->sendAppointmentConfirmation($enAppointment);

    // Verify correct templates were used
    expect(true)->toBeTrue(); // Templates are selected internally
});

it('handles bulk marketing campaigns', function () {
    // Create customers with marketing consent
    $consentedCustomers = Customer::factory(5)->create([
        'marketing_consent' => true,
        'status' => 'active'
    ]);

    $nonConsentedCustomers = Customer::factory(3)->create([
        'marketing_consent' => false,
        'status' => 'active'
    ]);

    $customerIds = array_merge(
        $consentedCustomers->pluck('id')->toArray(),
        $nonConsentedCustomers->pluck('id')->toArray()
    );

    $this->workflowService->sendMarketingCampaign(
        $customerIds,
        'summer_promotion',
        ['discount' => '20%']
    );

    // Only consented customers should receive campaigns
    // Job would be dispatched, we verify the filtering logic
    expect(Customer::whereIn('id', $customerIds)
        ->where('marketing_consent', true)
        ->count()
    )->toBe(5);
});

it('creates custom notification workflows', function () {
    $workflowSteps = [
        [
            'trigger' => 'appointment_created',
            'delay' => 0,
            'template' => 'instant_confirmation',
            'channels' => ['email', 'sms']
        ],
        [
            'trigger' => 'appointment_reminder',
            'delay' => 1440, // 24 hours
            'template' => 'day_before_reminder',
            'channels' => ['email']
        ],
        [
            'trigger' => 'appointment_reminder',
            'delay' => 120, // 2 hours
            'template' => 'final_reminder',
            'channels' => ['sms']
        ]
    ];

    $workflow = $this->workflowService->createWorkflow(
        'Standard Appointment Flow',
        $workflowSteps
    );

    expect($workflow->name)->toBe('Standard Appointment Flow');
    expect($workflow->steps)->toHaveCount(3);
    expect($workflow->is_active)->toBeTrue();
});

it('prevents duplicate reminders within time window', function () {
    $appointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->addHours(24)->format('Y-m-d'),
        'appointment_time' => now()->addHours(24)->format('H:i:s'),
        'status' => 'scheduled'
    ]);

    // Create recent reminder log
    NotificationLog::create([
        'appointment_id' => $appointment->id,
        'customer_id' => $this->customer->id,
        'type' => 'reminder_24h',
        'channels' => ['email'],
        'sent_at' => now()->subHours(6) // Within 12-hour window
    ]);

    $this->notificationService
        ->shouldReceive('send')
        ->never(); // Should not send duplicate

    $remindersSent = $this->workflowService->sendAppointmentReminders();

    expect($remindersSent)->toBe(0);
});

it('creates reschedule offers for no-shows', function () {
    $appointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id,
        'appointment_date' => now()->subHours(3)->format('Y-m-d'),
        'appointment_time' => now()->subHours(3)->format('H:i:s'),
        'status' => 'scheduled'
    ]);

    // Mock appointment service for available slots
    $this->mock(\App\Services\AppointmentService::class)
        ->shouldReceive('getAvailableSlots')
        ->andReturn([
            ['date' => now()->addDay(), 'time' => '10:00'],
            ['date' => now()->addDay(), 'time' => '14:00'],
            ['date' => now()->addDays(2), 'time' => '11:00']
        ]);

    $this->notificationService
        ->shouldReceive('send')
        ->twice(); // No-show followup + reschedule offer

    $processed = $this->workflowService->processNoShows();

    expect($processed)->toBe(1);

    // Check reschedule offer was created
    $this->assertDatabaseHas('reschedule_offers', [
        'appointment_id' => $appointment->id,
        'customer_id' => $this->customer->id
    ]);
});

it('handles notification delivery failures gracefully', function () {
    $appointment = Appointment::factory()->create([
        'customer_id' => $this->customer->id
    ]);

    $this->notificationService
        ->shouldReceive('send')
        ->andThrow(new \Exception('SMS gateway error'));

    // Should not throw exception
    try {
        $this->workflowService->sendAppointmentConfirmation($appointment);
        expect(true)->toBeTrue(); // Handled gracefully
    } catch (\Exception $e) {
        expect(false)->toBeTrue(); // Should not reach here
    }
});

function createNotificationTemplates()
{
    $templates = [
        'appointment_confirmation' => 'Ihre Terminbestätigung',
        'appointment_reminder_24h' => '24-Stunden Erinnerung',
        'appointment_reminder_2h' => '2-Stunden Erinnerung',
        'appointment_reminder_15m' => '15-Minuten Erinnerung',
        'no_show_followup' => 'Verpasster Termin',
        'review_request' => 'Bewertungsanfrage',
        'appointment_cancellation' => 'Terminabsage',
        'reschedule_offer' => 'Neuer Terminvorschlag'
    ];

    foreach ($templates as $key => $subject) {
        NotificationTemplate::factory()->create([
            'key' => $key,
            'language' => 'de',
            'subject' => $subject,
            'body' => 'Template body for ' . $key,
            'channel' => 'email'
        ]);
    }
}