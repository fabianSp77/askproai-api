<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitation Email Queue Model
 *
 * PURPOSE: Decouples invitation creation from email delivery
 *
 * RELIABILITY DESIGN:
 * - Retry mechanism with exponential backoff
 * - Error tracking for debugging
 * - Status transitions for monitoring
 * - Prevents email failures from blocking invitations
 *
 * STATUS FLOW:
 * pending → sent (success)
 * pending → failed (after max retries)
 * pending → cancelled (invitation deleted/expired)
 */
class InvitationEmailQueue extends Model
{
    protected $table = 'invitation_email_queue';

    protected $fillable = [
        'user_invitation_id',
        'status',
        'attempts',
        'next_attempt_at',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==========================================
    // STATUS CONSTANTS
    // ==========================================

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    const MAX_ATTEMPTS = 3;

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function userInvitation(): BelongsTo
    {
        return $this->belongsTo(UserInvitation::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where(function ($q) {
                         $q->whereNull('next_attempt_at')
                           ->orWhere('next_attempt_at', '<=', now());
                     })
                     ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    // ==========================================
    // BUSINESS LOGIC
    // ==========================================

    /**
     * Mark email as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Record failed attempt
     */
    public function recordFailure(string $error): void
    {
        $this->increment('attempts');

        $updates = [
            'last_error' => $error,
        ];

        // If max attempts reached, mark as failed
        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $updates['status'] = self::STATUS_FAILED;
            $updates['next_attempt_at'] = null;
        } else {
            // Exponential backoff: 5min, 30min, 2hr
            $backoffMinutes = [5, 30, 120][$this->attempts - 1] ?? 120;
            $updates['next_attempt_at'] = now()->addMinutes($backoffMinutes);
        }

        $this->update($updates);
    }

    /**
     * Cancel email delivery
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'next_attempt_at' => null,
        ]);
    }

    /**
     * Check if email can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->attempts < self::MAX_ATTEMPTS;
    }

    /**
     * Get next retry time in human-readable format
     */
    public function getNextRetryAttribute(): ?string
    {
        if (!$this->next_attempt_at) {
            return null;
        }

        return $this->next_attempt_at->diffForHumans();
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttemptsAttribute(): int
    {
        return max(0, self::MAX_ATTEMPTS - $this->attempts);
    }

    // ==========================================
    // AUDIT TRAIL
    // ==========================================

    protected static function booted()
    {
        static::updated(function (InvitationEmailQueue $queue) {
            // Log when email is sent
            if ($queue->isDirty('status') && $queue->status === self::STATUS_SENT) {
                activity()
                    ->performedOn($queue->userInvitation)
                    ->log('invitation_email_sent');
            }

            // Log when email fails permanently
            if ($queue->isDirty('status') && $queue->status === self::STATUS_FAILED) {
                activity()
                    ->performedOn($queue->userInvitation)
                    ->withProperties([
                        'attempts' => $queue->attempts,
                        'last_error' => $queue->last_error,
                    ])
                    ->log('invitation_email_failed');
            }
        });
    }

    // ==========================================
    // REPORTING HELPERS
    // ==========================================

    /**
     * Get delivery statistics
     */
    public static function getDeliveryStats(int $days = 30): array
    {
        $query = self::where('created_at', '>=', now()->subDays($days));

        return [
            'total' => $query->count(),
            'sent' => $query->clone()->sent()->count(),
            'pending' => $query->clone()->pending()->count(),
            'failed' => $query->clone()->failed()->count(),
            'success_rate' => $query->count() > 0
                ? round(($query->clone()->sent()->count() / $query->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get emails that need retry
     */
    public static function getRetryQueue(): \Illuminate\Support\Collection
    {
        return self::readyToSend()
            ->with('userInvitation')
            ->orderBy('next_attempt_at')
            ->get();
    }
}
