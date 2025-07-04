<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'command_workflow_id',
        'user_id',
        'company_id',
        'status',
        'parameters',
        'started_at',
        'completed_at',
        'current_step',
        'current_command_index',
        'total_steps',
        'output',
        'error_message',
        'execution_time_ms',
        'duration_ms',
        'metadata'
    ];

    protected $casts = [
        'parameters' => 'array',
        'output' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'execution_time_ms' => 'integer'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAUSED = 'paused';

    /**
     * Get the workflow
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(CommandWorkflow::class, 'command_workflow_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get command executions for this workflow
     */
    public function commandExecutions(): HasMany
    {
        return $this->hasMany(CommandExecution::class, 'workflow_execution_id');
    }

    /**
     * Mark as running
     */
    public function markAsRunning()
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now()
        ]);
        
        // Broadcast status change
        broadcast(new \App\Events\WorkflowExecutionStatusUpdated($this));
    }

    /**
     * Update progress
     */
    public function updateProgress(int $currentStep)
    {
        $this->update([
            'current_step' => $currentStep
        ]);
        
        // Broadcast status change
        broadcast(new \App\Events\WorkflowExecutionStatusUpdated($this));
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted($output = null)
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'completed_at' => now(),
            'output' => $output
        ]);
        
        $this->calculateExecutionTime();
        
        // Update workflow statistics
        $this->workflow->increment('usage_count');
        $this->updateWorkflowStats(true);
        
        // Broadcast status change
        broadcast(new \App\Events\WorkflowExecutionStatusUpdated($this));
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($error, $step = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $error,
            'current_step' => $step ?? $this->current_step
        ]);
        
        $this->calculateExecutionTime();
        
        // Update workflow statistics
        $this->workflow->increment('usage_count');
        $this->updateWorkflowStats(false);
        
        // Broadcast status change
        broadcast(new \App\Events\WorkflowExecutionStatusUpdated($this));
    }

    /**
     * Calculate execution time
     */
    protected function calculateExecutionTime()
    {
        if ($this->started_at && $this->completed_at) {
            $this->execution_time_ms = $this->started_at->diffInMilliseconds($this->completed_at);
            $this->save();
        }
    }

    /**
     * Update workflow statistics
     */
    protected function updateWorkflowStats(bool $success)
    {
        $workflow = $this->workflow;
        
        // Update average execution time
        if ($this->execution_time_ms) {
            $totalTime = $workflow->avg_execution_time * ($workflow->usage_count - 1) + $this->execution_time_ms;
            $workflow->avg_execution_time = $totalTime / $workflow->usage_count;
        }
        
        // Update success rate
        $successCount = $workflow->success_rate * ($workflow->usage_count - 1) / 100;
        if ($success) $successCount++;
        $workflow->success_rate = ($successCount / $workflow->usage_count) * 100;
        
        $workflow->save();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_steps === 0) {
            return 0;
        }
        
        return ($this->current_step / $this->total_steps) * 100;
    }
    
    /**
     * Get current command being executed
     */
    public function getCurrentCommand()
    {
        if (!$this->workflow || $this->current_command_index === null) {
            return null;
        }
        
        $commands = $this->workflow->commands()
            ->orderBy('workflow_commands.order')
            ->get();
            
        return $commands->get($this->current_command_index);
    }
}