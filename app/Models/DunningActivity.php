<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DunningActivity extends Model
{
    protected $fillable = [
        'dunning_process_id',
        'company_id',
        'type',
        'description',
        'details',
        'performed_by',
        'successful',
        'error_message'
    ];
    
    protected $casts = [
        'details' => 'array',
        'successful' => 'boolean'
    ];
    
    /**
     * Activity type constants
     */
    const TYPE_RETRY_SCHEDULED = 'retry_scheduled';
    const TYPE_RETRY_ATTEMPTED = 'retry_attempted';
    const TYPE_RETRY_SUCCEEDED = 'retry_succeeded';
    const TYPE_RETRY_FAILED = 'retry_failed';
    const TYPE_EMAIL_SENT = 'email_sent';
    const TYPE_SERVICE_PAUSED = 'service_paused';
    const TYPE_SERVICE_RESUMED = 'service_resumed';
    const TYPE_MANUAL_REVIEW_REQUESTED = 'manual_review_requested';
    const TYPE_MANUALLY_RESOLVED = 'manually_resolved';
    const TYPE_ESCALATED = 'escalated';
    const TYPE_CANCELLED = 'cancelled';
    
    /**
     * Get the dunning process
     */
    public function dunningProcess(): BelongsTo
    {
        return $this->belongsTo(DunningProcess::class);
    }
    
    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Get type label
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_RETRY_SCHEDULED => 'Retry Scheduled',
            self::TYPE_RETRY_ATTEMPTED => 'Retry Attempted',
            self::TYPE_RETRY_SUCCEEDED => 'Retry Succeeded',
            self::TYPE_RETRY_FAILED => 'Retry Failed',
            self::TYPE_EMAIL_SENT => 'Email Sent',
            self::TYPE_SERVICE_PAUSED => 'Service Paused',
            self::TYPE_SERVICE_RESUMED => 'Service Resumed',
            self::TYPE_MANUAL_REVIEW_REQUESTED => 'Manual Review Requested',
            self::TYPE_MANUALLY_RESOLVED => 'Manually Resolved',
            self::TYPE_ESCALATED => 'Escalated',
            self::TYPE_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->type))
        };
    }
    
    /**
     * Get icon for activity type
     */
    public function getIcon(): string
    {
        return match($this->type) {
            self::TYPE_RETRY_SCHEDULED => '🔄',
            self::TYPE_RETRY_ATTEMPTED => '🔁',
            self::TYPE_RETRY_SUCCEEDED => '✅',
            self::TYPE_RETRY_FAILED => '❌',
            self::TYPE_EMAIL_SENT => '📧',
            self::TYPE_SERVICE_PAUSED => '⏸️',
            self::TYPE_SERVICE_RESUMED => '▶️',
            self::TYPE_MANUAL_REVIEW_REQUESTED => '👀',
            self::TYPE_MANUALLY_RESOLVED => '✋',
            self::TYPE_ESCALATED => '⚠️',
            self::TYPE_CANCELLED => '🚫',
            default => '📌'
        };
    }
}