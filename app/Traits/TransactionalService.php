<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

trait TransactionalService
{
    /**
     * Execute a callback within a database transaction with proper error handling
     *
     * @param callable $callback
     * @param array $context Additional context for logging
     * @param int $attempts Number of retry attempts for deadlocks
     * @return mixed
     * @throws Throwable
     */
    protected function executeInTransaction(callable $callback, array $context = [], int $attempts = 1)
    {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $attempts) {
            $attempt++;
            
            try {
                DB::beginTransaction();
                
                // Log transaction start
                Log::debug('Transaction started', array_merge([
                    'service' => static::class,
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                ], $context));
                
                // Execute the callback
                $result = $callback();
                
                // Commit the transaction
                DB::commit();
                
                // Log successful transaction
                Log::debug('Transaction committed successfully', array_merge([
                    'service' => static::class,
                    'attempt' => $attempt,
                ], $context));
                
                return $result;
                
            } catch (Throwable $e) {
                // Rollback the transaction
                DB::rollBack();
                
                // Log the rollback event
                Log::error('Transaction rolled back', array_merge([
                    'service' => static::class,
                    'attempt' => $attempt,
                    'exception' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ], $context));
                
                $lastException = $e;
                
                // Check if it's a deadlock and we should retry
                if ($this->isDeadlockException($e) && $attempt < $attempts) {
                    Log::warning('Deadlock detected, retrying transaction', [
                        'service' => static::class,
                        'attempt' => $attempt,
                        'next_attempt' => $attempt + 1,
                    ]);
                    
                    // Wait a bit before retrying
                    usleep(100000 * $attempt); // 100ms * attempt number
                    continue;
                }
                
                // If not a deadlock or no more attempts, throw the exception
                throw $e;
            }
        }
        
        // If we've exhausted all attempts, throw the last exception
        if ($lastException) {
            throw $lastException;
        }
    }
    
    /**
     * Execute a callback within a transaction and return a default value on failure
     *
     * @param callable $callback
     * @param mixed $default Default value to return on failure
     * @param array $context Additional context for logging
     * @return mixed
     */
    protected function executeInTransactionOrDefault(callable $callback, $default = null, array $context = [])
    {
        try {
            return $this->executeInTransaction($callback, $context);
        } catch (Throwable $e) {
            Log::warning('Transaction failed, returning default value', array_merge([
                'service' => static::class,
                'default_value' => $default,
                'exception' => $e->getMessage(),
            ], $context));
            
            return $default;
        }
    }
    
    /**
     * Execute multiple operations in a single transaction
     *
     * @param array $operations Array of callables to execute
     * @param array $context Additional context for logging
     * @return array Results from each operation
     * @throws Throwable
     */
    protected function executeMultipleInTransaction(array $operations, array $context = []): array
    {
        return $this->executeInTransaction(function () use ($operations, $context) {
            $results = [];
            
            foreach ($operations as $key => $operation) {
                if (!is_callable($operation)) {
                    throw new \InvalidArgumentException("Operation at key '{$key}' is not callable");
                }
                
                Log::debug('Executing transaction operation', array_merge([
                    'service' => static::class,
                    'operation_key' => $key,
                ], $context));
                
                $results[$key] = $operation();
            }
            
            return $results;
        }, $context);
    }
    
    /**
     * Check if an exception is a database deadlock
     *
     * @param Throwable $e
     * @return bool
     */
    protected function isDeadlockException(Throwable $e): bool
    {
        $message = $e->getMessage();
        
        // MySQL/MariaDB deadlock error codes
        if (str_contains($message, '1213') || str_contains($message, 'Deadlock found')) {
            return true;
        }
        
        // PostgreSQL deadlock error
        if (str_contains($message, '40P01') || str_contains($message, 'deadlock detected')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log transaction metrics
     *
     * @param string $operation
     * @param float $startTime
     * @param bool $success
     * @param array $context
     */
    protected function logTransactionMetrics(string $operation, float $startTime, bool $success, array $context = []): void
    {
        $duration = microtime(true) - $startTime;
        
        Log::info('Transaction metrics', array_merge([
            'service' => static::class,
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'success' => $success,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ], $context));
    }
}