<?php

namespace App\Services\Tracing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Exception;

/**
 * Request Correlation Service - Manages request correlation across services
 *
 * Every request gets a unique correlation ID that flows through:
 * - HTTP requests
 * - Database operations
 * - External API calls
 * - Cache operations
 * - Saga steps
 * - Event listeners
 * - Queue jobs
 *
 * Enables end-to-end tracing and debugging
 */
class RequestCorrelationService
{
    /**
     * Current correlation ID for this request
     */
    private string $correlationId;

    /**
     * Parent correlation ID (if nested request)
     */
    private ?string $parentCorrelationId = null;

    /**
     * Request metadata
     */
    private array $metadata = [];

    /**
     * Create or retrieve correlation ID
     */
    public function __construct()
    {
        // Check if correlation ID already exists (from header or context)
        $this->correlationId = $this->retrieveOrCreateCorrelationId();
    }

    /**
     * Retrieve existing or create new correlation ID
     *
     * Checks (in order):
     * 1. Request header (X-Correlation-ID)
     * 2. Session/context
     * 3. Queue job payload
     * 4. Create new UUID
     *
     * @return string Correlation ID
     */
    private function retrieveOrCreateCorrelationId(): string
    {
        // Check request header (for incoming requests)
        if (request()->hasHeader('X-Correlation-ID')) {
            $correlationId = request()->header('X-Correlation-ID');
            Log::debug('Using correlation ID from header', ['correlation_id' => $correlationId]);
            return $correlationId;
        }

        // Check session (for user sessions)
        if (session()->has('correlation_id')) {
            return session()->get('correlation_id');
        }

        // Check queue job (for queued operations)
        if (isset($GLOBALS['correlation_id'])) {
            return $GLOBALS['correlation_id'];
        }

        // Create new correlation ID
        $newId = Uuid::uuid4()->toString();
        Log::debug('Created new correlation ID', ['correlation_id' => $newId]);

        // Store in session if available
        if (session()->started()) {
            session()->put('correlation_id', $newId);
        }

        return $newId;
    }

    /**
     * Get current correlation ID
     *
     * @return string Correlation ID
     */
    public function getId(): string
    {
        return $this->correlationId;
    }

    /**
     * Get correlation ID for HTTP responses
     *
     * @return array Headers to include in response
     */
    public function getResponseHeaders(): array
    {
        return [
            'X-Correlation-ID' => $this->correlationId,
            'X-Request-ID' => $this->correlationId,
        ];
    }

    /**
     * Set parent correlation ID (for nested/chained requests)
     *
     * @param string $parentId Parent correlation ID
     */
    public function setParent(string $parentId): void
    {
        $this->parentCorrelationId = $parentId;
    }

    /**
     * Get parent correlation ID
     *
     * @return string|null Parent ID or null
     */
    public function getParent(): ?string
    {
        return $this->parentCorrelationId;
    }

    /**
     * Set metadata for this request
     *
     * Used to track:
     * - User ID
     * - Company ID
     * - API endpoint
     * - Request method
     * - Client IP
     * - Request timestamp
     *
     * @param array $metadata Metadata to associate
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->storeMetadata();
    }

    /**
     * Get metadata for this request
     *
     * @return array Request metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Add metadata field
     *
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     */
    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
        $this->storeMetadata();
    }

    /**
     * Store metadata in cache/session
     */
    private function storeMetadata(): void
    {
        try {
            $metadataKey = "correlation:{$this->correlationId}:metadata";
            Cache::put($metadataKey, $this->metadata, 86400);  // 24 hours

        } catch (Exception $e) {
            Log::debug("Failed to store correlation metadata", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retrieve metadata for correlation ID
     *
     * @param string $correlationId Correlation ID
     * @return array Metadata
     */
    public static function getMetadataForId(string $correlationId): array
    {
        try {
            $metadataKey = "correlation:{$correlationId}:metadata";
            return Cache::get($metadataKey, []);

        } catch (Exception $e) {
            Log::debug("Failed to retrieve correlation metadata", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Record operation under this correlation ID
     *
     * Links operation to correlation ID for later retrieval
     *
     * @param string $operationType Type of operation (saga_step, api_call, cache_miss, etc.)
     * @param array $details Operation details
     */
    public function recordOperation(string $operationType, array $details = []): void
    {
        try {
            $operation = [
                'type' => $operationType,
                'timestamp' => now()->toIso8601String(),
                'details' => $details,
            ];

            $operationsKey = "correlation:{$this->correlationId}:operations";
            $operations = Cache::get($operationsKey, []);
            $operations[] = $operation;

            // Keep last 1000 operations
            if (count($operations) > 1000) {
                $operations = array_slice($operations, -1000);
            }

            Cache::put($operationsKey, $operations, 86400);

        } catch (Exception $e) {
            Log::debug("Failed to record operation", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all operations for this correlation ID
     *
     * @return array Operations list
     */
    public function getOperations(): array
    {
        try {
            $operationsKey = "correlation:{$this->correlationId}:operations";
            return Cache::get($operationsKey, []);

        } catch (Exception $e) {
            Log::debug("Failed to retrieve operations", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Mark correlation as failed
     *
     * @param string $reason Reason for failure
     * @param Exception|null $exception Exception that caused failure
     */
    public function markFailed(string $reason, ?Exception $exception = null): void
    {
        try {
            $failureKey = "correlation:{$this->correlationId}:failure";
            $failure = [
                'reason' => $reason,
                'exception' => $exception ? $exception->getMessage() : null,
                'trace' => $exception ? $exception->getTraceAsString() : null,
                'timestamp' => now()->toIso8601String(),
            ];

            Cache::put($failureKey, $failure, 86400);

            Log::warning("Correlation marked as failed", [
                'correlation_id' => $this->correlationId,
                'reason' => $reason,
                'exception' => $exception?->getMessage(),
            ]);

        } catch (Exception $e) {
            Log::debug("Failed to mark correlation as failed", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get failure info if this correlation failed
     *
     * @return array|null Failure info or null
     */
    public function getFailureInfo(): ?array
    {
        try {
            $failureKey = "correlation:{$this->correlationId}:failure";
            return Cache::get($failureKey);

        } catch (Exception $e) {
            Log::debug("Failed to retrieve failure info", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mark correlation as successful
     *
     * @param array $result Operation result
     */
    public function markSuccessful(array $result = []): void
    {
        try {
            $successKey = "correlation:{$this->correlationId}:success";
            $success = [
                'result' => $result,
                'timestamp' => now()->toIso8601String(),
                'operations_count' => count($this->getOperations()),
            ];

            Cache::put($successKey, $success, 86400);

            Log::info("Correlation completed successfully", [
                'correlation_id' => $this->correlationId,
            ]);

        } catch (Exception $e) {
            Log::debug("Failed to mark correlation as successful", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get complete correlation trace
     *
     * Returns all information for this correlation ID:
     * - Metadata
     * - Operations timeline
     * - Failure or success info
     *
     * @return array Complete trace
     */
    public function getCompleteTrace(): array
    {
        try {
            return [
                'correlation_id' => $this->correlationId,
                'parent_correlation_id' => $this->parentCorrelationId,
                'metadata' => $this->getMetadata(),
                'operations' => $this->getOperations(),
                'failure' => $this->getFailureInfo(),
                'success' => Cache::get("correlation:{$this->correlationId}:success"),
                'duration_seconds' => $this->calculateDuration(),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get complete trace", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate request duration
     *
     * @return float|null Duration in seconds or null
     */
    private function calculateDuration(): ?float
    {
        try {
            $operations = $this->getOperations();
            if (empty($operations)) {
                return null;
            }

            $firstOp = reset($operations);
            $lastOp = end($operations);

            $start = strtotime($firstOp['timestamp']);
            $end = strtotime($lastOp['timestamp']);

            return $end > $start ? $end - $start : null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Search correlations by criteria
     *
     * Finds correlation IDs matching:
     * - User ID
     * - Company ID
     * - Date range
     * - Status (success/failure)
     *
     * @param array $criteria Search criteria
     * @return array Matching correlation IDs
     */
    public static function search(array $criteria): array
    {
        try {
            $results = [];

            // This is a simplified search - in production use Elasticsearch or similar
            if (isset($criteria['user_id'])) {
                $userId = $criteria['user_id'];
                $pattern = "correlation:*:metadata";

                $redis = Cache::getRedis();
                $cursor = 0;

                do {
                    $keys = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                    $cursor = $keys[0];

                    foreach ($keys[1] as $key) {
                        $metadata = Cache::get($key);
                        if ($metadata && ($metadata['user_id'] ?? null) == $userId) {
                            preg_match('/correlation:(.+):metadata/', $key, $matches);
                            if (isset($matches[1])) {
                                $results[] = $matches[1];
                            }
                        }
                    }
                } while ($cursor !== 0);
            }

            return $results;

        } catch (Exception $e) {
            Log::warning("Failed to search correlations", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Clean up old correlations (expired)
     *
     * Removes correlation data older than 24 hours
     *
     * @return array Cleanup results
     */
    public static function cleanup(): array
    {
        try {
            $results = [
                'cleaned' => 0,
                'errors' => 0,
            ];

            $redis = Cache::getRedis();
            $patterns = [
                'correlation:*:metadata',
                'correlation:*:operations',
                'correlation:*:failure',
                'correlation:*:success',
            ];

            foreach ($patterns as $pattern) {
                $cursor = 0;

                do {
                    $keys = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                    $cursor = $keys[0];

                    foreach ($keys[1] as $key) {
                        try {
                            $redis->del($key);
                            $results['cleaned']++;
                        } catch (Exception $e) {
                            $results['errors']++;
                        }
                    }
                } while ($cursor !== 0);
            }

            Log::info("Correlation cleanup completed", $results);
            return $results;

        } catch (Exception $e) {
            Log::warning("Failed to cleanup correlations", [
                'error' => $e->getMessage(),
            ]);
            return ['cleaned' => 0, 'errors' => 1];
        }
    }

    /**
     * Get correlation statistics
     *
     * @return array Statistics
     */
    public static function getStatistics(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info('keyspace');

            $correlationCount = 0;
            $pattern = "correlation:*:metadata";

            $cursor = 0;
            do {
                $keys = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                $cursor = $keys[0];
                $correlationCount += count($keys[1]);
            } while ($cursor !== 0);

            return [
                'total_correlations' => $correlationCount,
                'redis_memory' => $info['used_memory_human'] ?? 'N/A',
                'timestamp' => now()->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get correlation statistics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
