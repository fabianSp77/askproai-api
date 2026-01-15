<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Resilience\AppointmentBookingCircuitBreaker;
use App\Services\Resilience\CircuitOpenException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Mockery;

/**
 * AppointmentBookingCircuitBreaker Test Suite
 *
 * Comprehensive tests for circuit breaker pattern implementation to prevent
 * cascading failures in appointment booking system.
 *
 * Coverage:
 * - Circuit state transitions (CLOSED → OPEN → HALF_OPEN → CLOSED)
 * - Failure threshold enforcement
 * - Cooldown period behavior
 * - Success threshold in HALF_OPEN state
 * - Redis state persistence
 * - Database state persistence
 * - Multiple circuit isolation
 * - Fast fail behavior
 * - Statistics tracking
 */
class AppointmentBookingCircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentBookingCircuitBreaker $circuitBreaker;
    private string $testCircuitKey = 'test_circuit:service:123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->circuitBreaker = new AppointmentBookingCircuitBreaker();

        // Clear Redis before each test
        Redis::connection()->flushdb();

        // Clear circuit breaker states table
        DB::table('circuit_breaker_states')->truncate();
    }

    // ============================================================
    // STATE TRANSITIONS - CLOSED → OPEN
    // ============================================================

    /**
     * @test
     * Circuit opens after reaching failure threshold
     */
    public function it_opens_circuit_after_failure_threshold_reached()
    {
        // Arrange: Operation that always fails
        $operation = function () {
            throw new \Exception('Service failure');
        };

        // Act: Execute operation 3 times (failure threshold)
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
                // Expected failures
            }
        }

        // Assert: Circuit is now OPEN
        $state = $this->circuitBreaker->getState($this->testCircuitKey);
        $this->assertEquals('open', $state);
    }

    /**
     * @test
     * Circuit stays closed with successful operations
     */
    public function it_stays_closed_with_successful_operations()
    {
        // Arrange: Operation that succeeds
        $operation = function () {
            return 'success';
        };

        // Act: Execute multiple successful operations
        for ($i = 0; $i < 10; $i++) {
            $result = $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            $this->assertEquals('success', $result);
        }

        // Assert: Circuit remains CLOSED
        $state = $this->circuitBreaker->getState($this->testCircuitKey);
        $this->assertEquals('closed', $state);
    }

    /**
     * @test
     * Circuit stays closed with intermittent failures below threshold
     */
    public function it_stays_closed_with_failures_below_threshold()
    {
        // Arrange: Operation that fails twice then succeeds
        $attemptCount = 0;
        $operation = function () use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount <= 2) {
                throw new \Exception('Transient failure');
            }
            return 'success';
        };

        // Act: Execute with some failures
        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        // Success resets counter
        $result = $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);

        // Assert: Circuit still CLOSED (failures reset by success)
        $state = $this->circuitBreaker->getState($this->testCircuitKey);
        $this->assertEquals('closed', $state);
        $this->assertEquals('success', $result);
    }

    // ============================================================
    // STATE TRANSITIONS - OPEN → HALF_OPEN
    // ============================================================

    /**
     * @test
     * Circuit enters HALF_OPEN after cooldown period
     */
    public function it_enters_half_open_after_cooldown_period()
    {
        // Arrange: Open the circuit
        $operation = function () {
            throw new \Exception('Failure');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
            }
        }

        $this->assertEquals('open', $this->circuitBreaker->getState($this->testCircuitKey));

        // Act: Wait for cooldown period (30 seconds)
        Carbon::setTestNow(now()->addSeconds(31));

        // Create successful operation
        $successOperation = function () {
            return 'recovered';
        };

        // Attempt operation after cooldown
        $result = $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $successOperation);

        // Assert: Circuit allowed test request and succeeded
        $this->assertEquals('recovered', $result);

        // Cleanup
        Carbon::setTestNow();
    }

    /**
     * @test
     * Circuit rejects requests during cooldown period
     */
    public function it_rejects_requests_during_cooldown_period()
    {
        // Arrange: Open the circuit
        $operation = function () {
            throw new \Exception('Failure');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
            }
        }

        // Act: Try to execute before cooldown expires
        Carbon::setTestNow(now()->addSeconds(10)); // Only 10 seconds

        $this->expectException(CircuitOpenException::class);
        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, function () {
            return 'should_not_execute';
        });

        // Cleanup
        Carbon::setTestNow();
    }

    // ============================================================
    // STATE TRANSITIONS - HALF_OPEN → CLOSED
    // ============================================================

    /**
     * @test
     * Circuit closes after success threshold in HALF_OPEN
     */
    public function it_closes_after_success_threshold_in_half_open()
    {
        // Arrange: Open circuit and wait for cooldown
        $this->openCircuitAndWaitForCooldown();

        // Act: Execute 2 successful operations (success threshold)
        $operation = function () {
            return 'success';
        };

        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);

        // Assert: Circuit is CLOSED
        $state = $this->circuitBreaker->getState($this->testCircuitKey);
        $this->assertEquals('closed', $state);

        // Cleanup
        Carbon::setTestNow();
    }

    /**
     * @test
     * Circuit allows only one test request at a time in HALF_OPEN
     */
    public function it_allows_only_one_test_request_in_half_open()
    {
        // Arrange: Open circuit and enter HALF_OPEN
        $this->openCircuitAndWaitForCooldown();

        // Mark test request in progress manually
        $redis = Redis::connection();
        $redis->setex('circuit_breaker:' . $this->testCircuitKey . ':test_in_progress', 15, '1');

        // Act & Assert: Second request should be rejected
        $this->expectException(CircuitOpenException::class);
        $this->expectExceptionMessage('test request in progress');

        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, function () {
            return 'should_be_rejected';
        });

        // Cleanup
        Carbon::setTestNow();
    }

    // ============================================================
    // STATE TRANSITIONS - HALF_OPEN → OPEN
    // ============================================================

    /**
     * @test
     * Single failure in HALF_OPEN reopens circuit
     */
    public function it_reopens_on_failure_in_half_open()
    {
        // Arrange: Open circuit and wait for cooldown
        $this->openCircuitAndWaitForCooldown();

        // Execute one successful operation to enter HALF_OPEN
        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, function () {
            return 'first_success';
        });

        // Act: Fail on second attempt
        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, function () {
                throw new \Exception('Failed in HALF_OPEN');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Assert: Circuit is OPEN again
        $state = $this->circuitBreaker->getState($this->testCircuitKey);
        $this->assertEquals('open', $state);

        // Cleanup
        Carbon::setTestNow();
    }

    // ============================================================
    // REDIS STATE PERSISTENCE
    // ============================================================

    /**
     * @test
     * Circuit state persisted in Redis
     */
    public function it_persists_state_in_redis()
    {
        // Arrange: Open circuit
        $operation = function () {
            throw new \Exception('Failure');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
            }
        }

        // Act: Check Redis directly
        $redis = Redis::connection();
        $state = $redis->get('circuit_breaker:' . $this->testCircuitKey . ':state');
        $failures = $redis->get('circuit_breaker:' . $this->testCircuitKey . ':failures');

        // Assert: State persisted
        $this->assertEquals('open', $state);
        $this->assertGreaterThanOrEqual(3, (int)$failures);
    }

    /**
     * @test
     * Redis keys have TTL set
     */
    public function it_sets_ttl_on_redis_keys()
    {
        // Arrange: Record failure
        $operation = function () {
            throw new \Exception('Failure');
        };

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        // Act: Check TTL
        $redis = Redis::connection();
        $ttl = $redis->ttl('circuit_breaker:' . $this->testCircuitKey . ':state');

        // Assert: TTL is set (should be 86400 seconds = 24 hours)
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(86400, $ttl);
    }

    // ============================================================
    // DATABASE STATE PERSISTENCE
    // ============================================================

    /**
     * @test
     * Circuit state persisted to database
     */
    public function it_persists_state_to_database()
    {
        // Arrange & Act: Open circuit
        $operation = function () {
            throw new \Exception('Database persistence test');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
            }
        }

        // Assert: Database record created
        $this->assertDatabaseHas('circuit_breaker_states', [
            'circuit_key' => $this->testCircuitKey,
            'state' => 'open',
        ]);

        $record = DB::table('circuit_breaker_states')
            ->where('circuit_key', $this->testCircuitKey)
            ->first();

        $this->assertGreaterThanOrEqual(3, $record->failure_count);
        $this->assertNotNull($record->opened_at);
    }

    /**
     * @test
     * Database record updates on state changes
     */
    public function it_updates_database_record_on_state_change()
    {
        // Arrange: Open circuit
        $this->openCircuitAndWaitForCooldown();

        // Act: Close circuit with successful operations
        for ($i = 0; $i < 2; $i++) {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, function () {
                return 'success';
            });
        }

        // Assert: Database updated to CLOSED
        $this->assertDatabaseHas('circuit_breaker_states', [
            'circuit_key' => $this->testCircuitKey,
            'state' => 'closed',
        ]);

        $record = DB::table('circuit_breaker_states')
            ->where('circuit_key', $this->testCircuitKey)
            ->first();

        $this->assertNotNull($record->closed_at);

        // Cleanup
        Carbon::setTestNow();
    }

    // ============================================================
    // MULTIPLE CIRCUIT ISOLATION
    // ============================================================

    /**
     * @test
     * Multiple circuits operate independently
     */
    public function it_isolates_multiple_circuits()
    {
        // Arrange: Two different circuits
        $circuit1 = 'test_circuit:service_a:123';
        $circuit2 = 'test_circuit:service_b:456';

        $failOperation = function () {
            throw new \Exception('Failure');
        };

        $successOperation = function () {
            return 'success';
        };

        // Act: Open circuit 1
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($circuit1, $failOperation);
            } catch (\Exception $e) {
            }
        }

        // Circuit 2 remains operational
        $result = $this->circuitBreaker->executeWithCircuitBreaker($circuit2, $successOperation);

        // Assert: Circuit 1 is OPEN, Circuit 2 is CLOSED
        $this->assertEquals('open', $this->circuitBreaker->getState($circuit1));
        $this->assertEquals('closed', $this->circuitBreaker->getState($circuit2));
        $this->assertEquals('success', $result);
    }

    /**
     * @test
     * Service-level isolation prevents cross-contamination
     */
    public function it_prevents_cross_service_contamination()
    {
        // Arrange: Different service IDs
        $serviceA = 'appointment_booking:service:abc';
        $serviceB = 'appointment_booking:service:xyz';

        $failOp = function () {
            throw new \Exception('Fail');
        };

        // Act: Open service A circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($serviceA, $failOp);
            } catch (\Exception $e) {
            }
        }

        // Assert: Service B unaffected
        $stateA = $this->circuitBreaker->getState($serviceA);
        $stateB = $this->circuitBreaker->getState($serviceB);

        $this->assertEquals('open', $stateA);
        $this->assertEquals('closed', $stateB);
    }

    // ============================================================
    // FAST FAIL BEHAVIOR
    // ============================================================

    /**
     * @test
     * Open circuit fails fast without executing operation
     */
    public function it_fails_fast_when_circuit_open()
    {
        // Arrange: Open circuit
        $operation = function () {
            throw new \Exception('Failure');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
            }
        }

        // Act: Try operation that should not execute
        $executionFlag = false;
        $fastFailOperation = function () use (&$executionFlag) {
            $executionFlag = true; // This should never be set
            return 'should_not_execute';
        };

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $fastFailOperation);
        } catch (CircuitOpenException $e) {
            // Expected
        }

        // Assert: Operation never executed
        $this->assertFalse($executionFlag, 'Operation should not have been executed');
    }

    /**
     * @test
     * Fast fail has minimal latency
     */
    public function it_fails_fast_with_minimal_latency()
    {
        // Arrange: Open circuit
        $this->openCircuitAndWaitForCooldown();

        // Reset time to trigger fast fail (not cooldown)
        Carbon::setTestNow(now());

        // Act: Measure fast fail latency
        $startTime = microtime(true);

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, function () {
                sleep(5); // Should never execute
                return 'slow_operation';
            });
        } catch (CircuitOpenException $e) {
            // Expected
        }

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Assert: Fast fail took < 10ms (should be nearly instant)
        $this->assertLessThan(10, $duration, "Fast fail took {$duration}ms (should be <10ms)");

        // Cleanup
        Carbon::setTestNow();
    }

    // ============================================================
    // STATISTICS TRACKING
    // ============================================================

    /**
     * @test
     * Get circuit statistics
     */
    public function it_provides_circuit_statistics()
    {
        // Arrange: Record some failures
        $operation = function () {
            throw new \Exception('Stat test failure');
        };

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        // Act: Get statistics
        $stats = $this->circuitBreaker->getStatistics($this->testCircuitKey);

        // Assert: Statistics complete
        $this->assertArrayHasKey('circuit_key', $stats);
        $this->assertArrayHasKey('state', $stats);
        $this->assertArrayHasKey('failure_count', $stats);
        $this->assertArrayHasKey('success_count', $stats);
        $this->assertArrayHasKey('last_failure_at', $stats);

        $this->assertEquals($this->testCircuitKey, $stats['circuit_key']);
        $this->assertEquals('closed', $stats['state']); // Not yet open (need 3 failures)
        $this->assertEquals(2, $stats['failure_count']);
    }

    /**
     * @test
     * Statistics track state changes
     */
    public function it_tracks_state_changes_in_statistics()
    {
        // Arrange: Open circuit
        $this->openCircuitAndWaitForCooldown();

        // Act: Get stats after opening
        $stats = $this->circuitBreaker->getStatistics($this->testCircuitKey);

        // Assert: Opened timestamp recorded
        $this->assertEquals('open', $stats['state']);
        $this->assertNotNull($stats['opened_at']);
        $this->assertGreaterThanOrEqual(3, $stats['failure_count']);

        // Cleanup
        Carbon::setTestNow();
    }

    // ============================================================
    // EDGE CASES & ERROR HANDLING
    // ============================================================

    /**
     * @test
     * Circuit handles operation exceptions properly
     */
    public function it_propagates_operation_exceptions()
    {
        // Arrange: Operation that throws custom exception
        $operation = function () {
            throw new \RuntimeException('Custom business logic error');
        };

        // Act & Assert: Exception propagated
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Custom business logic error');

        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
    }

    /**
     * @test
     * Circuit breaker handles concurrent requests
     */
    public function it_handles_concurrent_requests()
    {
        // Arrange: Simulate concurrent failures
        $operation = function () {
            throw new \Exception('Concurrent failure');
        };

        // Act: Execute multiple failures in quick succession
        $exceptions = [];
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
                $exceptions[] = $e;
            }
        }

        // Assert: Circuit opened and subsequent requests fast-failed
        $this->assertCount(5, $exceptions);
        $this->assertEquals('open', $this->circuitBreaker->getState($this->testCircuitKey));

        // Some should be CircuitOpenException (fast fails)
        $circuitOpenCount = 0;
        foreach ($exceptions as $exception) {
            if ($exception instanceof CircuitOpenException) {
                $circuitOpenCount++;
            }
        }

        $this->assertGreaterThan(0, $circuitOpenCount, 'Some requests should have fast-failed');
    }

    /**
     * @test
     * Default state is CLOSED for new circuit
     */
    public function it_defaults_to_closed_state_for_new_circuit()
    {
        // Act: Get state of never-used circuit
        $newCircuit = 'brand_new_circuit:test';
        $state = $this->circuitBreaker->getState($newCircuit);

        // Assert: Defaults to CLOSED
        $this->assertEquals('closed', $state);
    }

    /**
     * @test
     * Success resets failure counter in CLOSED state
     */
    public function it_resets_failure_counter_on_success()
    {
        // Arrange: Record 2 failures
        $attemptCount = 0;
        $operation = function () use (&$attemptCount) {
            $attemptCount++;
            if ($attemptCount <= 2) {
                throw new \Exception('Failure');
            }
            return 'success';
        };

        // Act: 2 failures, then success
        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        try {
            $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
        } catch (\Exception $e) {
        }

        // Success
        $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);

        // Now add 3 more failures
        $failOp = function () {
            throw new \Exception('New failure');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $failOp);
            } catch (\Exception $e) {
            }
        }

        // Assert: Circuit opened (counter was reset by success)
        $state = $this->circuitBreaker->getState($this->testCircuitKey);
        $this->assertEquals('open', $state);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Helper: Open circuit and wait for cooldown period
     */
    private function openCircuitAndWaitForCooldown(): void
    {
        // Open circuit
        $operation = function () {
            throw new \Exception('Open circuit');
        };

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->circuitBreaker->executeWithCircuitBreaker($this->testCircuitKey, $operation);
            } catch (\Exception $e) {
            }
        }

        // Wait for cooldown
        Carbon::setTestNow(now()->addSeconds(31));
    }

    protected function tearDown(): void
    {
        // Clear Redis
        Redis::connection()->flushdb();

        // Reset Carbon time
        Carbon::setTestNow();

        // Clean up Mockery expectations (prevents cascading test failures)
        Mockery::close();

        parent::tearDown();
    }
}
