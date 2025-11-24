<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AppointmentReservation extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'reservation_token',
        'status',
        'call_id',
        'customer_phone',
        'customer_name',
        'service_id',
        'staff_id',
        'start_time',
        'end_time',
        'is_compound',
        'compound_parent_token',
        'segment_number',
        'total_segments',
        'reserved_at',
        'expires_at',
        'converted_to_appointment_id',
    ];

    protected $casts = [
        'is_compound' => 'boolean',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Boot the model.
     *
     * Automatically generates UUID for reservation_token on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            // Generate UUID for reservation_token if not set
            if (empty($reservation->reservation_token)) {
                $reservation->reservation_token = Str::uuid()->toString();
            }

            // Set reserved_at if not set (though migration has default)
            if (empty($reservation->reserved_at)) {
                $reservation->reserved_at = now();
            }

            // Default status to 'active' if not set
            if (empty($reservation->status)) {
                $reservation->status = 'active';
            }
        });
    }

    /**
     * Relationships
     * Note: company() relationship provided by BelongsToCompany trait
     */

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function convertedAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'converted_to_appointment_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
                    ->where('expires_at', '<=', now());
    }

    public function scopeForTimeRange($query, $startTime, $endTime)
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
              ->orWhereBetween('end_time', [$startTime, $endTime])
              ->orWhere(function ($subq) use ($startTime, $endTime) {
                  $subq->where('start_time', '<=', $startTime)
                       ->where('end_time', '>=', $endTime);
              });
        });
    }

    /**
     * Helper methods
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function timeRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function markExpired(): bool
    {
        return $this->update(['status' => 'expired']);
    }

    public function markConverted(int $appointmentId): bool
    {
        return $this->update([
            'status' => 'converted',
            'converted_to_appointment_id' => $appointmentId,
        ]);
    }

    public function markCancelled(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }
}
