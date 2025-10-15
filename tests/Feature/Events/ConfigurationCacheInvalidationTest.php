<?php

namespace Tests\Feature\Events;

use Tests\TestCase;
use App\Models\Company;
use App\Models\PolicyConfiguration;
use App\Models\User;
use App\Events\ConfigurationUpdated;
use App\Listeners\InvalidateConfigurationCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * Test Cache Invalidation for Configuration Changes
 *
 * @package Tests\Feature\Events
 * @group events
 * @group cache
 */
class ConfigurationCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected InvalidateConfigurationCache $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);
        $this->listener = new InvalidateConfigurationCache();
    }

    /** @test */
    public function it_invalidates_cache_when_configuration_is_updated()
    {
        // Set up cache
        $cacheKey = "company:{$this->company->id}:config";
        Cache::put($cacheKey, ['test' => 'value'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Create event
        $event = new ConfigurationUpdated(
            companyId: (string) $this->company->id,
            modelType: PolicyConfiguration::class,
            modelId: 1,
            configKey: 'hours_before',
            oldValue: 24,
            newValue: 48,
        );

        // Handle event
        $this->listener->handleUpdated($event);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_clears_multiple_cache_tags_for_company()
    {
        $companyId = (string) $this->company->id;

        // Set up multiple caches
        $caches = [
            "company:{$companyId}:config",
            "company:{$companyId}:policies",
            "company:{$companyId}:settings",
        ];

        foreach ($caches as $key) {
            Cache::put($key, ['test' => 'value'], 3600);
        }

        // All caches should exist
        foreach ($caches as $key) {
            $this->assertTrue(Cache::has($key));
        }

        // Create event
        $event = new ConfigurationUpdated(
            companyId: $companyId,
            modelType: PolicyConfiguration::class,
            modelId: 1,
            configKey: 'hours_before',
            oldValue: 24,
            newValue: 48,
        );

        // Handle event
        $this->listener->handleUpdated($event);

        // All caches should be cleared
        foreach ($caches as $key) {
            $this->assertFalse(Cache::has($key));
        }
    }

    /** @test */
    public function it_handles_cache_invalidation_errors_gracefully()
    {
        // Create event with invalid company ID
        $event = new ConfigurationUpdated(
            companyId: 'invalid-id',
            modelType: PolicyConfiguration::class,
            modelId: 999,
            configKey: 'test',
            oldValue: 'old',
            newValue: 'new',
        );

        // Should not throw exception
        try {
            $this->listener->handleUpdated($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Cache invalidation should handle errors gracefully');
        }
    }

    /** @test */
    public function it_clears_configuration_specific_cache()
    {
        $configKey = 'api_key';
        $cacheKey = "config:{$configKey}";

        Cache::put($cacheKey, 'test-api-key', 3600);
        $this->assertTrue(Cache::has($cacheKey));

        $event = new ConfigurationUpdated(
            companyId: (string) $this->company->id,
            modelType: PolicyConfiguration::class,
            modelId: 1,
            configKey: $configKey,
            oldValue: 'old-key',
            newValue: 'new-key',
        );

        $this->listener->handleUpdated($event);

        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_clears_filament_navigation_badge_cache()
    {
        $badgeCacheKey = "filament:badge:policy_configurations:{$this->company->id}";

        Cache::put($badgeCacheKey, 5, 3600);
        $this->assertTrue(Cache::has($badgeCacheKey));

        $event = new ConfigurationUpdated(
            companyId: (string) $this->company->id,
            modelType: PolicyConfiguration::class,
            modelId: 1,
            configKey: 'hours_before',
            oldValue: 24,
            newValue: 48,
        );

        $this->listener->handleUpdated($event);

        // Badge cache should be cleared
        $this->assertFalse(Cache::has($badgeCacheKey));
    }
}
