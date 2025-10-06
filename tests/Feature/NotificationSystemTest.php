<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\NotificationTemplate;
use App\Models\NotificationQueue;
use App\Models\NotificationProvider;
use App\Services\Notifications\NotificationManager;
use App\Services\Notifications\TemplateEngine;
use App\Services\Notifications\DeliveryOptimizer;
use App\Services\Notifications\AnalyticsTracker;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\WhatsAppChannel;
use App\Services\Notifications\Channels\PushChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationManager $notificationManager;
    protected TemplateEngine $templateEngine;
    protected DeliveryOptimizer $deliveryOptimizer;
    protected AnalyticsTracker $analyticsTracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationManager = new NotificationManager();
        $this->templateEngine = new TemplateEngine();
        $this->deliveryOptimizer = new DeliveryOptimizer();
        $this->analyticsTracker = new AnalyticsTracker();

        // Setup test providers
        $this->setupTestProviders();

        // Setup test templates
        $this->setupTestTemplates();
    }

    protected function setupTestProviders(): void
    {
        // SMS Provider
        NotificationProvider::create([
            'name' => 'Test SMS Provider',
            'type' => 'twilio',
            'channel' => 'sms',
            'credentials' => [
                'account_sid' => 'test_sid',
                'auth_token' => 'test_token',
                'from_number' => '+1234567890'
            ],
            'is_default' => true,
            'is_active' => true,
            'priority' => 1
        ]);

        // Email Provider (uses Laravel Mail)
        NotificationProvider::create([
            'name' => 'Test Email Provider',
            'type' => 'smtp',
            'channel' => 'email',
            'credentials' => [
                'host' => 'smtp.test.com',
                'port' => 587,
                'username' => 'test@test.com',
                'password' => 'test_password'
            ],
            'is_default' => true,
            'is_active' => true,
            'priority' => 1
        ]);

        // WhatsApp Provider
        NotificationProvider::create([
            'name' => 'Test WhatsApp Provider',
            'type' => 'twilio',
            'channel' => 'whatsapp',
            'credentials' => [
                'account_sid' => 'test_sid',
                'auth_token' => 'test_token',
                'from_number' => 'whatsapp:+1234567890'
            ],
            'is_default' => true,
            'is_active' => true,
            'priority' => 1
        ]);
    }

    protected function setupTestTemplates(): void
    {
        // Appointment Confirmation Template
        NotificationTemplate::create([
            'key' => 'appointment_confirmation',
            'name' => 'Appointment Confirmation',
            'channel' => 'email',
            'type' => 'confirmation',
            'subject' => json_encode([
                'de' => 'Ihre Terminbestätigung - {service}',
                'en' => 'Your Appointment Confirmation - {service}'
            ]),
            'content' => json_encode([
                'de' => 'Hallo {name},\n\nIhr Termin für {service} wurde bestätigt.\n\nDatum: {date:d.m.Y}\nZeit: {time}\nOrt: {location}\n\nVielen Dank!',
                'en' => 'Hello {name},\n\nYour appointment for {service} has been confirmed.\n\nDate: {date:M d, Y}\nTime: {time}\nLocation: {location}\n\nThank you!'
            ]),
            'variables' => [
                'name' => 'Customer name',
                'service' => 'Service name',
                'date' => 'Appointment date',
                'time' => 'Appointment time',
                'location' => 'Branch location'
            ],
            'is_active' => true,
            'priority' => 1
        ]);

        // SMS Reminder Template
        NotificationTemplate::create([
            'key' => 'appointment_reminder_sms',
            'name' => 'SMS Appointment Reminder',
            'channel' => 'sms',
            'type' => 'reminder',
            'content' => json_encode([
                'de' => 'Erinnerung: Ihr Termin {service} ist morgen um {time}. Ort: {location}',
                'en' => 'Reminder: Your {service} appointment is tomorrow at {time}. Location: {location}'
            ]),
            'variables' => [
                'service' => 'Service name',
                'time' => 'Appointment time',
                'location' => 'Branch location'
            ],
            'is_active' => true,
            'priority' => 2
        ]);
    }

    /** @test */
    public function it_can_send_email_notification()
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'notification_language' => 'de'
        ]);

        $result = $this->notificationManager->send(
            $customer,
            'appointment_confirmation',
            [
                'name' => $customer->full_name,
                'service' => 'Haircut',
                'date' => now()->addDays(1),
                'time' => '14:00',
                'location' => 'Main Branch'
            ],
            ['email']
        );

        $this->assertTrue($result['email']['success']);

        Mail::assertQueued(function ($mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }

    /** @test */
    public function it_can_send_sms_notification()
    {
        $customer = Customer::factory()->create([
            'phone' => '+49123456789',
            'notification_language' => 'de'
        ]);

        // Mock SMS channel
        $smsChannel = $this->createMock(SmsChannel::class);
        $smsChannel->expects($this->once())
            ->method('send')
            ->willReturn([
                'success' => true,
                'channel' => 'sms',
                'data' => ['message_id' => 'test_123']
            ]);

        $this->app->instance(SmsChannel::class, $smsChannel);

        $result = $this->notificationManager->send(
            $customer,
            'appointment_reminder_sms',
            [
                'service' => 'Haircut',
                'time' => '14:00',
                'location' => 'Main Branch'
            ],
            ['sms']
        );

        $this->assertTrue($result['sms']['success']);
    }

    /** @test */
    public function it_can_use_template_engine_with_variables()
    {
        $template = NotificationTemplate::find(1);

        $rendered = $this->templateEngine->render($template, [
            'name' => 'John Doe',
            'service' => 'Premium Haircut',
            'date' => Carbon::parse('2025-09-30'),
            'time' => '14:30',
            'location' => 'Downtown Branch'
        ], 'de');

        $this->assertStringContainsString('John Doe', $rendered['content']);
        $this->assertStringContainsString('Premium Haircut', $rendered['content']);
        $this->assertStringContainsString('30.09.2025', $rendered['content']);
        $this->assertStringContainsString('14:30', $rendered['content']);
        $this->assertStringContainsString('Downtown Branch', $rendered['content']);
    }

    /** @test */
    public function it_can_handle_multi_language_templates()
    {
        $template = NotificationTemplate::find(1);

        // Test German
        $renderedDe = $this->templateEngine->render($template, [
            'name' => 'Hans Müller',
            'service' => 'Haarschnitt',
            'date' => Carbon::parse('2025-09-30'),
            'time' => '14:30',
            'location' => 'Hauptfiliale'
        ], 'de');

        $this->assertStringContainsString('Ihre Terminbestätigung', $renderedDe['subject']);
        $this->assertStringContainsString('Hallo Hans Müller', $renderedDe['content']);

        // Test English
        $renderedEn = $this->templateEngine->render($template, [
            'name' => 'John Smith',
            'service' => 'Haircut',
            'date' => Carbon::parse('2025-09-30'),
            'time' => '14:30',
            'location' => 'Main Branch'
        ], 'en');

        $this->assertStringContainsString('Your Appointment Confirmation', $renderedEn['subject']);
        $this->assertStringContainsString('Hello John Smith', $renderedEn['content']);
    }

    /** @test */
    public function it_can_optimize_delivery_time()
    {
        $customer = Customer::factory()->create([
            'timezone' => 'Europe/Berlin'
        ]);

        // Create some historical data
        for ($i = 0; $i < 10; $i++) {
            NotificationQueue::create([
                'uuid' => Str::uuid(),
                'notifiable_type' => Customer::class,
                'notifiable_id' => $customer->id,
                'channel' => 'email',
                'type' => 'reminder',
                'data' => [],
                'recipient' => ['email' => $customer->email],
                'status' => 'delivered',
                'sent_at' => now()->subDays($i)->setHour(14),
                'delivered_at' => now()->subDays($i)->setHour(14)->addMinutes(5),
                'opened_at' => now()->subDays($i)->setHour(15)
            ]);
        }

        $optimalTime = $this->deliveryOptimizer->getOptimalSendTime(
            $customer,
            'email',
            'reminder'
        );

        $this->assertInstanceOf(Carbon::class, $optimalTime);
        $this->assertEquals(14, $optimalTime->hour); // Based on historical data
    }

    /** @test */
    public function it_respects_quiet_hours()
    {
        $customer = Customer::factory()->create();

        // Set quiet hours preference
        $customer->notificationPreferences()->create([
            'channel' => 'sms',
            'enabled' => true,
            'quiet_hours' => [
                'start' => '22:00',
                'end' => '08:00'
            ]
        ]);

        $sendTime = now()->setTime(23, 0); // 11 PM
        $shouldSend = $this->deliveryOptimizer->shouldSendNow($customer, 'sms', $sendTime);

        $this->assertFalse($shouldSend);

        $sendTime = now()->setTime(10, 0); // 10 AM
        $shouldSend = $this->deliveryOptimizer->shouldSendNow($customer, 'sms', $sendTime);

        $this->assertTrue($shouldSend);
    }

    /** @test */
    public function it_tracks_notification_analytics()
    {
        $notification = NotificationQueue::create([
            'uuid' => Str::uuid(),
            'notifiable_type' => Customer::class,
            'notifiable_id' => 1,
            'channel' => 'email',
            'type' => 'confirmation',
            'data' => [],
            'recipient' => ['email' => 'test@example.com'],
            'status' => 'pending'
        ]);

        // Track send event
        $this->analyticsTracker->trackSent($notification);
        $notification->refresh();
        $this->assertEquals('sent', $notification->status);
        $this->assertNotNull($notification->sent_at);

        // Track delivery event
        $this->analyticsTracker->trackDelivered($notification);
        $notification->refresh();
        $this->assertEquals('delivered', $notification->status);
        $this->assertNotNull($notification->delivered_at);

        // Track open event
        $this->analyticsTracker->trackOpened($notification);
        $notification->refresh();
        $this->assertEquals('opened', $notification->status);
        $this->assertNotNull($notification->opened_at);

        // Track click event
        $this->analyticsTracker->trackClicked($notification, 'https://example.com');
        $notification->refresh();
        $this->assertEquals('clicked', $notification->status);
        $this->assertNotNull($notification->clicked_at);
    }

    /** @test */
    public function it_handles_bulk_notifications()
    {
        $customers = Customer::factory(5)->create();

        $notifications = [];
        foreach ($customers as $customer) {
            $notifications[] = [
                'notifiable' => $customer,
                'type' => 'marketing',
                'data' => [
                    'subject' => 'Special Offer',
                    'content' => 'Get 20% off your next appointment!'
                ]
            ];
        }

        $results = $this->notificationManager->sendBulk($notifications, ['email']);

        $this->assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['email']['success']);
        }
    }

    /** @test */
    public function it_handles_notification_failures_with_retry()
    {
        $customer = Customer::factory()->create([
            'phone' => '+49123456789'
        ]);

        // Mock SMS channel to fail first time
        $smsChannel = $this->createMock(SmsChannel::class);
        $smsChannel->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                [
                    'success' => false,
                    'channel' => 'sms',
                    'error' => 'Provider temporarily unavailable'
                ],
                [
                    'success' => true,
                    'channel' => 'sms',
                    'data' => ['message_id' => 'test_456']
                ]
            );

        $this->app->instance(SmsChannel::class, $smsChannel);

        $notification = NotificationQueue::create([
            'uuid' => Str::uuid(),
            'notifiable_type' => Customer::class,
            'notifiable_id' => $customer->id,
            'channel' => 'sms',
            'type' => 'reminder',
            'data' => ['content' => 'Test message'],
            'recipient' => ['phone' => $customer->phone],
            'status' => 'pending',
            'attempts' => 0
        ]);

        // First attempt fails
        $result = $this->notificationManager->processNotification($notification);
        $this->assertFalse($result['success']);
        $this->assertEquals(1, $notification->fresh()->attempts);

        // Retry succeeds
        $result = $this->notificationManager->processNotification($notification->fresh());
        $this->assertTrue($result['success']);
        $this->assertEquals('sent', $notification->fresh()->status);
    }

    /** @test */
    public function it_calculates_notification_costs()
    {
        // SMS cost calculation
        $smsChannel = new SmsChannel();
        $smsCost = $smsChannel->estimateCost(100);
        $this->assertEquals(2.0, $smsCost); // 100 * 0.02

        // WhatsApp cost calculation
        $whatsappChannel = new WhatsAppChannel();
        $whatsappCost = $whatsappChannel->estimateCost(100);
        $this->assertEquals(1.0, $whatsappCost); // 100 * 0.01

        // Email is free
        $emailChannel = new EmailChannel();
        $emailCost = $emailChannel->estimateCost(1000);
        $this->assertEquals(0.0, $emailCost);
    }

    /** @test */
    public function it_generates_notification_reports()
    {
        // Create test data
        $startDate = now()->subDays(7);
        $endDate = now();

        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i);

            // Create notifications for each day
            for ($j = 0; $j < 10; $j++) {
                NotificationQueue::create([
                    'uuid' => Str::uuid(),
                    'notifiable_type' => Customer::class,
                    'notifiable_id' => 1,
                    'channel' => $j < 5 ? 'email' : 'sms',
                    'type' => 'reminder',
                    'data' => [],
                    'recipient' => [],
                    'status' => $j < 8 ? 'delivered' : 'failed',
                    'sent_at' => $date,
                    'delivered_at' => $j < 8 ? $date->copy()->addMinutes(5) : null,
                    'cost' => $j >= 5 ? 0.02 : 0.00,
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
        }

        $report = $this->analyticsTracker->generateReport($startDate, $endDate);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('by_channel', $report);
        $this->assertArrayHasKey('by_type', $report);
        $this->assertArrayHasKey('daily_stats', $report);
        $this->assertArrayHasKey('cost_analysis', $report);

        $this->assertEquals(70, $report['summary']['total_sent']);
        $this->assertEquals(56, $report['summary']['total_delivered']);
        $this->assertEquals(14, $report['summary']['total_failed']);
        $this->assertEquals(80.0, $report['summary']['delivery_rate']);
    }

    /** @test */
    public function it_handles_unsubscribe_requests()
    {
        $customer = Customer::factory()->create([
            'email' => 'unsubscribe@example.com'
        ]);

        // Subscribe to notifications first
        $customer->notificationPreferences()->create([
            'channel' => 'email',
            'enabled' => true,
            'types' => ['marketing', 'reminder']
        ]);

        // Process unsubscribe
        $token = hash_hmac('sha256', $customer->id . ':email', config('app.key'));
        $result = $this->notificationManager->processUnsubscribe($token, 'email', 'marketing');

        $this->assertTrue($result);

        // Check preference was updated
        $preference = $customer->notificationPreferences()->where('channel', 'email')->first();
        $this->assertNotContains('marketing', $preference->types);
        $this->assertContains('reminder', $preference->types);
    }

    /** @test */
    public function it_validates_notification_channels()
    {
        $customer = Customer::factory()->create([
            'email' => null, // No email
            'phone' => '+49123456789'
        ]);

        $availableChannels = $this->notificationManager->getAvailableChannels($customer);

        $this->assertNotContains('email', $availableChannels);
        $this->assertContains('sms', $availableChannels);
    }

    /** @test */
    public function it_queues_notifications_for_scheduled_sending()
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $scheduledTime = now()->addHours(2);

        $result = $this->notificationManager->schedule(
            $customer,
            'reminder',
            ['content' => 'Scheduled reminder'],
            $scheduledTime,
            ['email']
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('notification_queue', [
            'notifiable_type' => Customer::class,
            'notifiable_id' => $customer->id,
            'status' => 'scheduled',
            'scheduled_at' => $scheduledTime
        ]);

        Queue::assertPushed(\App\Jobs\ProcessScheduledNotifications::class);
    }
}