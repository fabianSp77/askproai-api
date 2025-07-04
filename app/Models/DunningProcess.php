<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class DunningProcess extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_id',
        'stripe_invoice_id',
        'stripe_subscription_id',
        'status',
        'started_at',
        'resolved_at',
        'retry_count',
        'max_retries',
        'next_retry_at',
        'last_retry_at',
        'original_amount',
        'remaining_amount',
        'currency',
        'failure_code',
        'failure_message',
        'service_paused',
        'service_paused_at',
        'manual_review_requested',
        'manual_review_requested_at',
        'metadata'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'service_paused_at' => 'datetime',
        'manual_review_requested_at' => 'datetime',
        'service_paused' => 'boolean',
        'manual_review_requested' => 'boolean',
        'metadata' => 'array',
        'original_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2'
    ];
    
    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_FAILED = 'failed';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Get the invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
    
    /**
     * Get activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DunningActivity::class);
    }
    
    /**
     * Log an activity
     */
    public function logActivity(
        string $type, 
        string $description, 
        array $details = [], 
        bool $successful = true,
        ?string $errorMessage = null,
        ?string $performedBy = null
    ): DunningActivity
    {
        return $this->activities()->create([
            'company_id' => $this->company_id,
            'type' => $type,
            'description' => $description,
            'details' => $details,
            'successful' => $successful,
            'error_message' => $errorMessage,
            'performed_by' => $performedBy ?? 'system'
        ]);
    }
    
    /**
     * Schedule next retry
     */
    public function scheduleNextRetry(int $daysDelay): void
    {
        $this->next_retry_at = now()->addDays($daysDelay);
        $this->save();
        
        $this->logActivity(
            'retry_scheduled',
            "Next retry scheduled for {$this->next_retry_at->format('Y-m-d H:i')}",
            ['days_delay' => $daysDelay]
        );
    }
    
    /**
     * Mark retry as attempted
     */
    public function markRetryAttempted(): void
    {
        $this->increment('retry_count');
        $this->last_retry_at = now();
        $this->save();
    }
    
    /**
     * Mark as resolved
     */
    public function markAsResolved(string $description = 'Payment received'): void
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolved_at = now();
        $this->remaining_amount = 0;
        $this->save();
        
        $this->logActivity('retry_succeeded', $description);
        
        // Resume service if paused
        if ($this->service_paused) {
            $this->resumeService();
        }
    }
    
    /**
     * Mark as failed permanently
     */
    public function markAsFailed(string $reason): void
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
        
        $this->logActivity(
            'retry_failed',
            'Dunning process failed permanently',
            ['reason' => $reason],
            false
        );
    }
    
    /**
     * Pause service
     */
    public function pauseService(): void
    {
        if ($this->service_paused) {
            return;
        }
        
        $this->service_paused = true;
        $this->service_paused_at = now();
        $this->save();
        
        // Update company billing status
        $this->company->update([
            'billing_status' => 'suspended',
            'billing_suspended_at' => now()
        ]);
        
        $this->logActivity('service_paused', 'Service paused due to payment failure');
    }
    
    /**
     * Resume service
     */
    public function resumeService(): void
    {
        if (!$this->service_paused) {
            return;
        }
        
        $this->service_paused = false;
        $this->save();
        
        // Update company billing status
        $this->company->update([
            'billing_status' => 'active',
            'billing_suspended_at' => null
        ]);
        
        $this->logActivity('service_resumed', 'Service resumed after payment');
    }
    
    /**
     * Request manual review
     */
    public function requestManualReview(): void
    {
        $this->manual_review_requested = true;
        $this->manual_review_requested_at = now();
        $this->status = self::STATUS_PAUSED;
        $this->save();
        
        $this->logActivity(
            'manual_review_requested',
            'Manual review requested after multiple failed attempts'
        );
    }
    
    /**
     * Check if can retry
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->retry_count < $this->max_retries &&
               !$this->manual_review_requested;
    }
    
    /**
     * Get days since failure
     */
    public function getDaysSinceFailure(): int
    {
        return $this->started_at->diffInDays(now());
    }
    
    /**
     * Scope for active processes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
    
    /**
     * Scope for processes due for retry
     */
    public function scopeDueForRetry($query)
    {
        return $query->active()
            ->where('next_retry_at', '<=', now())
            ->whereNull('manual_review_requested_at');
    }
}