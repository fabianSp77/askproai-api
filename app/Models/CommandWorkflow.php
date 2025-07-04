<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommandWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'company_id',
        'created_by',
        'is_public',
        'is_active',
        'usage_count',
        'avg_execution_time',
        'success_rate',
        'config',
        'schedule',
        'metadata'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'avg_execution_time' => 'float',
        'success_rate' => 'float',
        'config' => 'array',
        'schedule' => 'array',
        'metadata' => 'array'
    ];

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Commands in this workflow
     */
    public function commands(): BelongsToMany
    {
        return $this->belongsToMany(CommandTemplate::class, 'workflow_commands')
            ->withPivot('order', 'config', 'condition')
            ->orderBy('order');
    }

    /**
     * Execution history
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    /**
     * Users who favorited this workflow
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workflow_favorites')
            ->withTimestamps();
    }

    /**
     * Add a command to the workflow
     */
    public function addCommand(CommandTemplate $command, int $order, array $config = [], string $condition = null)
    {
        $this->commands()->attach($command->id, [
            'order' => $order,
            'config' => json_encode($config),
            'condition' => $condition
        ]);
    }

    /**
     * Execute the workflow
     */
    public function execute(User $user, array $parameters = [])
    {
        $execution = WorkflowExecution::create([
            'command_workflow_id' => $this->id,
            'user_id' => $user->id,
            'company_id' => $this->company_id,
            'status' => WorkflowExecution::STATUS_PENDING,
            'parameters' => $parameters
        ]);

        // Dispatch job to execute workflow
        \App\Jobs\ExecuteWorkflowJob::dispatch($execution);

        return $execution;
    }

    /**
     * Scope for active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for scheduled workflows
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('schedule');
    }

    /**
     * Check if workflow should run based on schedule
     */
    public function shouldRunNow(): bool
    {
        if (!$this->schedule) {
            return false;
        }

        // Implement cron-like scheduling logic
        // This is a simplified version
        $schedule = $this->schedule;
        
        if (isset($schedule['daily_at'])) {
            return now()->format('H:i') === $schedule['daily_at'];
        }

        if (isset($schedule['hourly'])) {
            return now()->minute === 0;
        }

        return false;
    }
}