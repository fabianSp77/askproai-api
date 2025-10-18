<?php

namespace App\Services\Saga;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Saga Orchestrator - Manages multi-step distributed transactions
 *
 * Implements the Saga pattern for complex operations that span
 * multiple services (local DB, Cal.com API, cache, etc).
 *
 * Provides atomic "all-or-nothing" semantics through compensating transactions.
 *
 * @see https://microservices.io/patterns/data/saga.html
 */
class SagaOrchestrator
{
    /**
     * @var string Unique saga ID for tracking and idempotency
     */
    private string $sagaId;

    /**
     * @var array Completed steps with their results
     */
    private array $completedSteps = [];

    /**
     * @var array Compensation handlers for rollback
     */
    private array $compensations = [];

    /**
     * @var string Current saga operation name
     */
    private string $operationName;

    public function __construct(string $operationName, string $sagaId = null)
    {
        $this->operationName = $operationName;
        $this->sagaId = $sagaId ?? uniqid('saga_', true);

        Log::channel('saga')->info('🔄 Starting saga', [
            'saga_id' => $this->sagaId,
            'operation' => $this->operationName,
        ]);
    }

    /**
     * Execute a step and register its compensation handler
     *
     * If step succeeds: registers compensation for later rollback
     * If step fails: executes all compensations in reverse order
     *
     * @param string $stepName Human-readable step name
     * @param callable $action Step execution (must return result)
     * @param callable $compensation Rollback handler (receives step result)
     * @return mixed Result from action
     * @throws Exception If step fails and compensation also fails
     */
    public function executeStep(string $stepName, callable $action, callable $compensation): mixed
    {
        try {
            Log::channel('saga')->info('▶️ Executing step', [
                'saga_id' => $this->sagaId,
                'step' => $stepName,
                'step_count' => count($this->completedSteps) + 1,
            ]);

            // Execute the step
            $result = $action();

            // Register compensation handler for this step
            $this->completedSteps[$stepName] = $result;
            $this->compensations[$stepName] = $compensation;

            Log::channel('saga')->info('✅ Step completed', [
                'saga_id' => $this->sagaId,
                'step' => $stepName,
                'result_type' => gettype($result),
            ]);

            return $result;

        } catch (Exception $e) {
            Log::channel('saga')->error('❌ Step failed, initiating compensation', [
                'saga_id' => $this->sagaId,
                'step' => $stepName,
                'error' => $e->getMessage(),
                'completed_steps' => array_keys($this->completedSteps),
            ]);

            // Step failed - execute all compensations in REVERSE order
            $this->compensate();

            // Re-throw with saga context
            throw new SagaException(
                "Saga '{$this->operationName}' failed at step '{$stepName}': {$e->getMessage()}",
                sagaId: $this->sagaId,
                failedStep: $stepName,
                completedSteps: array_keys($this->completedSteps),
                previousException: $e
            );
        }
    }

    /**
     * Execute remaining steps conditionally with compensation
     *
     * Useful for optional steps that might fail gracefully
     *
     * @param string $stepName Step identifier
     * @param callable $action Step execution
     * @param callable $compensation Rollback handler
     * @param bool $required If false, failure logs warning but doesn't rollback
     * @return mixed|null Result or null if optional step fails
     */
    public function executeOptionalStep(
        string $stepName,
        callable $action,
        callable $compensation,
        bool $required = false
    ): mixed {
        try {
            return $this->executeStep($stepName, $action, $compensation);
        } catch (Exception $e) {
            if ($required) {
                throw $e;
            }

            Log::channel('saga')->warning('⚠️ Optional step failed', [
                'saga_id' => $this->sagaId,
                'step' => $stepName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Manually compensate (rollback) all completed steps
     * Executes compensations in REVERSE order (last step first)
     *
     * @throws SagaCompensationException If any compensation fails
     */
    public function compensate(): void
    {
        $stepsToCompensate = array_reverse($this->completedSteps, preserve_keys: true);
        $failedCompensations = [];

        foreach ($stepsToCompensate as $stepName => $result) {
            if (!isset($this->compensations[$stepName])) {
                continue;
            }

            try {
                Log::channel('saga')->info('⏮️ Compensating step', [
                    'saga_id' => $this->sagaId,
                    'step' => $stepName,
                ]);

                $handler = $this->compensations[$stepName];
                $handler($result);

                Log::channel('saga')->info('✅ Compensation succeeded', [
                    'saga_id' => $this->sagaId,
                    'step' => $stepName,
                ]);

            } catch (Exception $e) {
                Log::channel('saga')->error('🚨 Compensation FAILED', [
                    'saga_id' => $this->sagaId,
                    'step' => $stepName,
                    'error' => $e->getMessage(),
                    'critical' => true,
                ]);

                $failedCompensations[$stepName] = $e;
            }
        }

        if (!empty($failedCompensations)) {
            throw new SagaCompensationException(
                "Failed to compensate saga '{$this->operationName}'",
                sagaId: $this->sagaId,
                failedCompensations: $failedCompensations
            );
        }
    }

    /**
     * Complete saga successfully
     * Use this to mark saga as successfully completed
     */
    public function complete(): void
    {
        Log::channel('saga')->info('🎉 Saga completed successfully', [
            'saga_id' => $this->sagaId,
            'operation' => $this->operationName,
            'steps' => array_keys($this->completedSteps),
        ]);
    }

    /**
     * Get saga ID for idempotency/tracking
     */
    public function getSagaId(): string
    {
        return $this->sagaId;
    }

    /**
     * Get operation name
     */
    public function getOperationName(): string
    {
        return $this->operationName;
    }

    /**
     * Get all completed steps
     */
    public function getCompletedSteps(): array
    {
        return $this->completedSteps;
    }
}
