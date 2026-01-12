<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stripe Event Model
 *
 * Tracks processed Stripe webhook events to ensure idempotency.
 * Prevents duplicate processing of the same event when webhooks are retried.
 *
 * @property string $event_id Stripe event ID (evt_xxx)
 * @property string $event_type Event type (invoice.paid, invoice.payment_failed, etc.)
 * @property \Carbon\Carbon $processed_at When this event was processed
 */
class StripeEvent extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',
        'event_type',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Check if a Stripe event has already been processed.
     *
     * @param  string  $eventId  Stripe event ID
     * @return bool True if event was already processed
     */
    public static function isDuplicate(string $eventId): bool
    {
        return self::where('event_id', $eventId)->exists();
    }

    /**
     * Mark a Stripe event as processed.
     *
     * @param  string  $eventId  Stripe event ID
     * @param  string  $eventType  Event type
     */
    public static function markAsProcessed(string $eventId, string $eventType): self
    {
        return self::create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'processed_at' => now(),
        ]);
    }
}
