<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * NotificationEventMapping Model
 *
 * Master event definition table for notification system.
 * Defines all available notification events and their default configurations.
 * Multi-tenant isolation via BelongsToCompany trait.
 */
class NotificationEventMapping extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'event_type',
        'event_label',
        'event_category',
        'default_channels',
        'description',
        'is_system_event',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'default_channels' => 'array',
        'is_system_event' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all notification configurations using this event type.
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(NotificationConfiguration::class, 'event_type', 'event_type');
    }

    /**
     * Scope to filter active events only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter events by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope to filter system events.
     */
    public function scopeSystemEvents(Builder $query): Builder
    {
        return $query->where('is_system_event', true);
    }

    /**
     * Get event by type with caching.
     *
     * @param string $eventType The event type to look up
     * @return self|null
     */
    public static function getEventByType(string $eventType): ?self
    {
        $cacheKey = "notification_event_mapping:{$eventType}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($eventType) {
            return self::where('event_type', $eventType)->first();
        });
    }

    /**
     * Get available event categories.
     */
    public static function getCategories(): array
    {
        return [
            'booking',
            'reminder',
            'modification',
            'callback',
            'system',
        ];
    }

    /**
     * Clear cached event lookup.
     */
    public function clearCache(): void
    {
        Cache::forget("notification_event_mapping:{$this->event_type}");
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when model is updated or deleted
        static::saved(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }
}
