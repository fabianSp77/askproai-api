<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'command_template_id',
        'user_id',
        'company_id',
        'workflow_execution_id',
        'parameters',
        'status',
        'progress',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'duration_ms',
        'output',
        'error_message',
        'metadata',
        'ip_address',
        'user_agent',
        'correlation_id'
    ];

    protected $casts = [
        'parameters' => 'array',
        'output' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'execution_time_ms' => 'integer'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the command template
     */
    public function commandTemplate(): BelongsTo
    {
        return $this->belongsTo(CommandTemplate::class);
    }

    /**
     * Get the user who executed this command
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
     * Get the workflow execution if part of a workflow
     */
    public function workflowExecution(): BelongsTo
    {
        return $this->belongsTo(WorkflowExecution::class);
    }

    /**
     * Scope for successful executions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope for failed executions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Calculate execution time
     */
    public function calculateExecutionTime()
    {
        if ($this->started_at && $this->completed_at) {
            $this->execution_time_ms = $this->started_at->diffInMilliseconds($this->completed_at);
            $this->save();
        }
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
        broadcast(new \App\Events\CommandExecutionStatusUpdated($this));
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
        
        // Update command statistics
        $this->commandTemplate->recordUsage(true, $this->execution_time_ms);
        
        // Broadcast status change
        broadcast(new \App\Events\CommandExecutionStatusUpdated($this));
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($error)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $error
        ]);
        
        $this->calculateExecutionTime();
        
        // Update command statistics
        $this->commandTemplate->recordUsage(false, $this->execution_time_ms);
        
        // Broadcast status change
        broadcast(new \App\Events\CommandExecutionStatusUpdated($this));
    }
}