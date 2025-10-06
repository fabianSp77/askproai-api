<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NestedBookingSlot extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'parent_booking_id',
        'available_from',
        'available_to',
        'max_duration_minutes',
        'allowed_services',
        'is_available',
        'child_booking_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'available_from' => 'datetime',
        'available_to' => 'datetime',
        'allowed_services' => 'array',
        'is_available' => 'boolean'
    ];

    /**
     * Get the parent booking this slot belongs to
     */
    public function parentBooking(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'parent_booking_id');
    }

    /**
     * Get the child booking that filled this slot
     */
    public function childBooking(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'child_booking_id');
    }

    /**
     * Scope to get only available slots
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get slots within a time range
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->where('available_from', '>=', $start)
                     ->where('available_to', '<=', $end);
    }

    /**
     * Check if a service type can fit in this slot
     */
    public function canAccommodateService(string $serviceType, int $durationMinutes): bool
    {
        if (!$this->is_available) {
            return false;
        }

        // Check if service type is allowed
        if ($this->allowed_services && !in_array($serviceType, $this->allowed_services)) {
            return false;
        }

        // Check if duration fits
        $slotDuration = $this->available_from->diffInMinutes($this->available_to);
        return $durationMinutes <= $slotDuration;
    }

    /**
     * Get the duration of this slot in minutes
     */
    public function getDurationInMinutes(): int
    {
        return $this->available_from->diffInMinutes($this->available_to);
    }
}