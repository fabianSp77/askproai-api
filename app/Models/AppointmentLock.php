<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AppointmentLock extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'branch_id',
        'staff_id',
        'starts_at',
        'ends_at',
        'lock_token',
        'lock_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'lock_expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Generate lock token before creating
        static::creating(function ($lock) {
            if (empty($lock->lock_token)) {
                $lock->lock_token = static::generateLockToken();
            }
        });
    }

    /**
     * Get the branch that owns the lock.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the staff member that owns the lock.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Scope a query to only include active locks.
     */
    public function scopeActive($query)
    {
        return $query->where('lock_expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired locks.
     */
    public function scopeExpired($query)
    {
        return $query->where('lock_expires_at', '<=', now());
    }

    /**
     * Scope a query to only include locks for a specific time range.
     */
    public function scopeForTimeRange($query, $startsAt, $endsAt)
    {
        return $query->where(function ($q) use ($startsAt, $endsAt) {
            // Lock overlaps with the requested time range
            $q->where(function ($q2) use ($startsAt, $endsAt) {
                $q2->where('starts_at', '<=', $startsAt)
                   ->where('ends_at', '>', $startsAt);
            })->orWhere(function ($q2) use ($startsAt, $endsAt) {
                $q2->where('starts_at', '<', $endsAt)
                   ->where('ends_at', '>=', $endsAt);
            })->orWhere(function ($q2) use ($startsAt, $endsAt) {
                $q2->where('starts_at', '>=', $startsAt)
                   ->where('ends_at', '<=', $endsAt);
            });
        });
    }

    /**
     * Check if the lock is still active.
     */
    public function isActive(): bool
    {
        return $this->lock_expires_at->isFuture();
    }

    /**
     * Check if the lock has expired.
     */
    public function isExpired(): bool
    {
        return !$this->isActive();
    }

    /**
     * Extend the lock expiration time.
     */
    public function extend(int $minutes = 5): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        $this->lock_expires_at = now()->addMinutes($minutes);
        return $this->save();
    }

    /**
     * Release the lock.
     */
    public function release(): bool
    {
        return $this->delete();
    }

    /**
     * Generate a unique lock token.
     */
    public static function generateLockToken(): string
    {
        return Str::random(32);
    }

    /**
     * Acquire a lock for a specific time slot.
     */
    public static function acquire(string $branchId, string $staffId, Carbon $startsAt, Carbon $endsAt, int $lockMinutes = 5): ?self
    {
        // Check if there's already an active lock for this time range
        $existingLock = static::active()
            ->where('branch_id', $branchId)
            ->where('staff_id', $staffId)
            ->forTimeRange($startsAt, $endsAt)
            ->first();

        if ($existingLock) {
            return null; // Lock already exists
        }

        // Create new lock
        return static::create([
            'branch_id' => $branchId,
            'staff_id' => $staffId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'lock_expires_at' => now()->addMinutes($lockMinutes),
        ]);
    }

    /**
     * Clean up expired locks.
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }
}