<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AvailabilityCache extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'availability_cache';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'staff_id',
        'event_type_id',
        'date',
        'slots',
        'cache_key',
        'cached_at',
        'expires_at',
        'is_valid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'slots' => 'array',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_valid' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Generate cache key before creating
        static::creating(function ($cache) {
            if (empty($cache->cache_key)) {
                $cache->cache_key = static::generateCacheKey(
                    $cache->staff_id,
                    $cache->event_type_id,
                    $cache->date
                );
            }
            
            if (empty($cache->cached_at)) {
                $cache->cached_at = now();
            }
        });

        // Clear in-memory cache when model is updated/deleted
        static::updated(function ($cache) {
            Cache::forget($cache->cache_key);
        });

        static::deleted(function ($cache) {
            Cache::forget($cache->cache_key);
        });
    }

    /**
     * Get the staff member that owns the cache entry.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the event type associated with the cache entry.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(CalcomEventType::class, 'event_type_id');
    }

    /**
     * Scope a query to only include valid cache entries.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired cache entries.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include invalid cache entries.
     */
    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    /**
     * Check if the cache entry is still valid.
     */
    public function isValid(): bool
    {
        return $this->is_valid && $this->expires_at->isFuture();
    }

    /**
     * Check if the cache entry has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Invalidate the cache entry.
     */
    public function invalidate(): bool
    {
        $this->is_valid = false;
        $result = $this->save();
        
        // Also clear from in-memory cache
        Cache::forget($this->cache_key);
        
        return $result;
    }

    /**
     * Get available slots as a collection.
     */
    public function getAvailableSlots(): array
    {
        if (!$this->isValid()) {
            return [];
        }

        return $this->slots ?? [];
    }

    /**
     * Generate a unique cache key.
     */
    public static function generateCacheKey(string $staffId, ?int $eventTypeId, $date): string
    {
        $dateStr = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        $eventTypeStr = $eventTypeId ?: 'all';
        
        return "availability:{$staffId}:{$eventTypeStr}:{$dateStr}";
    }

    /**
     * Get or create cache entry for a specific date.
     */
    public static function getOrCreate(string $staffId, ?int $eventTypeId, $date, int $expiresInMinutes = 15): ?self
    {
        $cacheKey = static::generateCacheKey($staffId, $eventTypeId, $date);
        
        // Check for existing valid cache
        $cache = static::where('cache_key', $cacheKey)
                      ->valid()
                      ->first();
        
        if ($cache) {
            return $cache;
        }

        // Create new cache entry (slots will be populated by the service)
        return static::create([
            'staff_id' => $staffId,
            'event_type_id' => $eventTypeId,
            'date' => $date,
            'slots' => [],
            'cache_key' => $cacheKey,
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'is_valid' => false, // Will be set to true when slots are populated
        ]);
    }

    /**
     * Update cache with new slots.
     */
    public function updateSlots(array $slots, int $expiresInMinutes = 15): bool
    {
        $this->slots = $slots;
        $this->is_valid = true;
        $this->cached_at = now();
        $this->expires_at = now()->addMinutes($expiresInMinutes);
        
        $result = $this->save();
        
        // Also update in-memory cache
        if ($result) {
            Cache::put($this->cache_key, $slots, $this->expires_at);
        }
        
        return $result;
    }

    /**
     * Invalidate all cache entries for a staff member.
     */
    public static function invalidateForStaff(string $staffId): int
    {
        return static::where('staff_id', $staffId)
                    ->where('is_valid', true)
                    ->update(['is_valid' => false]);
    }

    /**
     * Invalidate all cache entries for a date range.
     */
    public static function invalidateForDateRange(string $staffId, Carbon $startDate, Carbon $endDate): int
    {
        return static::where('staff_id', $staffId)
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('is_valid', true)
                    ->update(['is_valid' => false]);
    }

    /**
     * Clean up expired cache entries.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}