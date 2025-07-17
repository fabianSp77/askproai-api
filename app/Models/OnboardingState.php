<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingState extends Model
{
    protected $fillable = [
        'company_id',
        'current_step',
        'completed_steps',
        'state_data',
        'time_elapsed',
        'is_completed',
        'completed_at',
        'industry_template',
        'completion_percentage',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'state_data' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if a step is completed.
     */
    public function isStepCompleted(int $step): bool
    {
        return in_array($step, $this->completed_steps ?? []);
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(int $step): self
    {
        $steps = $this->completed_steps ?? [];
        if (!in_array($step, $steps)) {
            $steps[] = $step;
            $this->completed_steps = $steps;
            $this->updateCompletionPercentage();
            $this->save();
        }
        return $this;
    }

    /**
     * Update completion percentage.
     */
    public function updateCompletionPercentage(): void
    {
        $totalSteps = 7; // Total number of onboarding steps
        $completedCount = count($this->completed_steps ?? []);
        $this->completion_percentage = round(($completedCount / $totalSteps) * 100);
    }

    /**
     * Get state data for a specific key.
     */
    public function getStateData(string $key, $default = null)
    {
        return data_get($this->state_data, $key, $default);
    }

    /**
     * Set state data for a specific key.
     */
    public function setStateData(string $key, $value): self
    {
        $data = $this->state_data ?? [];
        data_set($data, $key, $value);
        $this->state_data = $data;
        return $this;
    }

    /**
     * Complete the onboarding.
     */
    public function markAsCompleted(): self
    {
        $this->is_completed = true;
        $this->completed_at = now();
        $this->completion_percentage = 100;
        $this->save();
        
        return $this;
    }

    /**
     * Get time elapsed in human readable format.
     */
    public function getFormattedTimeElapsedAttribute(): string
    {
        $minutes = floor($this->time_elapsed / 60);
        $seconds = $this->time_elapsed % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Check if onboarding is within time limit.
     */
    public function isWithinTimeLimit(): bool
    {
        return $this->time_elapsed <= 300; // 5 minutes
    }
}