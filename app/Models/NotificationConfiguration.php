<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * NotificationConfiguration Model
 *
 * Hierarchical notification configuration system:
 * - Company level: Default notification policies
 * - Branch level: Location-specific overrides
 * - Service level: Service-specific notification requirements
 * - Staff level: Personal notification preferences ONLY (not business policies)
 *
 * IMPORTANT: Staff can override notification preferences ONLY, not business policies
 *
 * Multi-tenant isolation via BelongsToCompany trait
 */
class NotificationConfiguration extends Model
{
    use HasFactory;
    use BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'configurable_type',
        'configurable_id',
        'event_type',
        'channel',
        'fallback_channel',
        'is_enabled',
        'retry_count',
        'retry_delay_minutes',
        'template_override',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'retry_count' => 'integer',
        'retry_delay_minutes' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the owning configurable model (Company, Branch, Service, or Staff).
     */
    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the notification event mapping for this configuration.
     */
    public function eventMapping(): BelongsTo
    {
        return $this->belongsTo(NotificationEventMapping::class, 'event_type', 'event_type');
    }

    /**
     * Scope to filter configurations for a specific entity.
     */
    public function scopeForEntity(Builder $query, Model $entity): Builder
    {
        return $query->where('configurable_type', get_class($entity))
                     ->where('configurable_id', $entity->id);
    }

    /**
     * Scope to filter configurations by event type.
     */
    public function scopeByEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter configurations by channel.
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to filter enabled configurations only.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get available notification channels.
     */
    public static function getChannels(): array
    {
        return [
            'email',
            'sms',
            'whatsapp',
            'push',
        ];
    }

    /**
     * Get available fallback channels (including 'none').
     */
    public static function getFallbackChannels(): array
    {
        return [
            'email',
            'sms',
            'whatsapp',
            'push',
            'none',
        ];
    }
}
