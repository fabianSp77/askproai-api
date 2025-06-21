<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Distributed Transaction Manager for MCP Services
 * Implements Saga pattern for distributed transactions
 */
class DistributedTransactionManager
{
    protected string $transactionId;
    protected array $operations = [];
    protected array $completedOperations = [];
    protected array $compensations = [];
    protected bool $isStarted = false;
    protected bool $isCompleted = false;
    protected ?string $correlationId;
    
    public function __construct(string $correlationId = null)
    {
        $this->transactionId = Str::uuid()->toString();
        $this->correlationId = $correlationId ?? Str::uuid()->toString();
    }
    
    /**
     * Begin distributed transaction
     */
    public function begin(): self
    {
        if ($this->isStarted) {
            throw new \RuntimeException('Transaction already started');
        }
        
        $this->isStarted = true;
        
        // Store transaction state in cache
        $this->saveState();
        
        Log::info('Distributed transaction started', [
            'transaction_id' => $this->transactionId,
            'correlation_id' => $this->correlationId,
        ]);
        
        return $this;
    }
    
    /**
     * Add operation to transaction
     */
    public function addOperation(
        string $service,
        callable $operation,
        callable $compensation = null,
        array $metadata = []
    ): self {
        if (!$this->isStarted) {
            throw new \RuntimeException('Transaction not started');
        }
        
        if ($this->isCompleted) {
            throw new \RuntimeException('Transaction already completed');
        }
        
        $operationId = Str::uuid()->toString();
        
        $this->operations[$operationId] = [
            'id' => $operationId,
            'service' => $service,
            'operation' => $operation,
            'compensation' => $compensation,
            'metadata' => $metadata,
            'status' => 'pending',
            'result' => null,
            'error' => null,
        ];
        
        if ($compensation) {
            $this->compensations[$operationId] = $compensation;
        }
        
        $this->saveState();
        
        return $this;
    }
    
    /**
     * Execute all operations in the transaction
     */
    public function execute(): array
    {
        if (!$this->isStarted) {
            throw new \RuntimeException('Transaction not started');
        }
        
        if ($this->isCompleted) {
            throw new \RuntimeException('Transaction already completed');
        }
        
        $results = [];
        $failed = false;
        $failedOperation = null;
        
        // Execute operations in order
        foreach ($this->operations as $operationId => $operation) {
            try {
                Log::debug('Executing distributed operation', [
                    'transaction_id' => $this->transactionId,
                    'operation_id' => $operationId,
                    'service' => $operation['service'],
                ]);
                
                // Execute the operation
                $result = call_user_func($operation['operation']);
                
                // Mark as completed
                $this->operations[$operationId]['status'] = 'completed';
                $this->operations[$operationId]['result'] = $result;
                $this->completedOperations[] = $operationId;
                
                $results[$operationId] = [
                    'success' => true,
                    'result' => $result,
                    'service' => $operation['service'],
                ];
                
                // Update state after each successful operation
                $this->saveState();
                
            } catch (\Exception $e) {
                // Operation failed
                $this->operations[$operationId]['status'] = 'failed';
                $this->operations[$operationId]['error'] = $e->getMessage();
                
                $results[$operationId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'service' => $operation['service'],
                ];
                
                $failed = true;
                $failedOperation = $operationId;
                
                Log::error('Distributed operation failed', [
                    'transaction_id' => $this->transactionId,
                    'operation_id' => $operationId,
                    'service' => $operation['service'],
                    'error' => $e->getMessage(),
                ]);
                
                break;
            }
        }
        
        // If any operation failed, rollback
        if ($failed) {
            $this->rollback($failedOperation);
        } else {
            $this->commit();
        }
        
        return [
            'transaction_id' => $this->transactionId,
            'success' => !$failed,
            'operations' => $results,
            'completed_operations' => count($this->completedOperations),
            'total_operations' => count($this->operations),
        ];
    }
    
    /**
     * Commit the transaction
     */
    public function commit(): void
    {
        if (!$this->isStarted) {
            throw new \RuntimeException('Transaction not started');
        }
        
        $this->isCompleted = true;
        
        // Clear transaction state from cache
        $this->clearState();
        
        Log::info('Distributed transaction committed', [
            'transaction_id' => $this->transactionId,
            'operations_count' => count($this->completedOperations),
        ]);
    }
    
    /**
     * Rollback the transaction
     */
    public function rollback(string $failedOperationId = null): void
    {
        if (!$this->isStarted) {
            return;
        }
        
        Log::warning('Starting distributed transaction rollback', [
            'transaction_id' => $this->transactionId,
            'failed_operation' => $failedOperationId,
            'completed_operations' => count($this->completedOperations),
        ]);
        
        // Execute compensations in reverse order
        $operationsToCompensate = array_reverse($this->completedOperations);
        
        foreach ($operationsToCompensate as $operationId) {
            if (isset($this->compensations[$operationId])) {
                try {
                    Log::debug('Executing compensation', [
                        'transaction_id' => $this->transactionId,
                        'operation_id' => $operationId,
                    ]);
                    
                    call_user_func($this->compensations[$operationId]);
                    
                    $this->operations[$operationId]['status'] = 'compensated';
                    
                } catch (\Exception $e) {
                    Log::error('Compensation failed', [
                        'transaction_id' => $this->transactionId,
                        'operation_id' => $operationId,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Continue with other compensations even if one fails
                    $this->operations[$operationId]['status'] = 'compensation_failed';
                    $this->operations[$operationId]['compensation_error'] = $e->getMessage();
                }
            }
        }
        
        $this->isCompleted = true;
        $this->clearState();
        
        Log::info('Distributed transaction rolled back', [
            'transaction_id' => $this->transactionId,
            'compensations_executed' => count($operationsToCompensate),
        ]);
    }
    
    /**
     * Save transaction state to cache
     */
    protected function saveState(): void
    {
        $state = [
            'transaction_id' => $this->transactionId,
            'correlation_id' => $this->correlationId,
            'operations' => $this->operations,
            'completed_operations' => $this->completedOperations,
            'is_started' => $this->isStarted,
            'is_completed' => $this->isCompleted,
            'updated_at' => now()->toIso8601String(),
        ];
        
        // Store for 1 hour
        Cache::put(
            "mcp:transaction:{$this->transactionId}",
            $state,
            3600
        );
    }
    
    /**
     * Clear transaction state from cache
     */
    protected function clearState(): void
    {
        Cache::forget("mcp:transaction:{$this->transactionId}");
    }
    
    /**
     * Restore transaction from cache
     */
    public static function restore(string $transactionId): ?self
    {
        $state = Cache::get("mcp:transaction:{$transactionId}");
        
        if (!$state) {
            return null;
        }
        
        $manager = new self($state['correlation_id']);
        $manager->transactionId = $state['transaction_id'];
        $manager->operations = $state['operations'];
        $manager->completedOperations = $state['completed_operations'];
        $manager->isStarted = $state['is_started'];
        $manager->isCompleted = $state['is_completed'];
        
        // Rebuild compensations
        foreach ($manager->operations as $operationId => $operation) {
            if (isset($operation['compensation'])) {
                $manager->compensations[$operationId] = $operation['compensation'];
            }
        }
        
        return $manager;
    }
    
    /**
     * Get transaction status
     */
    public function getStatus(): array
    {
        return [
            'transaction_id' => $this->transactionId,
            'correlation_id' => $this->correlationId,
            'is_started' => $this->isStarted,
            'is_completed' => $this->isCompleted,
            'total_operations' => count($this->operations),
            'completed_operations' => count($this->completedOperations),
            'operations' => array_map(function ($op) {
                return [
                    'id' => $op['id'],
                    'service' => $op['service'],
                    'status' => $op['status'],
                    'error' => $op['error'] ?? null,
                ];
            }, $this->operations),
        ];
    }
    
    /**
     * Execute with automatic retry
     */
    public function executeWithRetry(int $maxRetries = 3): array
    {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $maxRetries) {
            try {
                return $this->execute();
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt < $maxRetries) {
                    // Exponential backoff
                    $delay = pow(2, $attempt) * 1000; // milliseconds
                    usleep($delay * 1000);
                    
                    Log::warning('Retrying distributed transaction', [
                        'transaction_id' => $this->transactionId,
                        'attempt' => $attempt,
                        'delay_ms' => $delay,
                    ]);
                }
            }
        }
        
        throw $lastError ?? new \RuntimeException('Transaction failed after retries');
    }
}