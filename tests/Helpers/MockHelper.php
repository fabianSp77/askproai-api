<?php

namespace Tests\Helpers;

use Mockery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Bus;

trait MockHelper
{
    /**
     * Mock external HTTP requests
     */
    protected function mockHttpRequest(string $url, array $response = [], int $status = 200): void
    {
        Http::fake([
            $url => Http::response($response, $status),
        ]);
    }

    /**
     * Mock multiple HTTP endpoints
     */
    protected function mockHttpEndpoints(array $endpoints): void
    {
        $fakes = [];
        
        foreach ($endpoints as $url => $config) {
            $response = $config['response'] ?? [];
            $status = $config['status'] ?? 200;
            $headers = $config['headers'] ?? [];
            
            $fakes[$url] = Http::response($response, $status, $headers);
        }
        
        Http::fake($fakes);
    }

    /**
     * Mock Cal.com API
     */
    protected function mockCalcomApi(): object
    {
        $mock = Mockery::mock(\App\Services\CalcomV2Service::class);
        $this->app->instance(\App\Services\CalcomV2Service::class, $mock);
        
        return $mock;
    }

    /**
     * Mock Retell API
     */
    protected function mockRetellApi(): object
    {
        $mock = Mockery::mock(\App\Services\RetellV2Service::class);
        $this->app->instance(\App\Services\RetellV2Service::class, $mock);
        
        return $mock;
    }

    /**
     * Mock email sending
     */
    protected function mockMail(): void
    {
        Mail::fake();
    }

    /**
     * Assert email was sent
     */
    protected function assertMailSent(string $mailable, ?callable $callback = null): void
    {
        Mail::assertSent($mailable, $callback);
    }

    /**
     * Assert email was not sent
     */
    protected function assertMailNotSent(string $mailable, ?callable $callback = null): void
    {
        Mail::assertNotSent($mailable, $callback);
    }

    /**
     * Mock queue jobs
     */
    protected function mockQueue(): void
    {
        Queue::fake();
    }

    /**
     * Assert job was pushed
     */
    protected function assertJobPushed(string $job, ?callable $callback = null): void
    {
        Queue::assertPushed($job, $callback);
    }

    /**
     * Assert job was not pushed
     */
    protected function assertJobNotPushed(string $job, ?callable $callback = null): void
    {
        Queue::assertNotPushed($job, $callback);
    }

    /**
     * Mock cache
     */
    protected function mockCache(): void
    {
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });
        
        Cache::shouldReceive('rememberForever')->andReturnUsing(function ($key, $callback) {
            return $callback();
        });
    }

    /**
     * Mock storage
     */
    protected function mockStorage(string $disk = 'local'): void
    {
        Storage::fake($disk);
    }

    /**
     * Assert file exists in storage
     */
    protected function assertStorageFileExists(string $path, string $disk = 'local'): void
    {
        Storage::disk($disk)->assertExists($path);
    }

    /**
     * Mock notifications
     */
    protected function mockNotifications(): void
    {
        Notification::fake();
    }

    /**
     * Assert notification was sent
     */
    protected function assertNotificationSent($notifiable, string $notification, ?callable $callback = null): void
    {
        Notification::assertSentTo($notifiable, $notification, $callback);
    }

    /**
     * Mock events
     */
    protected function mockEvents(array $events = []): void
    {
        Event::fake($events);
    }

    /**
     * Assert event was dispatched
     */
    protected function assertEventDispatched(string $event, ?callable $callback = null): void
    {
        Event::assertDispatched($event, $callback);
    }

    /**
     * Mock service
     */
    protected function mockService(string $service, array $methods = []): object
    {
        $mock = Mockery::mock($service);
        
        foreach ($methods as $method => $return) {
            if (is_callable($return)) {
                $mock->shouldReceive($method)->andReturnUsing($return);
            } else {
                $mock->shouldReceive($method)->andReturn($return);
            }
        }
        
        $this->app->instance($service, $mock);
        
        return $mock;
    }

    /**
     * Mock repository
     */
    protected function mockRepository(string $repository): object
    {
        $mock = Mockery::mock($repository);
        $this->app->instance($repository, $mock);
        
        return $mock;
    }

    /**
     * Spy on service
     */
    protected function spyOnService(string $service): object
    {
        $spy = Mockery::spy($service);
        $this->app->instance($service, $spy);
        
        return $spy;
    }

    /**
     * Mock time
     */
    protected function mockTime(string $time = '2025-01-01 12:00:00'): void
    {
        \Carbon\Carbon::setTestNow($time);
    }

    /**
     * Travel in time
     */
    protected function travelInTimeTo(string $time): void
    {
        \Carbon\Carbon::setTestNow($time);
    }

    /**
     * Travel forward in time
     */
    protected function travelForward(int $value, string $unit = 'hours'): void
    {
        \Carbon\Carbon::setTestNow(now()->add($value, $unit));
    }

    /**
     * Mock Bus for job batches
     */
    protected function mockBus(): void
    {
        Bus::fake();
    }

    /**
     * Assert job batch was dispatched
     */
    protected function assertBatchDispatched(callable $callback = null): void
    {
        Bus::assertBatched($callback);
    }

    /**
     * Mock config value
     */
    protected function mockConfig(string $key, $value): void
    {
        config([$key => $value]);
    }

    /**
     * Mock environment variable
     */
    protected function mockEnv(string $key, $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Create partial mock
     */
    protected function makePartialMock(string $class, array $methods = []): object
    {
        return Mockery::mock($class)->makePartial()->shouldReceive($methods)->getMock();
    }

    /**
     * Mock rate limiter
     */
    protected function mockRateLimiter(): void
    {
        \Illuminate\Support\Facades\RateLimiter::shouldReceive('tooManyAttempts')->andReturn(false);
        \Illuminate\Support\Facades\RateLimiter::shouldReceive('hit')->andReturn(1);
        \Illuminate\Support\Facades\RateLimiter::shouldReceive('availableIn')->andReturn(0);
    }

    /**
     * Assert method was called
     */
    protected function assertMethodCalled(object $mock, string $method, array $args = null, int $times = null): void
    {
        if ($args !== null && $times !== null) {
            $mock->shouldHaveReceived($method)->with(...$args)->times($times);
        } elseif ($args !== null) {
            $mock->shouldHaveReceived($method)->with(...$args);
        } elseif ($times !== null) {
            $mock->shouldHaveReceived($method)->times($times);
        } else {
            $mock->shouldHaveReceived($method);
        }
    }

    /**
     * Create mock expectation
     */
    protected function expectMock(object $mock, string $method): \Mockery\Expectation
    {
        return $mock->shouldReceive($method);
    }
}