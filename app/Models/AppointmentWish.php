<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AppointmentWish Model
 *
 * Represents a customer's appointment wish/request that couldn't be immediately fulfilled.
 * Used for follow-up tracking and analytics.
 *
 * EXAMPLES:
 * - Customer wanted "Monday 14:00" but that slot was unavailable
 * - Agent offered alternatives but customer declined them
 * - Booking confidence was too low for automatic booking
 *
 * FEATURES:
 * - Multi-tenant isolation via company_id
 * - Automatic expiration after 30 days
 * - Email notification integration
 * - Status tracking for follow-ups
 */
class AppointmentWish extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'desired_date' => 'datetime',
        'alternatives_offered' => 'array',
        'metadata' => 'array',
        'contacted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The call this wish originated from
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * The customer who expressed this wish
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The company that owns this wish
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The staff member who followed up
     */
    public function followUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_by_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Wishes requiring follow-up (pending status)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Wishes by company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Wishes requiring follow-up within timeframe
     * Can be used for escalation workflows
     */
    public function scopeRequiresFollowUp($query, int $daysOld = 1)
    {
        return $query
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subDays($daysOld));
    }

    /**
     * Old wishes that should be expired
     */
    public function scopeExpirable($query, int $daysOld = 30)
    {
        return $query
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays($daysOld));
    }

    /**
     * Wishes with specific rejection reason
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('rejection_reason', $reason);
    }

    /**
     * Recently created (for real-time notifications)
     */
    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get readable rejection reason
     */
    public function getRejectionReasonLabelAttribute(): ?string
    {
        return match ($this->rejection_reason) {
            'not_available' => 'Der gewünschte Termin ist leider nicht verfügbar',
            'customer_declined' => 'Kunde hat angebotene Alternativen abgelehnt',
            'technical_error' => 'Technischer Fehler bei der Buchung',
            'low_confidence' => 'Booking-Konfidenz zu niedrig',
            default => null
        };
    }

    /**
     * Check if wish is actionable (not expired)
     */
    public function isActionable(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        // Expire after 30 days
        return $this->created_at->addDays(30)->isFuture();
    }

    /**
     * Days since wish was created
     */
    public function getDaysSinceCreationAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get formatted desired time for display
     */
    public function getFormattedDesiredTimeAttribute(): ?string
    {
        if (!$this->desired_date) {
            return null;
        }

        $date = \Carbon\Carbon::parse($this->desired_date);
        return $date->locale('de')->isoFormat('dddd, [den] D. MMMM') .
               ($this->desired_time ? ' um ' . $this->desired_time : '');
    }
}
