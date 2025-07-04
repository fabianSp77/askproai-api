<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DunningConfiguration extends Model
{
    protected $fillable = [
        'company_id',
        'enabled',
        'max_retry_attempts',
        'retry_delays',
        'grace_period_days',
        'pause_service_on_failure',
        'pause_after_days',
        'send_payment_failed_email',
        'send_retry_warning_email',
        'send_service_paused_email',
        'send_payment_recovered_email',
        'enable_manual_review',
        'manual_review_after_attempts',
        'metadata'
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
        'retry_delays' => 'array',
        'pause_service_on_failure' => 'boolean',
        'send_payment_failed_email' => 'boolean',
        'send_retry_warning_email' => 'boolean',
        'send_service_paused_email' => 'boolean',
        'send_payment_recovered_email' => 'boolean',
        'enable_manual_review' => 'boolean',
        'metadata' => 'array'
    ];
    
    /**
     * Default retry delays in days
     */
    const DEFAULT_RETRY_DELAYS = [
        1 => 3,  // First retry after 3 days
        2 => 5,  // Second retry after 5 days
        3 => 7   // Third retry after 7 days
    ];
    
    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Get retry delay for specific attempt
     */
    public function getRetryDelayForAttempt(int $attempt): int
    {
        return $this->retry_delays[$attempt] ?? 7;
    }
    
    /**
     * Check if email should be sent for event
     */
    public function shouldSendEmail(string $event): bool
    {
        return match($event) {
            'payment_failed' => $this->send_payment_failed_email,
            'retry_warning' => $this->send_retry_warning_email,
            'service_paused' => $this->send_service_paused_email,
            'payment_recovered' => $this->send_payment_recovered_email,
            default => false
        };
    }
    
    /**
     * Check if manual review is needed
     */
    public function needsManualReview(int $attemptCount): bool
    {
        return $this->enable_manual_review && 
               $attemptCount >= $this->manual_review_after_attempts;
    }
    
    /**
     * Check if service should be paused
     */
    public function shouldPauseService(int $daysSinceFailure): bool
    {
        return $this->pause_service_on_failure && 
               $daysSinceFailure >= $this->pause_after_days;
    }
    
    /**
     * Get or create configuration for company
     */
    public static function forCompany(Company $company): self
    {
        return self::firstOrCreate(
            ['company_id' => $company->id],
            [
                'retry_delays' => self::DEFAULT_RETRY_DELAYS,
                'max_retry_attempts' => 3,
                'grace_period_days' => 3,
                'pause_after_days' => 14
            ]
        );
    }
}