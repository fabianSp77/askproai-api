<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\NotificationConfiguration;
use App\Models\NotificationQueue;
use App\Services\Notifications\NotificationManager;
use App\Services\Notifications\TemplateEngine;
use App\Services\Notifications\DeliveryOptimizer;
use App\Services\Notifications\AnalyticsTracker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationManagerHierarchicalConfigTest extends TestCase
{
    use DatabaseTransactions;

    protected NotificationManager $manager;
    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Staff $staff;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create hierarchical entities
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create(['company_id' => $this->company->id]);
        $this->staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Mock dependencies
        $templateEngine = $this->createMock(TemplateEngine::class);
        $optimizer = $this->createMock(DeliveryOptimizer::class);
        $analytics = $this->createMock(AnalyticsTracker::class);

        $optimizer->method('getOptimalSendTime')->willReturn(now());
        $analytics->method('trackSent')->willReturn(null);
        $analytics->method('trackFailed')->willReturn(null);

        $this->manager = new NotificationManager($templateEngine, $optimizer, $analytics);

        Queue::fake();
        Event::fake();
    }

    /** @test */
    public function it_resolves_config_at_staff_level()
    {
        // Create config at staff level
        NotificationConfiguration::create([
            'configurable_type' => Staff::class,
            'configurable_id' => $this->staff->id,
            'event_type' => 'appointment_reminder',
            'channel' => 'whatsapp',
            'fallback_channel' => 'email',
            'is_enabled' => true,
            'retry_count' => 3,
            'retry_delay_minutes' => 10,
        ]);

        // Send notification to staff
        $result = $this->manager->send(
            $this->staff,
            'appointment_reminder',
            ['message' => 'Test']
        );

        $this->assertEquals('success', $result['status']);

        // Verify WhatsApp channel was used (from staff config)
        $notification = NotificationQueue::latest()->first();
        $this->assertEquals('whatsapp', $notification->channel);
        $this->assertEquals('email', $notification->metadata['fallback_channel']);
    }

    /** @test */
    public function it_resolves_config_at_service_level_when_staff_has_none()
    {
        // Create config at service level only
        NotificationConfiguration::create([
            'configurable_type' => Service::class,
            'configurable_id' => $this->service->id,
            'event_type' => 'appointment_confirmation',
            'channel' => 'sms',
            'fallback_channel' => 'whatsapp',
            'is_enabled' => true,
        ]);

        // Send to customer (will resolve via appointment → service)
        $result = $this->manager->send(
            $this->customer,
            'appointment_confirmation',
            ['message' => 'Test']
        );

        $notification = NotificationQueue::latest()->first();
        $this->assertEquals('sms', $notification->channel);
    }

    /** @test */
    public function it_resolves_config_at_branch_level_when_service_has_none()
    {
        // Create config at branch level only
        NotificationConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'event_type' => 'appointment_cancelled',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        $result = $this->manager->send(
            $this->staff,
            'appointment_cancelled',
            ['message' => 'Test']
        );

        $notification = NotificationQueue::latest()->first();
        $this->assertEquals('email', $notification->channel);
    }

    /** @test */
    public function it_resolves_config_at_company_level_as_fallback()
    {
        // Create config at company level only
        NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'appointment_rescheduled',
            'channel' => 'push',
            'is_enabled' => true,
        ]);

        $result = $this->manager->send(
            $this->customer,
            'appointment_rescheduled',
            ['message' => 'Test']
        );

        $notification = NotificationQueue::latest()->first();
        $this->assertEquals('push', $notification->channel);
    }

    /** @test */
    public function it_prioritizes_staff_config_over_service_config()
    {
        // Create configs at multiple levels
        NotificationConfiguration::create([
            'configurable_type' => Service::class,
            'configurable_id' => $this->service->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'is_enabled' => true,
        ]);

        NotificationConfiguration::create([
            'configurable_type' => Staff::class,
            'configurable_id' => $this->staff->id,
            'event_type' => 'test_event',
            'channel' => 'whatsapp',
            'is_enabled' => true,
        ]);

        $result = $this->manager->send(
            $this->staff,
            'test_event',
            ['message' => 'Test']
        );

        // Should use staff config (whatsapp), not service config (sms)
        $notification = NotificationQueue::latest()->first();
        $this->assertEquals('whatsapp', $notification->channel);
    }

    /** @test */
    public function it_uses_system_defaults_when_no_config_exists()
    {
        // No configs created
        $result = $this->manager->send(
            $this->customer,
            'some_event',
            ['message' => 'Test']
        );

        // Should use email as default (from getPreferredChannels)
        $notification = NotificationQueue::latest()->first();
        $this->assertEquals('email', $notification->channel);
    }

    /** @test */
    public function it_attempts_fallback_channel_on_failure()
    {
        // Create config with fallback
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'fallback_channel' => 'email',
            'is_enabled' => true,
        ]);

        // Send notification
        $this->manager->send(
            $this->customer,
            'test_event',
            ['message' => 'Test'],
            null,
            ['immediate' => true]
        );

        $notification = NotificationQueue::where('channel', 'sms')->first();

        // Simulate failure - manually call handleFailure via reflection
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('handleFailure');
        $method->setAccessible(true);
        $method->invoke($this->manager, $notification, 'SMS provider error');

        // Should create fallback notification with email channel
        $this->assertDatabaseHas('notification_queues', [
            'channel' => 'email',
            'type' => 'test_event',
        ]);

        // Original should be marked as failed_with_fallback
        $notification->refresh();
        $this->assertEquals('failed_with_fallback', $notification->status);
    }

    /** @test */
    public function it_calculates_exponential_retry_delay()
    {
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'is_enabled' => true,
            'retry_count' => 5,
            'retry_delay_minutes' => 5,
            'metadata' => ['retry_strategy' => 'exponential'],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Exponential: pow(2, attempts) * baseDelay
        $this->assertEquals(5, $method->invoke($this->manager, $config, 0));   // 2^0 * 5 = 5
        $this->assertEquals(10, $method->invoke($this->manager, $config, 1));  // 2^1 * 5 = 10
        $this->assertEquals(20, $method->invoke($this->manager, $config, 2));  // 2^2 * 5 = 20
        $this->assertEquals(40, $method->invoke($this->manager, $config, 3));  // 2^3 * 5 = 40
    }

    /** @test */
    public function it_calculates_linear_retry_delay()
    {
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'is_enabled' => true,
            'retry_delay_minutes' => 10,
            'metadata' => ['retry_strategy' => 'linear'],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Linear: baseDelay * (attempts + 1)
        $this->assertEquals(10, $method->invoke($this->manager, $config, 0));  // 10 * 1 = 10
        $this->assertEquals(20, $method->invoke($this->manager, $config, 1));  // 10 * 2 = 20
        $this->assertEquals(30, $method->invoke($this->manager, $config, 2));  // 10 * 3 = 30
    }

    /** @test */
    public function it_calculates_fibonacci_retry_delay()
    {
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'is_enabled' => true,
            'retry_delay_minutes' => 5,
            'metadata' => ['retry_strategy' => 'fibonacci'],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Fibonacci: fib(attempts) * baseDelay
        $this->assertEquals(5, $method->invoke($this->manager, $config, 0));   // 1 * 5 = 5
        $this->assertEquals(5, $method->invoke($this->manager, $config, 1));   // 1 * 5 = 5
        $this->assertEquals(10, $method->invoke($this->manager, $config, 2));  // 2 * 5 = 10
        $this->assertEquals(15, $method->invoke($this->manager, $config, 3));  // 3 * 5 = 15
        $this->assertEquals(25, $method->invoke($this->manager, $config, 4));  // 5 * 5 = 25
    }

    /** @test */
    public function it_respects_max_retry_delay_cap()
    {
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'is_enabled' => true,
            'retry_delay_minutes' => 10,
            'metadata' => [
                'retry_strategy' => 'exponential',
                'max_retry_delay_minutes' => 60, // Cap at 60 minutes
            ],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Without cap: 2^6 * 10 = 640 minutes
        // With 60 min cap: should return 60
        $this->assertEquals(60, $method->invoke($this->manager, $config, 6));
    }

    /** @test */
    public function it_uses_constant_retry_delay()
    {
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'is_enabled' => true,
            'retry_delay_minutes' => 15,
            'metadata' => ['retry_strategy' => 'constant'],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Constant: always return baseDelay
        $this->assertEquals(15, $method->invoke($this->manager, $config, 0));
        $this->assertEquals(15, $method->invoke($this->manager, $config, 1));
        $this->assertEquals(15, $method->invoke($this->manager, $config, 5));
    }

    /** @test */
    public function it_extracts_context_from_staff()
    {
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('extractContext');
        $method->setAccessible(true);

        $context = $method->invoke($this->manager, $this->staff);

        $this->assertEquals($this->staff->id, $context['staff_id']);
        $this->assertEquals($this->branch->id, $context['branch_id']);
        $this->assertEquals($this->company->id, $context['company_id']);
    }

    /** @test */
    public function it_extracts_context_from_customer()
    {
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('extractContext');
        $method->setAccessible(true);

        $context = $method->invoke($this->manager, $this->customer);

        $this->assertEquals($this->company->id, $context['company_id']);
    }

    /** @test */
    public function it_stores_config_id_in_notification_metadata()
    {
        $config = NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'test_event',
            'channel' => 'sms',
            'fallback_channel' => 'email',
            'is_enabled' => true,
        ]);

        $this->manager->send(
            $this->customer,
            'test_event',
            ['message' => 'Test']
        );

        $notification = NotificationQueue::latest()->first();

        $this->assertNotNull($notification->metadata);
        $this->assertEquals($config->id, $notification->metadata['notification_config_id']);
        $this->assertEquals('email', $notification->metadata['fallback_channel']);
    }

    /** @test */
    public function it_does_not_fallback_if_already_a_fallback_notification()
    {
        // Create original notification with fallback metadata
        $notification = NotificationQueue::create([
            'uuid' => \Str::uuid(),
            'notifiable_type' => Customer::class,
            'notifiable_id' => $this->customer->id,
            'channel' => 'email',
            'type' => 'test_event',
            'data' => ['message' => 'Test'],
            'recipient' => ['email' => $this->customer->email],
            'language' => 'de',
            'priority' => 5,
            'status' => 'processing',
            'metadata' => [
                'fallback_from_notification_id' => 123,
                'fallback_from_channel' => 'sms',
                'fallback_channel' => 'whatsapp',
            ],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('handleFailure');
        $method->setAccessible(true);
        $method->invoke($this->manager, $notification, 'Email failed');

        // Should NOT create another fallback (no whatsapp notification)
        $this->assertDatabaseMissing('notification_queues', [
            'channel' => 'whatsapp',
        ]);

        // Should just schedule retry
        $notification->refresh();
        $this->assertEquals('pending', $notification->status);
    }
}
