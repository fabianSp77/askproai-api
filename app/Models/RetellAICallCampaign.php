<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Scopes\TenantScope;

class RetellAICallCampaign extends Model
{
    use HasFactory;

    protected $table = 'retell_ai_call_campaigns';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'agent_id',
        'target_type',
        'target_criteria',
        'schedule_type',
        'scheduled_at',
        'dynamic_variables',
        'status',
        'total_targets',
        'calls_completed',
        'calls_failed',
        'started_at',
        'completed_at',
        'created_by',
        'results',
    ];

    protected $casts = [
        'target_criteria' => 'array',
        'dynamic_variables' => 'array',
        'results' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company that owns the campaign.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the calls associated with this campaign.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'metadata->campaign_id');
    }

    /**
     * Scope a query to only include active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'running']);
    }

    /**
     * Scope a query to only include campaigns ready to run.
     */
    public function scopeReadyToRun($query)
    {
        return $query->where('status', 'scheduled')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_targets === 0) {
            return 0;
        }

        $completed = $this->calls_completed + $this->calls_failed;
        return round(($completed / $this->total_targets) * 100, 2);
    }

    /**
     * Get success rate.
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->calls_completed + $this->calls_failed;
        if ($total === 0) {
            return 0;
        }

        return round(($this->calls_completed / $total) * 100, 2);
    }

    /**
     * Check if campaign can be started.
     */
    public function canStart(): bool
    {
        return $this->status === 'draft' && $this->total_targets > 0;
    }

    /**
     * Check if campaign can be paused.
     */
    public function canPause(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if campaign can be resumed.
     */
    public function canResume(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if campaign is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || 
               ($this->total_targets > 0 && $this->completion_percentage >= 100);
    }
}