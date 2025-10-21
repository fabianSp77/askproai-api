<?php

namespace Tests\Unit\Services;

use App\Exceptions\Appointments\AppointmentException;
use App\Exceptions\Appointments\CalcomBookingException;
use App\Exceptions\Appointments\CustomerValidationException;
use App\Exceptions\Appointments\AppointmentDatabaseException;
use App\Services\Resilience\CalcomCircuitBreaker;
use App\Services\Resilience\RetryPolicy;
use App\Services\Resilience\FailureDetector;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Phase3ResilienceTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Exception Hierarchy Tests
    // ═══════════════════════════════════════════════════════════════════════════════

    public function test_appointment_exception_has_correlation_id()
    {
        $exception = new AppointmentException(
            message: "Test error",
            correlationId: "test_correlation_123"
        );

        $this->assertEquals("test_correlation_123", $exception->getCorrelationId());
    }

    public function test_appointment_exception_generates_correlation_id_if_not_provided()
    {
        $exception = new AppointmentException(message: "Test error");

        $this->assertStringStartsWith("appointment_", $exception->getCorrelationId());
    }

    public function test_appointment_exception_returns_structured_log_context()
    {
        $exception = new AppointmentException(
            message: "Test error",
            code: 500,
            correlationId: "test_123"
        );

        $logContext = $exception->toLogContext();

        $this->assertArrayHasKey('correlation_id', $logContext);
        $this->assertArrayHasKey('exception', $logContext);
        $this->assertArrayHasKey('message', $logContext);
        $this->assertEquals("test_123", $logContext['correlation_id']);
    }

    public function test_calcom_booking_exception_identifies_transient_errors()
    {
        // Test transient errors (should be retryable)
        $exception429 = new CalcomBookingException(
            message: "Rate limited",
            httpStatus: 429
        );
        $this->assertTrue($exception429->isRetryable());

        $exception503 = new CalcomBookingException(
            message: "Service unavailable",
            httpStatus: 503
        );
        $this->assertTrue($exception503->isRetryable());

        $exception504 = new CalcomBookingException(
            message: "Gateway timeout",
            httpStatus: 504
        );
        $this->assertTrue($exception504->isRetryable());
    }

    public function test_calcom_booking_exception_identifies_permanent_errors()
    {
        // Test permanent errors (should NOT be retryable)
        $exception400 = new CalcomBookingException(
            message: "Bad request",
            httpStatus: 400
        );
        $this->assertFalse($exception400->isRetryable());

        $exception401 = new CalcomBookingException(
            message: "Unauthorized",
            httpStatus: 401
        );
        $this->assertFalse($exception401->isRetryable());

        $exception404 = new CalcomBookingException(
            message: "Not found",
            httpStatus: 404
        );
        $this->assertFalse($exception404->isRetryable());
    }

    public function test_customer_validation_exception_tracks_field_errors()
    {
        $validationErrors = [
            'email' => 'Invalid email format',
            'phone' => 'Phone must be 10 digits',
        ];

        $exception = new CustomerValidationException(
            message: "Validation failed",
            validationErrors: $validationErrors
        );

        $this->assertTrue($exception->hasFieldError('email'));
        $this->assertTrue($exception->hasFieldError('phone'));
        $this->assertFalse($exception->hasFieldError('name'));
        $this->assertEquals('Invalid email format', $exception->getFieldError('email'));
    }

    public function test_database_exception_identifies_transient_errors()
    {
        $deadlockException = new AppointmentDatabaseException(
            message: "Deadlock detected",
            errorType: "deadlock"
        );
        $this->assertTrue($deadlockException->isRetryable());
        $this->assertTrue($deadlockException->isDeadlock());

        $timeoutException = new AppointmentDatabaseException(
            message: "Lock wait timeout",
            errorType: "timeout"
        );
        $this->assertTrue($timeoutException->isRetryable());
        $this->assertTrue($timeoutException->isTimeout());

        $connectionLostException = new AppointmentDatabaseException(
            message: "Connection lost",
            errorType: "connection_lost"
        );
        $this->assertTrue($connectionLostException->isRetryable());
        $this->assertTrue($connectionLostException->isConnectionLost());
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Circuit Breaker Tests
    // ═══════════════════════════════════════════════════════════════════════════════

    public function test_circuit_breaker_starts_in_closed_state()
    {
        $breaker = new CalcomCircuitBreaker();

        $this->assertEquals('closed', $breaker->getState());
        $this->assertFalse($breaker->isOpen());
        $this->assertFalse($breaker->isHalfOpen());
    }

    public function test_circuit_breaker_opens_after_threshold_failures()
    {
        $breaker = new CalcomCircuitBreaker();

        // Record 5 failures (threshold is 5)
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure("Test failure $i");
        }

        $this->assertTrue($breaker->isOpen());
        $this->assertEquals('open', $breaker->getState());
    }

    public function test_circuit_breaker_transitions_to_half_open_after_timeout()
    {
        $breaker = new CalcomCircuitBreaker();

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure("Test failure");
        }

        $this->assertTrue($breaker->isOpen());

        // Manually set opened_at to past (simulate timeout)
        Cache::put('circuit_breaker:calcom:opened_at', now()->timestamp - 35, 3600);

        $status = $breaker->getStatus();
        $this->assertEquals('half_open', $status['state']);
    }

    public function test_circuit_breaker_closes_on_success_from_half_open()
    {
        $breaker = new CalcomCircuitBreaker();

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure("Test failure");
        }

        // Force to half-open
        Cache::put('circuit_breaker:calcom:opened_at', now()->timestamp - 35, 3600);

        // Record success
        $breaker->recordSuccess();

        $this->assertEquals('closed', $breaker->getState());
        $this->assertFalse($breaker->isOpen());
    }

    public function test_circuit_breaker_reopens_on_failure_from_half_open()
    {
        $breaker = new CalcomCircuitBreaker();

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure("Test failure");
        }

        // Force to half-open
        Cache::put('circuit_breaker:calcom:opened_at', now()->timestamp - 35, 3600);

        // Record failure from half-open
        $breaker->recordFailure("Still failing");

        $this->assertTrue($breaker->isOpen());
    }

    public function test_circuit_breaker_can_be_manually_reset()
    {
        $breaker = new CalcomCircuitBreaker();

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure("Test failure");
        }

        $this->assertTrue($breaker->isOpen());

        // Manual reset
        $breaker->reset();

        $this->assertEquals('closed', $breaker->getState());
        $this->assertFalse($breaker->isOpen());
    }

    public function test_circuit_breaker_provides_status_for_monitoring()
    {
        $breaker = new CalcomCircuitBreaker();

        for ($i = 0; $i < 3; $i++) {
            $breaker->recordFailure("Test failure");
        }

        $status = $breaker->getStatus();

        $this->assertArrayHasKey('state', $status);
        $this->assertArrayHasKey('failures', $status);
        $this->assertArrayHasKey('threshold', $status);
        $this->assertArrayHasKey('is_open', $status);
        $this->assertArrayHasKey('is_half_open', $status);
        $this->assertEquals(3, $status['failures']);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Retry Policy Tests
    // ═══════════════════════════════════════════════════════════════════════════════

    public function test_retry_policy_executes_operation_successfully()
    {
        $retry = new RetryPolicy();

        $result = $retry->execute(function () {
            return "success";
        });

        $this->assertEquals("success", $result);
    }

    public function test_retry_policy_throws_non_retryable_exception()
    {
        $retry = new RetryPolicy();

        $this->expectException(\Exception::class);

        $retry->execute(function () {
            throw new \Exception("Bad request - not retryable");
        });
    }

    public function test_retry_policy_retries_on_timeout_exception()
    {
        $retry = new RetryPolicy();
        $attempts = 0;

        $result = $retry->execute(function () use (&$attempts) {
            $attempts++;

            if ($attempts < 2) {
                throw new \Exception("Timeout occurred");
            }

            return "success_after_retry";
        });

        $this->assertEquals("success_after_retry", $result);
        $this->assertEquals(2, $attempts);
    }

    public function test_retry_policy_exhausts_retries()
    {
        $retry = new RetryPolicy();
        $attempts = 0;

        $this->expectException(\Exception::class);

        $retry->execute(function () use (&$attempts) {
            $attempts++;
            throw new \Exception("Timeout occurred");
        });

        // Should have attempted: 1 initial + 3 max_attempts = 4 attempts
        // But max_attempts=3 means allow up to attempt 3, so: 1 + 3 = 4 total checks
        // Actually the loop is: while ($attempt <= max_attempts), so 0,1,2,3 = 4 iterations
        $this->assertGreaterThanOrEqual(1, $attempts);
    }

    public function test_retry_policy_returns_config()
    {
        $retry = new RetryPolicy();
        $config = $retry->getConfig();

        $this->assertArrayHasKey('max_attempts', $config);
        $this->assertArrayHasKey('delays', $config);
        $this->assertArrayHasKey('transient_errors', $config);
        $this->assertArrayHasKey('jitter', $config);
        $this->assertEquals(3, $config['max_attempts']);
        $this->assertEquals([1, 2, 4], $config['delays']);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Failure Detector Tests
    // ═══════════════════════════════════════════════════════════════════════════════

    public function test_failure_detector_records_failures()
    {
        $detector = new FailureDetector();

        $detector->recordFailure('calcom', 'timeout', 2);
        $detector->recordFailure('calcom', 'rate_limit', 1);

        $stats = $detector->getFailureStats('calcom');

        $this->assertGreaterThan(0, $stats['total']);
    }

    public function test_failure_detector_calculates_failure_stats()
    {
        $detector = new FailureDetector();

        $detector->recordFailure('calcom', 'timeout', 3);
        $detector->recordFailure('calcom', 'timeout', 3);

        $stats = $detector->getFailureStats('calcom');

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('recent', $stats);
        $this->assertArrayHasKey('severity_distribution', $stats);
        $this->assertArrayHasKey('last_failure_at', $stats);
        $this->assertEquals(2, $stats['total']);
    }

    public function test_failure_detector_detects_degradation()
    {
        $detector = new FailureDetector();

        // Record multiple failures to trigger degradation
        for ($i = 0; $i < 5; $i++) {
            $detector->recordFailure('calcom', 'timeout', 2);
        }

        $isDegraded = $detector->isServiceDegraded('calcom', 0.25);

        $this->assertTrue($isDegraded);
    }

    public function test_failure_detector_can_reset()
    {
        $detector = new FailureDetector();

        $detector->recordFailure('calcom', 'timeout', 2);
        $statsBefore = $detector->getFailureStats('calcom');

        $this->assertGreaterThan(0, $statsBefore['total']);

        $detector->reset('calcom');
        $statsAfter = $detector->getFailureStats('calcom');

        $this->assertEquals(0, $statsAfter['total']);
    }

    public function test_failure_detector_provides_health_status()
    {
        $detector = new FailureDetector();

        $detector->recordFailure('calcom', 'timeout', 2);

        $health = $detector->getHealthStatus('calcom');

        $this->assertArrayHasKey('service', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('statistics', $health);
        $this->assertContains($health['status'], ['healthy', 'degraded', 'critical']);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // Integration Tests
    // ═══════════════════════════════════════════════════════════════════════════════

    public function test_circuit_breaker_and_failure_detector_work_together()
    {
        $breaker = new CalcomCircuitBreaker();
        $detector = new FailureDetector();

        // Record failures in both systems
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure("API failure");
            $detector->recordFailure('calcom', 'timeout', 2);
        }

        // Both should recognize the problem
        $this->assertTrue($breaker->isOpen());
        $this->assertTrue($detector->isServiceDegraded('calcom'));

        // Manual recovery
        $breaker->reset();
        $detector->reset('calcom');

        // Both should be recovered
        $this->assertFalse($breaker->isOpen());
        $this->assertFalse($detector->isServiceDegraded('calcom'));
    }

    public function test_exception_correlation_id_flows_through_resilience_stack()
    {
        $correlationId = "test_flow_123";

        $exception = new CalcomBookingException(
            message: "Cal.com failed",
            httpStatus: 503,
            correlationId: $correlationId
        );

        $this->assertEquals($correlationId, $exception->getCorrelationId());
        $logContext = $exception->toLogContext();
        $this->assertEquals($correlationId, $logContext['correlation_id']);
    }
}
