<?php

namespace Tests\Unit;

use App\Models\NotificationConfiguration;
use App\Services\Notifications\NotificationManager;
use App\Services\Notifications\TemplateEngine;
use App\Services\Notifications\DeliveryOptimizer;
use App\Services\Notifications\AnalyticsTracker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for NotificationManager hierarchical config integration
 *
 * Tests core functionality without complex entity factories
 */
class NotificationManagerConfigIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected NotificationManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $templateEngine = $this->createMock(TemplateEngine::class);
        $optimizer = $this->createMock(DeliveryOptimizer::class);
        $analytics = $this->createMock(AnalyticsTracker::class);

        $optimizer->method('getOptimalSendTime')->willReturn(now());
        // Analytics methods are void, no need to mock return values

        $this->manager = new NotificationManager($templateEngine, $optimizer, $analytics);
    }

    /** @test */
    public function it_calculates_exponential_retry_delay_correctly()
    {
        $config = new NotificationConfiguration([
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
        $this->assertEquals(80, $method->invoke($this->manager, $config, 4));  // 2^4 * 5 = 80
    }

    /** @test */
    public function it_calculates_linear_retry_delay_correctly()
    {
        $config = new NotificationConfiguration([
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
        $this->assertEquals(40, $method->invoke($this->manager, $config, 3));  // 10 * 4 = 40
    }

    /** @test */
    public function it_calculates_fibonacci_retry_delay_correctly()
    {
        $config = new NotificationConfiguration([
            'retry_delay_minutes' => 5,
            'metadata' => ['retry_strategy' => 'fibonacci'],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Fibonacci: fib(attempts) * baseDelay
        // Sequence: 1, 1, 2, 3, 5, 8, 13...
        $this->assertEquals(5, $method->invoke($this->manager, $config, 0));   // 1 * 5 = 5
        $this->assertEquals(5, $method->invoke($this->manager, $config, 1));   // 1 * 5 = 5
        $this->assertEquals(10, $method->invoke($this->manager, $config, 2));  // 2 * 5 = 10
        $this->assertEquals(15, $method->invoke($this->manager, $config, 3));  // 3 * 5 = 15
        $this->assertEquals(25, $method->invoke($this->manager, $config, 4));  // 5 * 5 = 25
        $this->assertEquals(40, $method->invoke($this->manager, $config, 5));  // 8 * 5 = 40
    }

    /** @test */
    public function it_respects_max_retry_delay_cap()
    {
        $config = new NotificationConfiguration([
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
        $delay = $method->invoke($this->manager, $config, 6);
        $this->assertEquals(60, $delay);
        $this->assertLessThanOrEqual(60, $delay);
    }

    /** @test */
    public function it_uses_constant_retry_delay()
    {
        $config = new NotificationConfiguration([
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
        $this->assertEquals(15, $method->invoke($this->manager, $config, 10));
    }

    /** @test */
    public function it_defaults_to_exponential_strategy()
    {
        $config = new NotificationConfiguration([
            'retry_delay_minutes' => 5,
            'metadata' => [], // No strategy specified
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // Should default to exponential
        $this->assertEquals(5, $method->invoke($this->manager, $config, 0));   // 2^0 * 5 = 5
        $this->assertEquals(10, $method->invoke($this->manager, $config, 1));  // 2^1 * 5 = 10
        $this->assertEquals(20, $method->invoke($this->manager, $config, 2));  // 2^2 * 5 = 20
    }

    /** @test */
    public function it_uses_config_delay_when_provided()
    {
        $config = new NotificationConfiguration([
            'retry_delay_minutes' => 25,
            'metadata' => ['retry_strategy' => 'constant'],
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        $this->assertEquals(25, $method->invoke($this->manager, $config, 0));
    }

    /** @test */
    public function it_falls_back_to_system_default_when_no_config()
    {
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('calculateRetryDelay');
        $method->setAccessible(true);

        // No config provided, should use system default (5 minutes)
        $delay = $method->invoke($this->manager, null, 1);
        $this->assertEquals(10, $delay); // 2^1 * 5 = 10
    }

    /** @test */
    public function it_calculates_fibonacci_sequence_correctly()
    {
        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('fibonacciBackoff');
        $method->setAccessible(true);

        $baseDelay = 1;

        // Verify Fibonacci sequence: 1, 1, 2, 3, 5, 8, 13, 21
        $this->assertEquals(1, $method->invoke($this->manager, $baseDelay, 0));
        $this->assertEquals(1, $method->invoke($this->manager, $baseDelay, 1));
        $this->assertEquals(2, $method->invoke($this->manager, $baseDelay, 2));
        $this->assertEquals(3, $method->invoke($this->manager, $baseDelay, 3));
        $this->assertEquals(5, $method->invoke($this->manager, $baseDelay, 4));
        $this->assertEquals(8, $method->invoke($this->manager, $baseDelay, 5));
        $this->assertEquals(13, $method->invoke($this->manager, $baseDelay, 6));
        $this->assertEquals(21, $method->invoke($this->manager, $baseDelay, 7));
    }

    /** @test */
    public function it_applies_max_delay_cap_across_all_strategies()
    {
        $maxDelay = 100;

        $strategies = ['exponential', 'linear', 'fibonacci'];

        foreach ($strategies as $strategy) {
            $config = new NotificationConfiguration([
                'retry_delay_minutes' => 50,
                'metadata' => [
                    'retry_strategy' => $strategy,
                    'max_retry_delay_minutes' => $maxDelay,
                ],
            ]);

            $reflection = new \ReflectionClass($this->manager);
            $method = $reflection->getMethod('calculateRetryDelay');
            $method->setAccessible(true);

            // Test high attempt count to ensure cap is applied
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $delay = $method->invoke($this->manager, $config, $attempt);
                $this->assertLessThanOrEqual($maxDelay, $delay,
                    "Strategy '{$strategy}' exceeded max delay at attempt {$attempt}");
            }
        }
    }

    /** @test */
    public function getNotificationConfig_returns_null_when_no_config_in_metadata()
    {
        $notification = new \App\Models\NotificationQueue([
            'uuid' => \Str::uuid(),
            'notifiable_type' => 'App\Models\Customer',
            'notifiable_id' => 1,
            'channel' => 'email',
            'type' => 'test',
            'data' => [],
            'recipient' => [],
            'language' => 'de',
            'priority' => 5,
            'status' => 'pending',
            'metadata' => null,
        ]);

        $reflection = new \ReflectionClass($this->manager);
        $method = $reflection->getMethod('getNotificationConfig');
        $method->setAccessible(true);

        $config = $method->invoke($this->manager, $notification);
        $this->assertNull($config);
    }
}
