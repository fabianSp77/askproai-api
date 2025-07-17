<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\PortalUser;
use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Cache;

class CacheApiTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private PortalUser $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        
        Cache::flush();
    }

    /** @test */
    public function authenticated_user_can_view_cache_statistics()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        // Populate some cache data
        Cache::put('test_key1', 'value1', 300);
        Cache::put('test_key2', 'value2', 300);
        Cache::get('test_key1'); // Hit
        Cache::get('test_key3'); // Miss

        // Act
        $response = $this->getJson('/api/cache/stats');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'hits',
                'misses',
                'hit_rate',
                'memory_usage',
                'keys',
                'uptime',
            ]);
    }

    /** @test */
    public function admin_can_clear_company_cache()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        // Add some cache entries
        Cache::tags(['company_' . $this->company->id])->put('test_data', 'value', 300);
        Cache::put('global_data', 'global_value', 300);

        // Act
        $response = $this->postJson('/api/cache/clear', [
            'scope' => 'company',
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Company cache cleared successfully',
            ]);

        // Verify company cache is cleared but global remains
        if (Cache::supportsTags()) {
            $this->assertNull(Cache::tags(['company_' . $this->company->id])->get('test_data'));
        }
        $this->assertEquals('global_value', Cache::get('global_data'));
    }

    /** @test */
    public function super_admin_can_clear_all_cache()
    {
        // Arrange
        $this->user->update(['role' => 'super_admin']);
        Sanctum::actingAs($this->user);
        
        Cache::put('test_data', 'value', 300);

        // Act
        $response = $this->postJson('/api/cache/clear', [
            'scope' => 'all',
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'All cache cleared successfully',
            ]);

        $this->assertNull(Cache::get('test_data'));
    }

    /** @test */
    public function regular_user_cannot_clear_cache()
    {
        // Arrange
        $this->user->update(['role' => 'user']);
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/cache/clear');

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function api_response_caching_works_correctly()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        // Act - First request should hit the controller
        $response1 = $this->getJson('/api/dashboard/stats?cache=1');
        $response1->assertOk();
        
        // Modify data that would change the response
        \App\Models\Appointment::factory()->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => now(),
        ]);
        
        // Second request with cache flag should return cached response
        $response2 = $this->getJson('/api/dashboard/stats?cache=1');
        
        // Assert - Responses should be identical (cached)
        $this->assertEquals($response1->json(), $response2->json());
    }

    /** @test */
    public function cache_headers_are_set_correctly()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->getJson('/api/services');

        // Assert
        $response->assertOk()
            ->assertHeader('X-Cache-Status')
            ->assertHeader('Cache-Control');
    }

    /** @test */
    public function warm_cache_endpoint_works()
    {
        // Arrange
        Sanctum::actingAs($this->user);

        // Act
        $response = $this->postJson('/api/cache/warm', [
            'types' => ['dashboard', 'availability'],
        ]);

        // Assert
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'warmed' => [
                    'dashboard' => true,
                    'availability' => true,
                ],
            ]);
    }

    /** @test */
    public function cache_invalidation_webhook_works()
    {
        // Arrange
        $secret = config('services.cache.webhook_secret', 'test-secret');
        $payload = [
            'event' => 'invalidate',
            'keys' => ['test_key1', 'test_key2'],
        ];
        
        Cache::put('test_key1', 'value1', 300);
        Cache::put('test_key2', 'value2', 300);
        
        $signature = hash_hmac('sha256', json_encode($payload), $secret);

        // Act
        $response = $this->postJson('/api/webhooks/cache/invalidate', $payload, [
            'X-Cache-Signature' => $signature,
        ]);

        // Assert
        $response->assertOk();
        $this->assertNull(Cache::get('test_key1'));
        $this->assertNull(Cache::get('test_key2'));
    }

    /** @test */
    public function rate_limiting_uses_cache_correctly()
    {
        // Arrange
        Sanctum::actingAs($this->user);
        
        // Act - Make requests up to rate limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/appointments');
            $response->assertOk();
        }
        
        // Next request should be rate limited
        $response = $this->getJson('/api/appointments');
        
        // Assert
        $response->assertStatus(429)
            ->assertHeader('X-RateLimit-Limit', 60)
            ->assertHeader('X-RateLimit-Remaining', 0)
            ->assertHeader('Retry-After');
        
        // Verify rate limit is stored in cache
        $rateLimitKey = 'rate_limit:' . sha1($this->user->id . '|' . request()->ip());
        $this->assertTrue(Cache::has($rateLimitKey));
    }

    /** @test */
    public function cache_tagging_isolates_company_data()
    {
        // Skip if tags not supported
        if (!Cache::supportsTags()) {
            $this->markTestSkipped('Cache driver does not support tags');
        }
        
        // Arrange
        $company2 = Company::factory()->create();
        $user2 = PortalUser::factory()->create(['company_id' => $company2->id]);
        
        // User 1 caches data
        Sanctum::actingAs($this->user);
        $this->getJson('/api/services'); // This should cache services for company 1
        
        // User 2 caches data
        Sanctum::actingAs($user2);
        $this->getJson('/api/services'); // This should cache services for company 2
        
        // Act - Clear company 1 cache
        Sanctum::actingAs($this->user);
        $this->postJson('/api/cache/clear', ['scope' => 'company']);
        
        // Assert - Company 2 cache should still exist
        Sanctum::actingAs($user2);
        $response = $this->getJson('/api/services');
        $response->assertHeader('X-Cache-Status', 'hit');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}