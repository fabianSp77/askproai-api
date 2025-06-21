<?php

namespace App\Services\Setup;

use Illuminate\Support\Facades\Log;
use Exception;

class RetryableApiService
{
    private int $maxRetries = 3;
    private int $retryDelay = 1000; // milliseconds
    private array $retryableExceptions = [
        \GuzzleHttp\Exception\ConnectException::class,
        \GuzzleHttp\Exception\ServerException::class,
        \Illuminate\Http\Client\ConnectionException::class,
    ];

    /**
     * Execute an API call with retry logic
     */
    public function executeWithRetry(callable $callback, string $operation = 'API call'): mixed
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                Log::debug("Attempting {$operation}", ['attempt' => $attempt]);
                
                $result = $callback();
                
                if ($attempt > 1) {
                    Log::info("{$operation} succeeded after {$attempt} attempts");
                }
                
                return $result;
                
            } catch (Exception $e) {
                $lastException = $e;
                
                if (!$this->isRetryable($e) || $attempt === $this->maxRetries) {
                    Log::error("{$operation} failed permanently", [
                        'attempt' => $attempt,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
                
                $delay = $this->getRetryDelay($attempt);
                Log::warning("{$operation} failed, retrying in {$delay}ms", [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                
                usleep($delay * 1000);
            }
        }
        
        throw $lastException ?? new Exception("{$operation} failed after {$this->maxRetries} attempts");
    }

    /**
     * Check if an exception is retryable
     */
    private function isRetryable(Exception $e): bool
    {
        foreach ($this->retryableExceptions as $retryableClass) {
            if ($e instanceof $retryableClass) {
                return true;
            }
        }
        
        // Check for specific HTTP status codes
        if (method_exists($e, 'getCode')) {
            $code = $e->getCode();
            // Retry on 429 (Too Many Requests), 502 (Bad Gateway), 503 (Service Unavailable), 504 (Gateway Timeout)
            if (in_array($code, [429, 502, 503, 504])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate retry delay with exponential backoff
     */
    private function getRetryDelay(int $attempt): int
    {
        // Exponential backoff: 1s, 2s, 4s...
        $baseDelay = $this->retryDelay * pow(2, $attempt - 1);
        
        // Add jitter to prevent thundering herd
        $jitter = rand(0, 500);
        
        return min($baseDelay + $jitter, 10000); // Max 10 seconds
    }

    /**
     * Set custom retry configuration
     */
    public function configure(int $maxRetries, int $retryDelay): self
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        return $this;
    }
}