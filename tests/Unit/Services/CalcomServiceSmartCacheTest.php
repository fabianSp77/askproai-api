<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 🎯 PHASE 2.2: Unit Tests for Smart Cache Invalidation (2025-11-11)
 *
 * Tests the smartClearAvailabilityCache() method to ensure:
 * 1. Only affected date ranges are cleared (not 30 days)
 * 2. Only affected time ranges are cleared (not 24 hours)
 * 3. Only specific company/branch caches are cleared (tenant isolation)
 * 4. Cache key count is ~18 keys (not 300+ keys)
 * 5. Error handling works correctly
 */
class CalcomServiceSmartCacheTest extends TestCase
{
    use RefreshDatabase;

    protected CalcomService $calcomService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calcomService = new CalcomService();

        // Mock Cache facade to track operations
        Cache::spy();
    }

    /**
     * Test: Smart cache clears only 2 days (not 30 days)
     */
    public function test_smart_cache_clears_only_affected_dates(): void
    {
        // Setup: Appointment on 2025-11-12 14:30-15:00
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');
        $eventTypeId = 123;
        $teamId = 456;

        // Create mock service
        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'duration_minutes' => 30,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        // Execute
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        // Assert: Should clear only 2 days (2025-11-12 and 2025-11-13)
        // CalcomService layer: 2 days × 1 team = 2 keys
        // AlternativeFinder layer: 2 days × 3 hours (13,14,15) × 1 service = 6 keys
        // Total: 2 + 6 = 8 keys
        $this->assertLessThan(25, $clearedKeys, 'Smart cache should clear < 25 keys');
        $this->assertGreaterThan(5, $clearedKeys, 'Smart cache should clear > 5 keys');

        // Verify Cache::forget was called (not Cache::flush)
        Cache::shouldHaveReceived('forget')->atLeast(5);
    }

    /**
     * Test: Smart cache clears only affected hours (not all 24 hours)
     */
    public function test_smart_cache_clears_only_affected_hours(): void
    {
        // Setup: Appointment on 2025-11-12 14:30-15:00
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');
        $eventTypeId = 123;
        $teamId = 456;

        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'duration_minutes' => 30,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        // Execute
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        // Assert: Should clear only hours 13, 14, 15 (appointment 14:30 ± 1 hour buffer)
        // NOT all 10 business hours (9-18) or all 24 hours
        // Verify by checking cache key patterns
        Cache::shouldHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_.*_\d{4}-\d{2}-\d{2}-(13|14|15)_/'))
            ->atLeast(1);

        // Should NOT clear hours outside the range (e.g., hour 9 or hour 18)
        Cache::shouldNotHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_.*_\d{4}-\d{2}-\d{2}-09_/'));

        Cache::shouldNotHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_.*_\d{4}-\d{2}-\d{2}-18_/'));
    }

    /**
     * Test: Smart cache respects tenant isolation (company/branch filtering)
     */
    public function test_smart_cache_respects_tenant_isolation(): void
    {
        // Setup: Two services with same event type but different companies
        $eventTypeId = 123;
        $teamId = 456;

        $service1 = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        $service2 = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 2,
            'branch_id' => 2
        ]);

        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Execute: Clear cache for company 1 only
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service1->company_id,
            branchId: $service1->branch_id
        );

        // Assert: Should clear cache for company 1 only (not company 2)
        Cache::shouldHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_1_1_/'))
            ->atLeast(1);

        // Should NOT clear cache for company 2
        Cache::shouldNotHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_2_2_/'));
    }

    /**
     * Test: Cache key count is significantly less than broad clearing
     */
    public function test_smart_cache_key_count_is_optimized(): void
    {
        $eventTypeId = 123;
        $teamId = 456;

        // Single service scenario
        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'duration_minutes' => 30,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Execute: Smart cache
        $smartKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        // Assert: Smart cache should clear ~8-18 keys (not 300+)
        $this->assertLessThan(25, $smartKeys, 'Smart cache should clear < 25 keys');

        // Calculate expected broad clearing:
        // - CalcomService layer: 30 days × 1 team = 30 keys
        // - AlternativeFinder layer: 7 days × 10 hours × 1 service = 70 keys
        // - Total: 100 keys (for single team/service)
        $expectedBroadKeys = 100;

        // Assert: Smart cache should be at least 80% more efficient
        $efficiency = (1 - ($smartKeys / $expectedBroadKeys)) * 100;
        $this->assertGreaterThan(80, $efficiency, 'Smart cache should be > 80% more efficient');
    }

    /**
     * Test: Error handling doesn't break webhook processing
     */
    public function test_smart_cache_error_handling_is_non_blocking(): void
    {
        // Setup: Force cache error by using invalid cache key
        $eventTypeId = 123;
        $teamId = 456;

        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Mock Cache::forget to throw exception
        Cache::shouldReceive('forget')
            ->andThrow(new \Exception('Redis connection failed'));

        // Mock Log to capture error
        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->atLeast(0);

        // Execute: Should not throw exception (non-blocking)
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId
        );

        // Assert: Should return 0 keys cleared (but not throw exception)
        $this->assertEquals(0, $clearedKeys, 'Should return 0 keys on error');

        // Assert: Error should be logged
        Log::shouldHaveReceived('error')
            ->with(
                \Mockery::pattern('/Failed to clear .* cache/'),
                \Mockery::type('array')
            );
    }

    /**
     * Test: Edge case - appointment at midnight (hour boundary)
     */
    public function test_smart_cache_handles_midnight_appointment(): void
    {
        $eventTypeId = 123;
        $teamId = 456;

        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        // Appointment at midnight: 00:00-00:30
        $appointmentStart = Carbon::parse('2025-11-12 00:00:00');
        $appointmentEnd = Carbon::parse('2025-11-12 00:30:00');

        // Execute
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        // Assert: Should not crash, should clear hours 0,1 (23 is not valid since -1 from 0 = max(0, -1) = 0)
        $this->assertGreaterThan(0, $clearedKeys, 'Should clear > 0 keys for midnight appointment');

        // Verify hours 0 and 1 are cleared (not hour -1)
        Cache::shouldHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_.*_\d{4}-\d{2}-\d{2}-(00|01)_/'))
            ->atLeast(1);
    }

    /**
     * Test: Edge case - appointment at 23:00 (end of day)
     */
    public function test_smart_cache_handles_late_night_appointment(): void
    {
        $eventTypeId = 123;
        $teamId = 456;

        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        // Appointment at 23:00-23:30
        $appointmentStart = Carbon::parse('2025-11-12 23:00:00');
        $appointmentEnd = Carbon::parse('2025-11-12 23:30:00');

        // Execute
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        // Assert: Should not crash, should clear hours 22,23 (not hour 24)
        $this->assertGreaterThan(0, $clearedKeys, 'Should clear > 0 keys for late night appointment');

        // Verify hours 22 and 23 are cleared (not hour 24)
        Cache::shouldHaveReceived('forget')
            ->with(\Mockery::pattern('/cal_slots_.*_\d{4}-\d{2}-\d{2}-(22|23)_/'))
            ->atLeast(1);
    }

    /**
     * Test: Multi-service scenario (3 services, typical real-world)
     */
    public function test_smart_cache_handles_multiple_services(): void
    {
        $eventTypeId = 123;
        $teamId = 456;

        // Create 3 services with same event type (typical multi-branch scenario)
        $services = \App\Models\Service::factory()->count(3)->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'duration_minutes' => 30
        ]);

        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Execute: WITHOUT company/branch filtering (should clear all 3 services)
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId
            // No companyId/branchId = clear all services
        );

        // Assert: Should clear for all 3 services
        // CalcomService layer: 2 days × 1 team = 2 keys
        // AlternativeFinder layer: 2 days × 3 hours × 3 services = 18 keys
        // Total: 2 + 18 = 20 keys
        $this->assertGreaterThan(15, $clearedKeys, 'Should clear > 15 keys for 3 services');
        $this->assertLessThan(30, $clearedKeys, 'Should clear < 30 keys for 3 services');

        // Still significantly less than broad clearing (would be 300+ keys)
    }

    /**
     * Test: Return value is accurate key count
     */
    public function test_smart_cache_returns_accurate_key_count(): void
    {
        $eventTypeId = 123;
        $teamId = 456;

        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Execute
        $clearedKeys = $this->calcomService->smartClearAvailabilityCache(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        // Count actual Cache::forget calls
        $forgetCalls = Cache::spy()->shouldHaveReceived('forget')->times();

        // Assert: Return value should match actual cache operations
        $this->assertEquals($forgetCalls, $clearedKeys, 'Returned key count should match actual cache operations');
    }
}
