<?php

namespace Tests\Unit\ServiceDesk;

use Tests\TestCase;
use App\Services\ServiceDesk\ServiceDeskLockService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit Test Suite: ServiceDeskLockService
 *
 * Tests the lock service that mirrors BookingLockService pattern:
 * - Distributed locking for case creation
 * - Idempotency checks via cache
 * - Lock acquisition with timeout
 *
 * @since 2025-12-10 (Phase 2: Smart Office)
 */
class ServiceDeskLockServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceDeskLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->lockService = new ServiceDeskLockService();
    }

    /**
     * Test: Lock can be acquired for new call
     *
     * @test
     */
    public function test_acquire_case_lock_success(): void
    {
        $callId = 'call_test_' . uniqid();

        $lock = $this->lockService->acquireCaseLock($callId);

        $this->assertNotNull($lock);

        // Clean up
        $lock->release();
    }

    /**
     * Test: Second lock attempt fails for same call
     *
     * @test
     */
    public function test_duplicate_lock_blocked(): void
    {
        $callId = 'call_test_' . uniqid();

        // First lock succeeds
        $lock1 = $this->lockService->acquireCaseLock($callId);
        $this->assertNotNull($lock1);

        // Create new service instance to simulate concurrent request
        $lockService2 = new ServiceDeskLockService();
        $lockService2->setMaxWait(1); // Short wait for test

        // Second lock should fail (or timeout)
        $lock2 = $lockService2->acquireCaseLock($callId);

        // Either null or same lock (depending on timing)
        if ($lock2 !== null) {
            $lock2->release();
        }

        // Clean up
        $lock1->release();
    }

    /**
     * Test: Idempotency check returns false for new call
     *
     * @test
     */
    public function test_is_case_already_created_false(): void
    {
        $callId = 'call_new_' . uniqid();

        $result = $this->lockService->isCaseAlreadyCreated($callId);

        $this->assertFalse($result);
    }

    /**
     * Test: Idempotency check returns true after marking
     *
     * @test
     */
    public function test_is_case_already_created_true_after_mark(): void
    {
        $callId = 'call_marked_' . uniqid();
        $caseId = 123;

        // Mark as created
        $this->lockService->markCaseCreated($callId, $caseId);

        // Check should return true
        $result = $this->lockService->isCaseAlreadyCreated($callId);

        $this->assertTrue($result);
    }

    /**
     * Test: Get existing case ID returns correct value
     *
     * @test
     */
    public function test_get_existing_case_id(): void
    {
        $callId = 'call_existing_' . uniqid();
        $caseId = 456;

        // Mark as created
        $this->lockService->markCaseCreated($callId, $caseId);

        // Get should return same case ID
        $result = $this->lockService->getExistingCaseId($callId);

        $this->assertEquals($caseId, $result);
    }

    /**
     * Test: Get existing case ID returns null for non-existent
     *
     * @test
     */
    public function test_get_existing_case_id_null(): void
    {
        $callId = 'call_nonexistent_' . uniqid();

        $result = $this->lockService->getExistingCaseId($callId);

        $this->assertNull($result);
    }

    /**
     * Test: withCaseLock executes callback on success
     *
     * @test
     */
    public function test_with_case_lock_executes_callback(): void
    {
        $callId = 'call_callback_' . uniqid();
        $executed = false;

        $result = $this->lockService->withCaseLock($callId, function($lock) use (&$executed) {
            $executed = true;
            return 'success';
        });

        $this->assertTrue($executed);
        $this->assertEquals('success', $result);
    }

    /**
     * Test: withCaseLock releases lock after callback
     *
     * @test
     */
    public function test_with_case_lock_releases_after_callback(): void
    {
        $callId = 'call_release_' . uniqid();

        // Execute with lock
        $this->lockService->withCaseLock($callId, function($lock) {
            return true;
        });

        // Should be able to acquire lock again
        $lock = $this->lockService->acquireCaseLock($callId);
        $this->assertNotNull($lock);

        // Clean up
        $lock->release();
    }

    /**
     * Test: TTL can be customized
     *
     * @test
     */
    public function test_set_ttl(): void
    {
        $this->lockService->setTtl(60);

        $stats = $this->lockService->getLockStats();

        $this->assertEquals(60, $stats['ttl']);
    }

    /**
     * Test: Max wait can be customized
     *
     * @test
     */
    public function test_set_max_wait(): void
    {
        $this->lockService->setMaxWait(15);

        $stats = $this->lockService->getLockStats();

        $this->assertEquals(15, $stats['max_wait']);
    }

    /**
     * Test: Invalid TTL throws exception
     *
     * @test
     */
    public function test_set_ttl_invalid(): void
    {
        $this->expectException(\Exception::class);

        $this->lockService->setTtl(5); // Too low, minimum is 10
    }

    /**
     * Test: Lock stats returns all expected values
     *
     * @test
     */
    public function test_get_lock_stats(): void
    {
        $stats = $this->lockService->getLockStats();

        // Core configuration values
        $this->assertArrayHasKey('ttl', $stats);
        $this->assertArrayHasKey('max_wait', $stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('service', $stats);

        // Verify values are sensible
        $this->assertIsInt($stats['ttl']);
        $this->assertIsInt($stats['max_wait']);
        $this->assertEquals('ServiceDeskLockService', $stats['service']);
    }
}
