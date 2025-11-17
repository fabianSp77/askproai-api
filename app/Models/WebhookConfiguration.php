<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * WebhookConfiguration Model
 *
 * Manages outgoing webhook subscriptions for external systems (CRM, Slack, custom apps)
 * to receive real-time notifications about callback request events.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name Human-readable webhook name
 * @property string $url Webhook endpoint URL
 * @property array $subscribed_events Event types to trigger webhook
 * @property string $secret_key HMAC signature key
 * @property bool $is_active Enable/disable webhook
 * @property int $timeout_seconds HTTP request timeout
 * @property int $max_retry_attempts Retry failed deliveries
 * @property array|null $headers Custom HTTP headers
 * @property string|null $description
 * @property int|null $created_by Staff ID
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property int $total_deliveries
 * @property int $successful_deliveries
 * @property int $failed_deliveries
 */
class WebhookConfiguration extends Model
{
    use BelongsToCompany;

    /**
     * Available webhook events for CallbackRequest
     */
    public const EVENT_CALLBACK_CREATED = 'callback.created';
    public const EVENT_CALLBACK_ASSIGNED = 'callback.assigned';
    public const EVENT_CALLBACK_CONTACTED = 'callback.contacted';
    public const EVENT_CALLBACK_COMPLETED = 'callback.completed';
    public const EVENT_CALLBACK_CANCELLED = 'callback.cancelled';
    public const EVENT_CALLBACK_EXPIRED = 'callback.expired';
    public const EVENT_CALLBACK_OVERDUE = 'callback.overdue';
    public const EVENT_CALLBACK_ESCALATED = 'callback.escalated';

    public const AVAILABLE_EVENTS = [
        self::EVENT_CALLBACK_CREATED,
        self::EVENT_CALLBACK_ASSIGNED,
        self::EVENT_CALLBACK_CONTACTED,
        self::EVENT_CALLBACK_COMPLETED,
        self::EVENT_CALLBACK_CANCELLED,
        self::EVENT_CALLBACK_EXPIRED,
        self::EVENT_CALLBACK_OVERDUE,
        self::EVENT_CALLBACK_ESCALATED,
    ];

    protected $fillable = [
        'company_id',
        'name',
        'url',
        'subscribed_events',
        'secret_key',
        'is_active',
        'timeout_seconds',
        'max_retry_attempts',
        'headers',
        'description',
        'created_by',
        'last_triggered_at',
        'total_deliveries',
        'successful_deliveries',
        'failed_deliveries',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_key', // Never expose secret key in API responses
    ];

    /**
     * Get the staff member who created this webhook configuration.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Scope for active webhooks only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for webhooks subscribed to a specific event.
     */
    public function scopeSubscribedTo($query, string $event)
    {
        return $query->whereJsonContains('subscribed_events', $event);
    }

    /**
     * Check if this webhook is subscribed to an event.
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->subscribed_events ?? []);
    }

    /**
     * Increment delivery counters.
     */
    public function recordDelivery(bool $success): void
    {
        $this->increment('total_deliveries');

        if ($success) {
            $this->increment('successful_deliveries');
        } else {
            $this->increment('failed_deliveries');
        }

        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Calculate success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_deliveries === 0) {
            return 0.0;
        }

        return round(($this->successful_deliveries / $this->total_deliveries) * 100, 2);
    }

    /**
     * Generate HMAC signature for payload.
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret_key);
    }

    /**
     * Validate enums and generate secret key before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate secure secret key if not provided
            if (empty($model->secret_key)) {
                $model->secret_key = 'whsec_' . Str::random(64);
            }

            // Validate subscribed events
            foreach ($model->subscribed_events as $event) {
                if (!in_array($event, self::AVAILABLE_EVENTS)) {
                    throw new \InvalidArgumentException("Invalid webhook event: {$event}");
                }
            }
        });

        static::updating(function ($model) {
            // Validate subscribed events on update
            if ($model->isDirty('subscribed_events')) {
                foreach ($model->subscribed_events as $event) {
                    if (!in_array($event, self::AVAILABLE_EVENTS)) {
                        throw new \InvalidArgumentException("Invalid webhook event: {$event}");
                    }
                }
            }
        });
    }
}
