<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationDelivery Model
 *
 * Tracks individual delivery attempts for notifications queued in NotificationQueue.
 * Provides audit trail for notification delivery across multiple channels.
 *
 * @property int $id
 * @property int $notification_queue_id
 * @property string $channel (sms, email, push, webhook, etc.)
 * @property string $status (pending, sending, sent, failed, delivered, bounced)
 * @property string|null $provider_name (Twilio, SendGrid, Firebase, etc.)
 * @property string|null $provider_message_id
 * @property array|null $provider_response (Full response from provider)
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class NotificationDelivery extends Model
{
    protected $table = 'notification_deliveries';

    protected $fillable = [
        'notification_queue_id',
        'channel',
        'status',
        'provider_name',
        'provider_message_id',
        'provider_response',
        'error_code',
        'error_message',
        'retry_count',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Get the notification queue this delivery belongs to
     */
    public function notificationQueue(): BelongsTo
    {
        return $this->belongsTo(NotificationQueue::class);
    }

    /**
     * Scope: Get all pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get all failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Get all delivered notifications
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Mark delivery as sent
     */
    public function markAsSent(?string $providerId = null, ?array $response = null): self
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => $providerId,
            'provider_response' => $response,
        ]);

        return $this;
    }

    /**
     * Mark delivery as delivered
     */
    public function markAsDelivered(): self
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark delivery as failed
     */
    public function markAsFailed(string $errorCode, string $errorMessage, ?array $response = null): self
    {
        $this->update([
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'provider_response' => $response,
        ]);

        return $this;
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): self
    {
        $this->increment('retry_count');
        return $this;
    }
}
