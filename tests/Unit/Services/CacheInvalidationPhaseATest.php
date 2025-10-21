<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CalcomService;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Phase A+ Unit Tests - Cache Invalidation for Race Condition Fix
 *
 * Tests that clearAvailabilityCacheForEventType() clears BOTH cache layers:
 * - Layer 1: CalcomService cache (calcom:slots:*)
 * - Layer 2: AppointmentAlternativeFinder cache (cal_slots_*)
 */
class CacheInvalidationPhaseATest extends TestCase
{
    use RefreshDatabase;

    protected CalcomService $calcomService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test time
        Carbon::setTestNow(Carbon::parse('2025-10-20 10:00:00'));

        // Clear all caches before each test
        Cache::flush();

        $this->calcomService = new CalcomService();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that clearAvailabilityCacheForEventType() clears CalcomService cache (Layer 1)
     */
    public function test_clears_calcom_service_cache_layer()
    {
        $eventTypeId = 123;
        $teamId = 1;

        // Setup: Populate cache with test data
        $today = Carbon::today();
        for ($i = 0; $i < 3; $i++) {
            $date = $today->copy()->addDays($i)->format('Y-m-d');
            $cacheKey = "calcom:slots:{$teamId}:{$eventTypeId}:{$date}:{$date}";
            Cache::put($cacheKey, ['test' => 'data'], 600);
        }

        // Verify cache is populated
        $testKey = "calcom:slots:{$teamId}:{$eventTypeId}:{$today->format('Y-m-d')}:{$today->format('Y-m-d')}";
        $this->assertNotNull(Cache::get($testKey), 'Cache should be populated before clearing');

        // Execute: Clear cache
        $this->calcomService->clearAvailabilityCacheForEventType($eventTypeId, $teamId);

        // Assert: Cache should be cleared
        $this->assertNull(Cache::get($testKey), 'CalcomService cache Layer 1 should be cleared');
    }

    /**
     * Test that clearAvailabilityCacheForEventType() clears AppointmentAlternativeFinder cache (Layer 2)
     *
     * This is the CRITICAL fix for Phase A+ - prevents race conditions
     */
    public function test_clears_alternative_finder_cache_layer()
    {
        $eventTypeId = 123;
        $teamId = 1;

        // Setup: Create a Service that uses this event type
        $service = Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 0,
        ]);

        // Populate AppointmentAlternativeFinder cache (Layer 2)
        $today = Carbon::today();
        $companyId = $service->company_id;
        $branchId = $service->branch_id;

        $hour = 13;
        $startTime = $today->copy()->setTime($hour, 0);
        $endTime = $startTime->copy()->addHours(1);

        $altCacheKey = sprintf(
            'cal_slots_%d_%d_%d_%s_%s',
            $companyId,
            $branchId,
            $eventTypeId,
            $startTime->format('Y-m-d-H'),
            $endTime->format('Y-m-d-H')
        );

        Cache::put($altCacheKey, ['slots' => ['13:00', '13:30']], 600);

        // Verify cache is populated
        $this->assertNotNull(Cache::get($altCacheKey), 'AlternativeFinder cache should be populated before clearing');

        // Execute: Clear cache
        $this->calcomService->clearAvailabilityCacheForEventType($eventTypeId, $teamId);

        // Assert: AppointmentAlternativeFinder cache (Layer 2) should be cleared
        $this->assertNull(Cache::get($altCacheKey), 'AlternativeFinder cache Layer 2 should be cleared to prevent race conditions');
    }

    /**
     * Test backward compatibility: clearAvailabilityCacheForEventType() works without teamId
     *
     * Webhook handlers call this method without teamId - must still work
     */
    public function test_backward_compatibility_without_team_id()
    {
        $eventTypeId = 123;

        // Setup: Create a Service with teamId
        $service = Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => 1,
            'company_id' => 1,
            'branch_id' => 0,
        ]);

        // Populate cache
        $today = Carbon::today();
        $cacheKey = "calcom:slots:1:{$eventTypeId}:{$today->format('Y-m-d')}:{$today->format('Y-m-d')}";
        Cache::put($cacheKey, ['test' => 'data'], 600);

        $this->assertNotNull(Cache::get($cacheKey), 'Cache should be populated');

        // Execute: Call WITHOUT teamId (backward compatibility)
        $this->calcomService->clearAvailabilityCacheForEventType($eventTypeId);

        // Assert: Should still clear cache by auto-detecting teamId from Service
        $this->assertNull(Cache::get($cacheKey), 'Cache should be cleared even without explicit teamId');
    }

    /**
     * Test multi-tenant isolation: clearAvailabilityCacheForEventType() clears all affected tenants
     */
    public function test_clears_cache_for_all_affected_tenants()
    {
        $eventTypeId = 123;

        // Setup: Create 2 services (different companies) using same event type
        $service1 = Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => 1,
            'company_id' => 1,
            'branch_id' => 0,
        ]);

        $service2 = Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => 2,
            'company_id' => 2,
            'branch_id' => 0,
        ]);

        // Populate cache for both tenants
        $today = Carbon::today();
        $key1 = "calcom:slots:1:{$eventTypeId}:{$today->format('Y-m-d')}:{$today->format('Y-m-d')}";
        $key2 = "calcom:slots:2:{$eventTypeId}:{$today->format('Y-m-d')}:{$today->format('Y-m-d')}";

        Cache::put($key1, ['tenant' => 1], 600);
        Cache::put($key2, ['tenant' => 2], 600);

        // Execute: Clear cache without specific teamId
        $this->calcomService->clearAvailabilityCacheForEventType($eventTypeId);

        // Assert: BOTH tenants' caches should be cleared
        $this->assertNull(Cache::get($key1), 'Tenant 1 cache should be cleared');
        $this->assertNull(Cache::get($key2), 'Tenant 2 cache should be cleared');
    }

    /**
     * Test race condition scenario: User A books, User B should see fresh data
     *
     * This simulates the exact bug that Phase A+ fixes
     */
    public function test_race_condition_prevention()
    {
        $eventTypeId = 123;
        $teamId = 1;

        // Setup: Create service
        $service = Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 0,
        ]);

        // Simulate: User A and User B both check availability (cache gets populated)
        $today = Carbon::today();
        $hour = 13;
        $startTime = $today->copy()->setTime($hour, 0);
        $endTime = $startTime->copy()->addHours(1);

        // Layer 1 cache
        $layer1Key = "calcom:slots:{$teamId}:{$eventTypeId}:{$today->format('Y-m-d')}:{$today->format('Y-m-d')}";
        Cache::put($layer1Key, ['slots' => ['13:00', '13:30']], 60);

        // Layer 2 cache (AlternativeFinder)
        $layer2Key = sprintf(
            'cal_slots_%d_%d_%d_%s_%s',
            $service->company_id,
            $service->branch_id,
            $eventTypeId,
            $startTime->format('Y-m-d-H'),
            $endTime->format('Y-m-d-H')
        );
        Cache::put($layer2Key, ['slots' => ['13:00', '13:30']], 300);

        // Verify: Both layers cached
        $this->assertNotNull(Cache::get($layer1Key), 'Layer 1 should be cached');
        $this->assertNotNull(Cache::get($layer2Key), 'Layer 2 should be cached');

        // Execute: User A books 13:00 → Cache invalidation triggered
        $this->calcomService->clearAvailabilityCacheForEventType($eventTypeId, $teamId);

        // Assert: User B should NOT see stale data
        $this->assertNull(Cache::get($layer1Key), 'Layer 1 cache cleared - User B gets fresh data');
        $this->assertNull(Cache::get($layer2Key), 'Layer 2 cache cleared - NO RACE CONDITION!');
    }

    /**
     * Test performance: Cache clearing should complete quickly
     */
    public function test_cache_clearing_performance()
    {
        $eventTypeId = 123;
        $teamId = 1;

        // Setup: Create service
        Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 0,
        ]);

        // Measure performance
        $startTime = microtime(true);

        $this->calcomService->clearAvailabilityCacheForEventType($eventTypeId, $teamId);

        $duration = (microtime(true) - $startTime) * 1000; // ms

        // Assert: Should complete in <500ms (optimized with 7 days + business hours only)
        $this->assertLessThan(500, $duration, 'Cache clearing should be fast (<500ms)');
    }
}
