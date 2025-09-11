<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    /**
     * The relationships that should always be loaded.
     * Prevents N+1 query issues by eager loading common relationships.
     */
    protected $with = ['customer', 'staff', 'service', 'branch'];

    protected $fillable = [
        'customer_id', 'staff_id', 'service_id', 'branch_id', 'call_id',
        'external_id', 'starts_at', 'ends_at', 'status', 'notes',
        'payload', 'calcom_booking_id', 'internal_notes', 'price',
        'booking_type', 'booking_metadata', 'version', 'lock_token',
        'lock_expires_at', 'reminder_24h_sent_at', 'calcom_v2_booking_id',
        'calcom_event_type_id', 'series_id', 'group_booking_id',
        'parent_appointment_id', 'company_id', 'source',
        'meeting_url', 'calcom_booking_uid', 'reschedule_uid',
        'attendees', 'responses', 'location_type', 'location_value',
        'is_recurring', 'recurring_event_id', 'cancellation_reason', 'rejected_reason'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'payload'   => 'array',
        'booking_metadata' => 'array',
        'attendees' => 'array',
        'responses' => 'array',
        'lock_expires_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function calcomEventType(): BelongsTo
    {
        return $this->belongsTo(CalcomEventType::class, 'calcom_event_type_id');
    }

    // Computed Properties
    public function getDurationMinutesAttribute(): int
    {
        if ($this->starts_at && $this->ends_at) {
            return $this->starts_at->diffInMinutes($this->ends_at);
        }
        return 60; // Default duration
    }

    public function getPriceCentsAttribute(): int
    {
        // Use appointment price if set, otherwise fall back to service price
        if ($this->price !== null) {
            return $this->price;
        }
        return $this->service?->price_cents ?? 0;
    }

    public function getIsRecurringAttribute(): bool
    {
        return $this->booking_type === 'recurring' && !empty($this->series_id);
    }

    public function getIsGroupBookingAttribute(): bool
    {
        return $this->booking_type === 'group' && !empty($this->group_booking_id);
    }

    public function getReminderSentAttribute(): bool
    {
        return $this->reminder_24h_sent_at !== null;
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function getIsPastAttribute(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        $now = now();
        return $this->starts_at && $this->ends_at 
            && $this->starts_at->lte($now) 
            && $this->ends_at->gte($now);
    }

    public function getAttendeeCountAttribute(): int
    {
        if (!$this->attendees || !is_array($this->attendees)) {
            return 0;
        }
        return count($this->attendees);
    }

    public function getPrimaryAttendeeAttribute(): ?array
    {
        if (!$this->attendees || !is_array($this->attendees) || empty($this->attendees)) {
            return null;
        }
        return $this->attendees[0];
    }

    public function getLocationDisplayAttribute(): string
    {
        if ($this->location_type === 'video') {
            return '📹 Video Call';
        } elseif ($this->location_type === 'phone') {
            return '📞 Phone Call';
        } elseif ($this->location_type === 'inPerson') {
            return '🏢 In Person';
        } elseif ($this->location_type === 'email') {
            return '✉️ Email';
        } elseif ($this->meeting_url) {
            return '📹 Online Meeting';
        }
        return '📍 Location TBD';
    }

    public function getBookingTitleAttribute(): ?string
    {
        if ($this->booking_metadata && isset($this->booking_metadata['title'])) {
            return $this->booking_metadata['title'];
        }
        return null;
    }

    public function getHostsAttribute(): array
    {
        if ($this->booking_metadata && isset($this->booking_metadata['hosts'])) {
            return $this->booking_metadata['hosts'];
        }
        return [];
    }

    public function hasCustomResponses(): bool
    {
        return !empty($this->responses) && is_array($this->responses);
    }

    // Scopes for filtering
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('starts_at', today());
    }

    public function scopeRecurring($query)
    {
        return $query->where('booking_type', 'recurring');
    }

    public function scopeGroupBookings($query)
    {
        return $query->where('booking_type', 'group');
    }
}
