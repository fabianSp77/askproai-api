<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RateLimiter\ApiRateLimiter;
use App\Services\CalcomV2Service;
use App\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CriticalOptimizationsTest extends TestCase
{
    use RefreshDatabase;

    protected ApiRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new ApiRateLimiter();
        Cache::flush(); // Clear rate limit cache
    }

    /** @test */
    public function it_enforces_rate_limits_per_minute()
    {
        $service = 'calcom';
        $identifier = 'test-user';
        
        // Should allow up to 60 requests per minute for Cal.com
        for ($i = 0; $i < 60; $i++) {
            $this->assertTrue($this->rateLimiter->attempt($service, $identifier));
        }
        
        // 61st request should fail
        $this->expectException(RateLimitExceededException::class);
        $this->rateLimiter->attempt($service, $identifier);
    }

    /** @test */
    public function it_applies_exponential_backoff()
    {
        $service = 'retell';
        $identifier = 'test-api';
        
        // First backoff: 3 minutes (base for retell)
        $delay1 = $this->rateLimiter->applyBackoff($service, $identifier, 1);
        $this->assertEquals(3, $delay1);
        
        // Second backoff: 6 minutes (3 * 2^1)
        $delay2 = $this->rateLimiter->applyBackoff($service, $identifier, 2);
        $this->assertEquals(6, $delay2);
        
        // Third backoff: 12 minutes (3 * 2^2)
        $delay3 = $this->rateLimiter->applyBackoff($service, $identifier, 3);
        $this->assertEquals(12, $delay3);
    }

    /** @test */
    public function it_provides_usage_statistics()
    {
        $service = 'calcom';
        $identifier = 'test-stats';
        
        // Make 10 requests
        for ($i = 0; $i < 10; $i++) {
            $this->rateLimiter->attempt($service, $identifier);
        }
        
        $usage = $this->rateLimiter->getUsage($service, $identifier);
        
        $this->assertEquals(10, $usage['minute']['current']);
        $this->assertEquals(60, $usage['minute']['limit']);
        $this->assertEquals(50, $usage['minute']['remaining']);
        $this->assertFalse($usage['in_backoff']);
    }

    /** @test */
    public function it_validates_calcom_api_responses()
    {
        // Mock HTTP response
        Http::fake([
            'api.cal.com/v2/slots/available' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-06-19' => [
                            ['time' => '10:00'],
                            ['time' => '11:00'],
                        ]
                    ]
                ]
            ], 200)
        ]);
        
        $service = new CalcomV2Service('test-api-key');
        $result = $service->checkAvailability(123, '2025-06-19');
        
        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']['slots']);
    }

    /** @test */
    public function it_handles_rate_limit_responses_gracefully()
    {
        // Mock 429 response
        Http::fake([
            'api.cal.com/v2/teams' => Http::response('Rate limit exceeded', 429, [
                'Retry-After' => '120'
            ])
        ]);
        
        $service = new CalcomV2Service('test-api-key');
        $result = $service->getTeams();
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Rate limit exceeded', $result['error']);
        $this->assertEquals(120, $result['retry_after']);
    }

    /** @test */
    public function it_validates_input_parameters()
    {
        $service = new CalcomV2Service('test-api-key');
        
        // Invalid event type ID
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event type ID');
        $service->checkAvailability(-1, '2025-06-19');
    }

    /** @test */
    public function it_validates_date_format()
    {
        $service = new CalcomV2Service('test-api-key');
        
        // Invalid date format
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        $service->checkAvailability(123, '19-06-2025');
    }

    /** @test */
    public function it_validates_timezone()
    {
        $service = new CalcomV2Service('test-api-key');
        
        // Invalid timezone
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timezone');
        $service->checkAvailability(123, '2025-06-19', 'Invalid/Timezone');
    }

    /** @test */
    public function it_handles_invalid_json_responses()
    {
        // Mock invalid JSON response
        Http::fake([
            'api.cal.com/v2/teams' => Http::response('Not JSON content', 200)
        ]);
        
        $service = new CalcomV2Service('test-api-key');
        $result = $service->getTeams();
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Unexpected response format from Cal.com', $result['error']);
    }
}