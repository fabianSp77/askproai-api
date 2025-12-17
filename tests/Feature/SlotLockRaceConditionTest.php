<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Booking\SlotLockService;
use App\Services\Booking\AvailabilityWithLockService;
use App\Services\Metrics\ReservationMetricsCollector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SlotLockRaceConditionTest extends TestCase
{
    private SlotLockService $lockService;
    private AvailabilityWithLockService $lockWrapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockService = app(SlotLockService::class);
        $this->lockWrapper = app(AvailabilityWithLockService::class);

        // Clear Redis before each test
        Cache::flush();
    }

    /**
     * Test 1: Basic lock acquisition
     */
    public function test_can_acquire_lock_on_available_slot()
    {
        $result = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'test_call_1',
            customerPhone: '+4915112345678',
            metadata: ['customer_name' => 'Test Customer']
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('lock_key', $result);
        $this->assertStringContainsString('slot_lock:', $result['lock_key']);
    }

    /**
     * Test 2: Race condition prevention (core test)
     */
    public function test_prevents_race_condition_on_concurrent_bookings()
    {
        $companyId = 1;
        $serviceId = 31;
        $startTime = Carbon::parse('2025-11-24 10:00');
        $endTime = Carbon::parse('2025-11-24 10:30');

        // Customer A tries to book
        $lockA = $this->lockService->acquireLock(
            $companyId,
            $serviceId,
            $startTime,
            $endTime,
            'call_customer_a',
            '+4915111111111',
            ['customer_name' => 'Customer A']
        );

        // Customer B tries to book same slot (race condition!)
        $lockB = $this->lockService->acquireLock(
            $companyId,
            $serviceId,
            $startTime,
            $endTime,
            'call_customer_b',
            '+4915122222222',
            ['customer_name' => 'Customer B']
        );

        // First lock should succeed
        $this->assertTrue($lockA['success'], 'Customer A should acquire lock');
        $this->assertArrayHasKey('lock_key', $lockA);

        // Second lock should FAIL (race condition prevented!)
        $this->assertFalse($lockB['success'], 'Customer B should NOT acquire lock');
        $this->assertEquals('slot_locked', $lockB['reason']);
        $this->assertEquals('call_customer_a', $lockB['locked_by']);
    }

    /**
     * Test 3: Lock validation
     */
    public function test_validates_lock_ownership()
    {
        $lockResult = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'call_owner',
            customerPhone: '+4915112345678'
        );

        $lockKey = $lockResult['lock_key'];

        // Valid ownership check
        $validationOwner = $this->lockService->validateLock($lockKey, 'call_owner');
        $this->assertTrue($validationOwner['valid']);

        // Invalid ownership check (different call_id)
        $validationThief = $this->lockService->validateLock($lockKey, 'call_thief');
        $this->assertFalse($validationThief['valid']);
        $this->assertEquals('lock_ownership_mismatch', $validationThief['reason']);
    }

    /**
     * Test 4: Lock expiration
     */
    public function test_lock_expires_after_ttl()
    {
        // Mock short TTL for testing
        $lockResult = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'test_call',
            customerPhone: '+4915112345678'
        );

        $lockKey = $lockResult['lock_key'];

        // Lock should exist
        $this->assertTrue(Cache::has($lockKey));

        // Manually delete to simulate expiry
        Cache::forget($lockKey);

        // Lock should be expired
        $validation = $this->lockService->validateLock($lockKey, 'test_call');
        $this->assertFalse($validation['valid']);
        $this->assertEquals('lock_expired', $validation['reason']);
    }

    /**
     * Test 5: Lock release after booking
     */
    public function test_releases_lock_after_successful_booking()
    {
        $lockResult = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'test_call',
            customerPhone: '+4915112345678'
        );

        $lockKey = $lockResult['lock_key'];

        // Lock exists
        $this->assertTrue(Cache::has($lockKey));

        // Release lock
        $released = $this->lockService->releaseLock($lockKey, 'test_call', 123);
        $this->assertTrue($released);

        // Lock should be gone
        $this->assertFalse(Cache::has($lockKey));
    }

    /**
     * Test 6: Different slots don't conflict
     */
    public function test_different_slots_dont_conflict()
    {
        // Lock slot 1
        $lock1 = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'call_1',
            customerPhone: '+4915111111111'
        );

        // Lock slot 2 (different time)
        $lock2 = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 11:00'),
            endTime: Carbon::parse('2025-11-24 11:30'),
            callId: 'call_2',
            customerPhone: '+4915122222222'
        );

        // Both should succeed
        $this->assertTrue($lock1['success']);
        $this->assertTrue($lock2['success']);
    }

    /**
     * Test 7: Wrapper integration
     */
    public function test_wrapper_adds_lock_to_available_result()
    {
        $availabilityResult = [
            'success' => true,
            'available' => true,
            'message' => 'Slot available',
        ];

        $wrapped = $this->lockWrapper->wrapWithLock(
            $availabilityResult,
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'test_call',
            customerPhone: '+4915112345678'
        );

        $this->assertTrue($wrapped['available']);
        $this->assertArrayHasKey('lock_key', $wrapped);
        $this->assertTrue($wrapped['slot_locked']);
    }

    /**
     * Test 8: Wrapper detects race condition
     */
    public function test_wrapper_detects_race_condition()
    {
        // First booking locks the slot
        $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'call_first',
            customerPhone: '+4915111111111'
        );

        // Second booking tries to wrap (race condition!)
        $availabilityResult = [
            'success' => true,
            'available' => true,
            'message' => 'Slot available',
        ];

        $wrapped = $this->lockWrapper->wrapWithLock(
            $availabilityResult,
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'call_second',
            customerPhone: '+4915122222222'
        );

        // Should return "not available" (race detected)
        $this->assertFalse($wrapped['available']);
        $this->assertEquals('slot_just_taken', $wrapped['reason']);
        $this->assertTrue($wrapped['race_condition_detected']);
    }

    /**
     * Test 9: Compound service lock (multi-segment)
     */
    public function test_compound_service_locks_multiple_segments()
    {
        $parentToken = \Illuminate\Support\Str::uuid()->toString();

        // Lock segment 1
        $lock1 = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'call_compound',
            customerPhone: '+4915112345678',
            metadata: [
                'is_compound' => true,
                'compound_parent_token' => $parentToken,
                'segment_number' => 1,
                'total_segments' => 2,
            ]
        );

        // Lock segment 2 (same parent)
        $lock2 = $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:30'),
            endTime: Carbon::parse('2025-11-24 11:00'),
            callId: 'call_compound',
            customerPhone: '+4915112345678',
            metadata: [
                'is_compound' => true,
                'compound_parent_token' => $parentToken,
                'segment_number' => 2,
                'total_segments' => 2,
            ]
        );

        $this->assertTrue($lock1['success']);
        $this->assertTrue($lock2['success']);
    }

    /**
     * Test 10: Performance test (lock acquisition time)
     */
    public function test_lock_acquisition_is_fast()
    {
        $startTime = microtime(true);

        $this->lockService->acquireLock(
            companyId: 1,
            serviceId: 31,
            startTime: Carbon::parse('2025-11-24 10:00'),
            endTime: Carbon::parse('2025-11-24 10:30'),
            callId: 'test_call',
            customerPhone: '+4915112345678'
        );

        $duration = (microtime(true) - $startTime) * 1000; // ms

        // Lock acquisition should be < 100ms (Redis is fast!)
        $this->assertLessThan(100, $duration, "Lock acquisition took {$duration}ms (should be < 100ms)");
    }
}
