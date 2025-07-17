<?php

namespace Tests\Helpers;

use Mockery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

/**
 * Helper for creating common mocks
 */
trait MockHelper
{
    /**
     * Mock external API responses
     */
    protected function mockRetellApi(): void
    {
        Http::fake([
            'api.retellai.com/v2/list-calls*' => Http::response([
                'calls' => [
                    [
                        'call_id' => 'call_test_123',
                        'from_number' => '+491234567890',
                        'to_number' => '+499876543210',
                        'duration' => 120,
                        'transcript' => 'Test transcript',
                        'created_at' => now()->toIso8601String(),
                    ],
                ],
            ], 200),
            
            'api.retellai.com/v2/get-call/*' => Http::response([
                'call_id' => 'call_test_123',
                'transcript' => 'Full transcript content',
                'recording_url' => 'https://example.com/recording.mp3',
                'metadata' => [],
            ], 200),
            
            'api.retellai.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);
    }

    /**
     * Mock Cal.com API responses
     */
    protected function mockCalcomApi(): void
    {
        Http::fake([
            'api.cal.com/v2/event-types*' => Http::response([
                'event_types' => [
                    [
                        'id' => 123,
                        'title' => 'Consultation',
                        'slug' => 'consultation',
                        'length' => 30,
                    ],
                ],
            ], 200),
            
            'api.cal.com/v2/bookings*' => Http::response([
                'bookings' => [
                    [
                        'id' => 456,
                        'title' => 'Consultation with John Doe',
                        'startTime' => now()->addDays(2)->toIso8601String(),
                        'endTime' => now()->addDays(2)->addMinutes(30)->toIso8601String(),
                        'attendees' => [
                            ['email' => 'john@example.com', 'name' => 'John Doe'],
                        ],
                    ],
                ],
            ], 200),
            
            'api.cal.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);
    }

    /**
     * Mock Stripe API responses
     */
    protected function mockStripeApi(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents*' => Http::response([
                'id' => 'pi_test_123',
                'amount' => 10000,
                'currency' => 'eur',
                'status' => 'succeeded',
            ], 200),
            
            'api.stripe.com/v1/customers*' => Http::response([
                'id' => 'cus_test_123',
                'email' => 'customer@example.com',
                'name' => 'Test Customer',
            ], 200),
            
            'api.stripe.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);
    }

    /**
     * Mock Redis cache operations
     */
    protected function mockCache(): \Mockery\MockInterface
    {
        $mock = Mockery::mock('cache');
        Cache::shouldReceive('driver')->andReturn($mock);
        
        return $mock;
    }

    /**
     * Setup cache expectations
     */
    protected function expectCacheOperations(array $operations): void
    {
        $cache = $this->mockCache();
        
        foreach ($operations as $operation) {
            switch ($operation['method']) {
                case 'get':
                    $cache->shouldReceive('get')
                        ->with($operation['key'])
                        ->andReturn($operation['return'] ?? null);
                    break;
                    
                case 'put':
                    $cache->shouldReceive('put')
                        ->with($operation['key'], $operation['value'], $operation['ttl'] ?? null)
                        ->andReturn(true);
                    break;
                    
                case 'forget':
                    $cache->shouldReceive('forget')
                        ->with($operation['key'])
                        ->andReturn(true);
                    break;
                    
                case 'flush':
                    $cache->shouldReceive('flush')
                        ->andReturn(true);
                    break;
            }
        }
    }

    /**
     * Mock file storage operations
     */
    protected function mockStorage(string $disk = 'local'): \Mockery\MockInterface
    {
        Storage::fake($disk);
        
        return Storage::disk($disk);
    }

    /**
     * Create mock service with common methods
     */
    protected function mockService(string $className, array $methods = []): \Mockery\MockInterface
    {
        $mock = Mockery::mock($className);
        
        foreach ($methods as $method => $return) {
            if (is_callable($return)) {
                $mock->shouldReceive($method)->andReturnUsing($return);
            } else {
                $mock->shouldReceive($method)->andReturn($return);
            }
        }
        
        $this->app->instance($className, $mock);
        
        return $mock;
    }

    /**
     * Mock queue jobs
     */
    protected function expectQueuedJobs(array $jobs): void
    {
        Queue::fake();
        
        // After your code runs, assert jobs were queued
        foreach ($jobs as $jobClass => $expectedCount) {
            Queue::assertPushed($jobClass, $expectedCount);
        }
    }

    /**
     * Mock mail sending
     */
    protected function expectMailSent(array $mailables): void
    {
        Mail::fake();
        
        // After your code runs, assert mails were sent
        foreach ($mailables as $mailableClass => $expectedCount) {
            Mail::assertSent($mailableClass, $expectedCount);
        }
    }

    /**
     * Mock notifications
     */
    protected function expectNotificationsSent(array $notifications): void
    {
        Notification::fake();
        
        // After your code runs, assert notifications were sent
        foreach ($notifications as $notificationClass => $expectedCount) {
            Notification::assertSentTimes($notificationClass, $expectedCount);
        }
    }

    /**
     * Create partial mock with spy
     */
    protected function spyOn(string $className): \Mockery\MockInterface
    {
        $spy = Mockery::spy($className);
        $this->app->instance($className, $spy);
        
        return $spy;
    }

    /**
     * Mock time for testing
     */
    protected function travelTo(string $datetime): void
    {
        \Carbon\Carbon::setTestNow($datetime);
    }

    /**
     * Reset time after testing
     */
    protected function travelBack(): void
    {
        \Carbon\Carbon::setTestNow();
    }

    /**
     * Mock environment variables
     */
    protected function withEnvironment(array $variables): void
    {
        foreach ($variables as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Create mock HTTP client with specific responses
     */
    protected function mockHttpSequence(array $responses): void
    {
        $sequence = Http::sequence();
        
        foreach ($responses as $response) {
            if ($response instanceof \Exception) {
                $sequence->push($response);
            } else {
                $sequence->push(
                    $response['body'] ?? [],
                    $response['status'] ?? 200,
                    $response['headers'] ?? []
                );
            }
        }
        
        Http::fake(['*' => $sequence]);
    }

    /**
     * Assert mock expectations
     */
    protected function assertMockExpectations(): void
    {
        Mockery::close();
    }
}