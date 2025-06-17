<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookEvent extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'provider',
        'event_type',
        'event_id',
        'idempotency_key',
        'payload',
        'status',
        'processed_at',
        'error_message',
        'retry_count',
        'correlation_id'
    ];
    
    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime'
    ];
    
    /**
     * Constants for webhook providers
     */
    const PROVIDER_RETELL = 'retell';
    const PROVIDER_CALCOM = 'calcom';
    const PROVIDER_STRIPE = 'stripe';
    
    /**
     * Constants for webhook statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_DUPLICATE = 'duplicate';
    
    /**
     * Check if this event has already been processed
     */
    public static function hasBeenProcessed(string $idempotencyKey): bool
    {
        return self::where('idempotency_key', $idempotencyKey)
            ->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_PROCESSING])
            ->exists();
    }
    
    /**
     * Generate idempotency key from webhook data
     */
    public static function generateIdempotencyKey(string $provider, array $data): string
    {
        // Use provider-specific fields to generate a unique key
        $keyData = match ($provider) {
            self::PROVIDER_RETELL => [
                'provider' => $provider,
                'event' => $data['event'] ?? '',
                'call_id' => $data['call']['call_id'] ?? $data['call_id'] ?? '',
                'timestamp' => $data['call']['end_timestamp'] ?? $data['timestamp'] ?? ''
            ],
            self::PROVIDER_CALCOM => [
                'provider' => $provider,
                'event' => $data['triggerEvent'] ?? '',
                'booking_id' => $data['payload']['id'] ?? '',
                'uid' => $data['payload']['uid'] ?? ''
            ],
            self::PROVIDER_STRIPE => [
                'provider' => $provider,
                'event_id' => $data['id'] ?? '',
                'type' => $data['type'] ?? ''
            ],
            default => [
                'provider' => $provider,
                'data' => json_encode($data)
            ]
        };
        
        return hash('sha256', json_encode($keyData));
    }
    
    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_at' => now()
        ]);
    }
    
    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now()
        ]);
    }
    
    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->increment('retry_count');
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage
        ]);
    }
}
