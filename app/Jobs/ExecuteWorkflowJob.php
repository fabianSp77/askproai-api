<?php

namespace App\Jobs;

use App\Models\WorkflowExecution;
use App\Models\CommandExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WorkflowExecution $execution;

    /**
     * Create a new job instance.
     */
    public function __construct(WorkflowExecution $execution)
    {
        $this->execution = $execution;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Mark workflow as running
            $this->execution->markAsRunning();
            
            // Get workflow with commands
            $workflow = $this->execution->workflow()->with('commands')->first();
            
            // Set total steps
            $this->execution->update(['total_steps' => $workflow->commands->count()]);
            
            // Execute each command in order
            $workflowOutput = [];
            $currentStep = 0;
            
            foreach ($workflow->commands as $command) {
                $currentStep++;
                $this->execution->updateProgress($currentStep);
                
                // Check condition if specified
                $pivotData = $command->pivot;
                if ($pivotData->condition && !$this->evaluateCondition($pivotData->condition, $workflowOutput)) {
                    Log::info("Skipping command due to condition", [
                        'workflow_execution_id' => $this->execution->id,
                        'command_id' => $command->id,
                        'condition' => $pivotData->condition
                    ]);
                    continue;
                }
                
                // Get config from pivot
                $config = json_decode($pivotData->config, true) ?? [];
                
                // Merge workflow parameters with command config
                $parameters = array_merge(
                    $this->execution->parameters ?? [],
                    $config['parameters'] ?? []
                );
                
                // Create command execution
                $commandExecution = CommandExecution::create([
                    'command_template_id' => $command->id,
                    'user_id' => $this->execution->user_id,
                    'company_id' => $this->execution->company_id,
                    'workflow_execution_id' => $this->execution->id,
                    'parameters' => $parameters,
                    'status' => CommandExecution::STATUS_PENDING,
                    'correlation_id' => $this->execution->id . '_' . $currentStep,
                ]);
                
                // Execute command synchronously
                try {
                    ExecuteCommandJob::dispatchSync($commandExecution);
                    
                    // Refresh to get results
                    $commandExecution->refresh();
                    
                    if ($commandExecution->status === CommandExecution::STATUS_FAILED) {
                        throw new \Exception("Command failed: " . $commandExecution->error_message);
                    }
                    
                    // Store output for next commands
                    $workflowOutput["step_{$currentStep}"] = $commandExecution->output;
                    
                } catch (\Exception $e) {
                    // Mark workflow as failed
                    $this->execution->markAsFailed($e->getMessage(), $currentStep);
                    throw $e;
                }
            }
            
            // Mark workflow as completed
            $this->execution->markAsCompleted($workflowOutput);
            
            Log::info('Workflow executed successfully', [
                'execution_id' => $this->execution->id,
                'workflow_id' => $workflow->id,
                'total_steps' => $currentStep
            ]);
            
        } catch (Throwable $e) {
            // Already marked as failed in the catch block above
            Log::error('Workflow execution failed', [
                'execution_id' => $this->execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Evaluate condition based on previous outputs
     */
    protected function evaluateCondition(string $condition, array $outputs): bool
    {
        // Simple condition evaluation
        // Format: "step_1.success == true" or "step_2.output.value > 10"
        
        try {
            // For now, simple implementation
            // In production, use a proper expression evaluator
            
            if ($condition === 'always') {
                return true;
            }
            
            if ($condition === 'never') {
                return false;
            }
            
            // Check for previous step success
            if (preg_match('/step_(\d+)\.success/', $condition, $matches)) {
                $stepNum = $matches[1];
                return isset($outputs["step_{$stepNum}"]['success']) && 
                       $outputs["step_{$stepNum}"]['success'] === true;
            }
            
            // Default to true if we can't evaluate
            return true;
            
        } catch (\Exception $e) {
            Log::warning('Failed to evaluate condition', [
                'condition' => $condition,
                'error' => $e->getMessage()
            ]);
            return true; // Continue on error
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ExecuteWorkflowJob failed completely', [
            'execution_id' => $this->execution->id,
            'exception' => $exception->getMessage()
        ]);
    }
}