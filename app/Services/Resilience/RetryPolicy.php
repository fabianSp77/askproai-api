<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Log;
use Throwable;
use Closure;

/**
 * Retry Policy Service
 *
 * Implements exponential backoff with jitter for automatic retries
 *
 * STRATEGY:
 * - 1st retry: 1 second
 * - 2nd retry: 2 seconds
 * - 3rd retry: 4 seconds
 * - Optional jitter: +/- 10%
 *
 * Backoff delay = base_delay * (2 ^ attempt_number)
 */
class RetryPolicy
{
    private array $config;
    private int $attempt = 0;

    public function __construct()
    {
        $this->config = config('appointments.retry', [
            'max_attempts' => 3,
            'delays' => [1, 2, 4],
            'transient_errors' => ['timeout', '429', '5xx'],
            'jitter' => true,
        ]);
    }

    /**
     * Execute operation with automatic retries
     *
     * @param Closure $operation Callable that may throw exceptions
     * @param ?string $correlationId For structured logging
     * @return mixed Result of successful operation
     * @throws Throwable If all retries exhausted
     */
    public function execute(Closure $operation, ?string $correlationId = null): mixed
    {
        $correlationId = $correlationId ?: uniqid('retry_');
        $this->attempt = 0;

        while ($this->attempt <= $this->config['max_attempts']) {
            try {
                $result = $operation();

                if ($this->attempt > 0) {
                    Log::info('Operation succeeded after retry', [
                        'correlation_id' => $correlationId,
                        'attempts' => $this->attempt + 1,
                    ]);
                }

                return $result;

            } catch (Throwable $e) {
                $this->attempt++;

                if (!$this->shouldRetry($e, $this->attempt)) {
                    Log::error('Operation failed - not retryable', [
                        'correlation_id' => $correlationId,
                        'error' => $e->getMessage(),
                        'attempts' => $this->attempt,
                    ]);
                    throw $e;
                }

                if ($this->attempt > $this->config['max_attempts']) {
                    Log::error('Operation failed - max retries exceeded', [
                        'correlation_id' => $correlationId,
                        'error' => $e->getMessage(),
                        'attempts' => $this->attempt,
                        'max_attempts' => $this->config['max_attempts'],
                    ]);
                    throw $e;
                }

                $delay = $this->getDelay($this->attempt - 1);
                Log::warning('Operation failed - retrying', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempt,
                    'next_retry_in_seconds' => $delay,
                ]);

                sleep($delay);
            }
        }

        throw new \Exception('Retry exhausted without result');
    }

    /**
     * Determine if exception is retryable
     *
     * @return bool True if should retry, false if permanent error
     */
    private function shouldRetry(Throwable $e, int $attempt): bool
    {
        if ($attempt > $this->config['max_attempts']) {
            return false;
        }

        $message = $e->getMessage();

        // Check for transient error indicators
        foreach ($this->config['transient_errors'] as $indicator) {
            if (str_contains(strtolower($message), strtolower($indicator))) {
                return true;
            }
        }

        // Check exception type
        $exceptionClass = get_class($e);
        return str_contains($exceptionClass, 'Timeout') ||
               str_contains($exceptionClass, 'Network') ||
               str_contains($exceptionClass, 'Connection');
    }

    /**
     * Calculate delay for exponential backoff
     *
     * Formula: base_delay * (2 ^ attempt_index)
     * With optional jitter: delay * (0.9 + 0.2 * rand())
     */
    private function getDelay(int $attemptIndex): int
    {
        $delays = $this->config['delays'];
        $delay = $delays[$attemptIndex] ?? end($delays);

        if ($this->config['jitter']) {
            // Add jitter: +/- 10%
            $jitter = $delay * (0.1 + 0.2 * (mt_rand() / mt_getrandmax()));
            $delay = (int)($delay + $jitter - ($delay * 0.1));
        }

        return max(1, $delay); // At least 1 second
    }

    /**
     * Get retry configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
