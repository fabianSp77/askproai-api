<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email', 
        'phone',
        'birthdate',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'birthdate' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the tenant that owns the customer.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all calls made by this customer.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get all appointments for this customer.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get all branches associated with this customer.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Scope for searching customers by name, email, or phone.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for customers created after a specific date.
     */
    public function scopeCreatedAfter($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    /**
     * Get the customer's full display name with email.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name . ' (' . $this->email . ')';
    }

    /**
     * Get total number of calls made by this customer.
     */
    public function getTotalCallsAttribute(): int
    {
        return $this->calls()->count();
    }

    /**
     * Get total number of successful calls.
     */
    public function getSuccessfulCallsAttribute(): int
    {
        return $this->calls()->successful()->count();
    }

    /**
     * Get total number of appointments.
     */
    public function getTotalAppointmentsAttribute(): int
    {
        return $this->appointments()->count();
    }

    /**
     * Get completed appointments count.
     */
    public function getCompletedAppointmentsAttribute(): int
    {
        return $this->appointments()->where('status', 'completed')->count();
    }

    /**
     * Get upcoming appointments count.
     */
    public function getUpcomingAppointmentsAttribute(): int
    {
        return $this->appointments()
            ->where('status', 'scheduled')
            ->where('start_time', '>', now())
            ->count();
    }

    /**
     * Get the date of the last call.
     */
    public function getLastCallAtAttribute(): ?string
    {
        $lastCall = $this->calls()->latest('start_timestamp')->first();
        return $lastCall?->start_timestamp?->format('Y-m-d H:i:s');
    }

    /**
     * Get the date of the last appointment.
     */
    public function getLastAppointmentAtAttribute(): ?string
    {
        $lastAppointment = $this->appointments()->latest('start_time')->first();
        return $lastAppointment?->start_time?->format('Y-m-d H:i:s');
    }

    /**
     * Get customer since date (formatted).
     */
    public function getCustomerSinceAttribute(): string
    {
        return $this->created_at->format('Y-m-d');
    }

    /**
     * Check if customer has any recent activity (calls or appointments in last 30 days).
     */
    public function hasRecentActivity(): bool
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        return $this->calls()->where('start_timestamp', '>=', $thirtyDaysAgo)->exists() ||
               $this->appointments()->where('start_time', '>=', $thirtyDaysAgo)->exists();
    }

    /**
     * Get customer's preferred contact method based on interaction history.
     */
    public function getPreferredContactMethod(): string
    {
        $callsCount = $this->calls()->count();
        $appointmentsCount = $this->appointments()->count();

        if ($callsCount > $appointmentsCount) {
            return 'phone';
        } elseif ($appointmentsCount > 0) {
            return 'email';
        }

        return 'phone'; // Default
    }

    /**
     * Get customer's activity summary.
     */
    public function getActivitySummary(): array
    {
        return [
            'total_calls' => $this->total_calls,
            'successful_calls' => $this->successful_calls,
            'total_appointments' => $this->total_appointments,
            'completed_appointments' => $this->completed_appointments,
            'upcoming_appointments' => $this->upcoming_appointments,
            'last_call_at' => $this->last_call_at,
            'last_appointment_at' => $this->last_appointment_at,
            'has_recent_activity' => $this->hasRecentActivity(),
            'preferred_contact_method' => $this->getPreferredContactMethod()
        ];
    }
}
