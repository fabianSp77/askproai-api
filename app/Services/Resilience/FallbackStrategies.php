<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Fallback Strategies - Graceful degradation patterns
 *
 * When circuit breakers are open, provides alternative strategies:
 * 1. Stale Cache Strategy: Serve last known good value
 * 2. Reduced Feature Strategy: Show limited functionality
 * 3. Queue Strategy: Buffer requests for later processing
 * 4. Manual Review Strategy: Flag for human intervention
 * 5. Failure Response Strategy: Return appropriate error to user
 *
 * Ensures system remains available even when external services fail
 */
class FallbackStrategies
{
    /**
     * Service being accessed (for logging/tracking)
     */
    private string $serviceName;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;
    }

    /**
     * Execute operation with fallback chain
     *
     * Tries: Primary → Stale Cache → Queue → Manual Review
     *
     * @param callable $primary Primary operation (e.g., Cal.com API call)
     * @param string $cacheKey Cache key for stale fallback
     * @param string $operationName Operation name for logging
     * @return mixed Result or null if all fallbacks exhausted
     */
    public function executeWithFallback(
        callable $primary,
        string $cacheKey,
        string $operationName = 'operation'
    ): mixed {
        try {
            // Try primary operation
            Log::debug("🔄 Attempting primary operation", [
                'service' => $this->serviceName,
                'operation' => $operationName,
            ]);

            return $primary();

        } catch (Exception $e) {
            Log::warning("⚠️ Primary operation failed, attempting fallbacks", [
                'service' => $this->serviceName,
                'operation' => $operationName,
                'error' => $e->getMessage(),
            ]);

            // Try stale cache
            $staleValue = $this->tryStaleCache($cacheKey, $operationName);
            if ($staleValue !== null) {
                return $staleValue;
            }

            // Queue for retry
            $this->queueForRetry($operationName, $cacheKey);

            // Request manual review if critical
            $this->requestManualReviewIfCritical($operationName, $e);

            return null;
        }
    }

    /**
     * Stale Cache Fallback Strategy
     *
     * Returns last known good value even if expired
     * Useful for: Availability data, service listings, staff info
     *
     * @param string $cacheKey Cache key to retrieve
     * @param string $operationName Operation name for logging
     * @return mixed Stale cached value or null
     */
    private function tryStaleCache(string $cacheKey, string $operationName): mixed
    {
        try {
            // Try to get from cache even if expired
            $staleValue = Cache::get($cacheKey);

            if ($staleValue !== null) {
                Log::info("✓ Using stale cache fallback", [
                    'service' => $this->serviceName,
                    'operation' => $operationName,
                    'cache_key' => $cacheKey,
                ]);

                return $staleValue;
            }

        } catch (Exception $e) {
            Log::debug("Stale cache fallback failed", [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Queue for Retry Strategy
     *
     * Buffers failed requests in Redis for background retry
     * Prevents user-facing errors, processes when service recovers
     *
     * @param string $operationName Operation being retried
     * @param string $cacheKey Cache key for context
     */
    private function queueForRetry(string $operationName, string $cacheKey): void
    {
        try {
            $retryKey = "fallback:retry_queue:{$this->serviceName}:{$operationName}";
            $queueEntry = [
                'operation' => $operationName,
                'cache_key' => $cacheKey,
                'queued_at' => now()->toIso8601String(),
                'retry_count' => 0,
                'max_retries' => 5,
            ];

            Cache::push($retryKey, $queueEntry);

            Log::info("📋 Operation queued for retry", [
                'service' => $this->serviceName,
                'operation' => $operationName,
            ]);

        } catch (Exception $e) {
            Log::warning("Failed to queue for retry", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manual Review Strategy
     *
     * For critical operations (e.g., appointment creation),
     * flags for human intervention instead of silently failing
     *
     * @param string $operationName Operation that failed
     * @param Exception $error Exception details
     */
    private function requestManualReviewIfCritical(string $operationName, Exception $error): void
    {
        $criticalOperations = ['appointment_creation', 'sync_to_calcom', 'customer_verification'];

        if (in_array($operationName, $criticalOperations)) {
            try {
                $reviewKey = "fallback:manual_review:{$this->serviceName}:" . now()->timestamp;
                $reviewEntry = [
                    'service' => $this->serviceName,
                    'operation' => $operationName,
                    'error' => $error->getMessage(),
                    'flagged_at' => now()->toIso8601String(),
                    'severity' => 'high',
                    'action_required' => true,
                ];

                Cache::put($reviewKey, $reviewEntry, 86400);  // 24 hours

                Log::error("🚨 Critical operation flagged for manual review", [
                    'service' => $this->serviceName,
                    'operation' => $operationName,
                    'error' => $error->getMessage(),
                ]);

            } catch (Exception $e) {
                Log::warning("Failed to flag for manual review", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reduced Feature Strategy
     *
     * When service degraded, show limited but usable functionality
     * Example: Show available times, but disable advanced filtering
     *
     * @param string $featureId Feature being accessed
     * @return bool True if feature available
     */
    public function isFeatureAvailable(string $featureId): bool
    {
        $breaker = new DistributedCircuitBreaker($this->serviceName);
        $status = $breaker->getStatus();

        // When OPEN or HALF_OPEN, disable advanced features
        if ($status['state'] !== 'closed') {
            $advancedFeatures = ['filtering', 'sorting', 'recommendations', 'bulk_operations'];

            if (in_array($featureId, $advancedFeatures)) {
                Log::debug("Feature disabled due to service degradation", [
                    'service' => $this->serviceName,
                    'feature' => $featureId,
                    'reason' => 'Circuit breaker: ' . $status['state'],
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Get current degradation level (0-100)
     *
     * 0 = fully operational, 100 = fully degraded
     *
     * @return int Degradation level
     */
    public function getDegradationLevel(): int
    {
        $breaker = new DistributedCircuitBreaker($this->serviceName);
        $status = $breaker->getStatus();

        return match ($status['state']) {
            'closed' => 0,              // Fully operational
            'half_open' => 50,          // Partially degraded
            'open' => 100,              // Fully degraded
            default => 0,
        };
    }

    /**
     * Get user-friendly degradation message
     *
     * @return string User-facing message about system status
     */
    public function getDegradationMessage(): string
    {
        $level = $this->getDegradationLevel();

        if ($level === 0) {
            return 'All systems operational';
        } elseif ($level === 50) {
            return 'Some features temporarily unavailable. Retrying...';
        } else {
            return 'System experiencing issues. We are working to restore service.';
        }
    }

    /**
     * Should request be retried?
     *
     * Determines if operation should be retried based on:
     * - Current service state
     * - Retry count
     * - Operation type
     *
     * @param string $operationName Operation name
     * @param int $retryCount Current retry count
     * @return bool True if should retry
     */
    public function shouldRetry(string $operationName, int $retryCount = 0): bool
    {
        $breaker = new DistributedCircuitBreaker($this->serviceName);
        $status = $breaker->getStatus();

        // Max retries exceeded
        if ($retryCount >= 5) {
            Log::warning("Max retries exceeded", [
                'service' => $this->serviceName,
                'operation' => $operationName,
            ]);
            return false;
        }

        // If OPEN, don't retry (fail fast)
        if ($status['state'] === 'open') {
            return false;
        }

        // If HALF_OPEN, retry with quota limit
        if ($status['state'] === 'half_open') {
            $halfOpenQuota = $status['half_open_quota'] ?? 0;
            if ($halfOpenQuota <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Idempotent retry configuration
     *
     * Returns retry parameters ensuring idempotent operations
     *
     * @return array Retry configuration
     */
    public function getRetryConfiguration(): array
    {
        $breaker = new DistributedCircuitBreaker($this->serviceName);
        $status = $breaker->getStatus();

        $delays = [0, 100, 500, 1000, 5000];  // ms

        return [
            'max_attempts' => 5,
            'delays_ms' => $delays,
            'exponential_backoff' => true,
            'jitter' => true,
            'circuit_breaker_state' => $status['state'],
            'should_retry' => $status['state'] !== 'open',
        ];
    }

    /**
     * Get metrics on fallback usage
     *
     * Tracks how often fallbacks are being used
     * High usage = service is experiencing issues
     *
     * @return array Fallback usage metrics
     */
    public function getFallbackMetrics(): array
    {
        try {
            $staleUsage = Cache::get("fallback:stale_cache_usage:{$this->serviceName}", 0);
            $queuedCount = Cache::get("fallback:queued_operations:{$this->serviceName}", 0);
            $manualReviewCount = Cache::get("fallback:manual_review_count:{$this->serviceName}", 0);

            return [
                'service' => $this->serviceName,
                'stale_cache_uses' => $staleUsage,
                'queued_operations' => $queuedCount,
                'manual_reviews' => $manualReviewCount,
                'is_degraded' => $staleUsage > 0 || $queuedCount > 0,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get fallback metrics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Process retry queue
     *
     * Called by background job to retry queued operations
     *
     * @param callable $retryHandler Handler to retry operations
     * @return array Processing results
     */
    public function processRetryQueue(callable $retryHandler): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $queueKey = "fallback:retry_queue:{$this->serviceName}";
            $queue = Cache::get($queueKey, []);

            foreach ($queue as $entry) {
                $results['processed']++;

                try {
                    if ($retryHandler($entry)) {
                        $results['succeeded']++;
                    } else {
                        $results['failed']++;
                    }

                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }

            // Clear processed queue
            Cache::forget($queueKey);

            Log::info("✓ Retry queue processed", [
                'service' => $this->serviceName,
                'results' => $results,
            ]);

        } catch (Exception $e) {
            Log::error("Failed to process retry queue", [
                'service' => $this->serviceName,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Get circuit breaker compatible exception
     *
     * Wraps exception in a way circuit breaker can understand
     *
     * @param string $message Error message
     * @param string $code Error code
     * @return CircuitBreakerOpenException Exception for circuit breaker
     */
    public function getCircuitBreakerException(
        string $message = 'Service unavailable',
        string $code = 'SERVICE_UNAVAILABLE'
    ): CircuitBreakerOpenException {
        return new CircuitBreakerOpenException($message);
    }
}
