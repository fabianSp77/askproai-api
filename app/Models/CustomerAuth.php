<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\CustomerResetPasswordNotification;
use App\Notifications\CustomerVerifyEmailNotification;

class CustomerAuth extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'branch_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'portal_enabled',
        'portal_access_token',
        'portal_token_expires_at',
        'last_portal_login_at',
        'preferred_language',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'portal_access_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'portal_token_expires_at' => 'datetime',
        'last_portal_login_at' => 'datetime',
        'portal_enabled' => 'boolean',
    ];

    /**
     * Get the company that owns the customer.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the customer.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the appointments for the customer.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    /**
     * Get the calls for the customer.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'customer_id');
    }

    /**
     * Get the invoices for the customer.
     */
    public function invoices()
    {
        // Invoices are linked to company/branch, we need to filter by customer's appointments
        return Invoice::whereHas('billingPeriod', function ($query) {
            $query->whereHas('appointments', function ($q) {
                $q->where('customer_id', $this->id);
            });
        })->orWhere('metadata->customer_id', $this->id);
    }

    /**
     * Get full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Check if portal access is enabled.
     */
    public function hasPortalAccess(): bool
    {
        return $this->portal_enabled && 
               $this->email_verified_at !== null;
    }

    /**
     * Generate a new portal access token.
     */
    public function generatePortalAccessToken(): string
    {
        $token = \Illuminate\Support\Str::random(60);
        
        $this->update([
            'portal_access_token' => hash('sha256', $token),
            'portal_token_expires_at' => now()->addHours(24),
        ]);
        
        return $token;
    }

    /**
     * Verify portal access token.
     */
    public function verifyPortalAccessToken(string $token): bool
    {
        if (!$this->portal_access_token || !$this->portal_token_expires_at) {
            return false;
        }
        
        return hash('sha256', $token) === $this->portal_access_token &&
               $this->portal_token_expires_at->isFuture();
    }

    /**
     * Send password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }

    /**
     * Send email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomerVerifyEmailNotification());
    }

    /**
     * Get upcoming appointments.
     */
    public function getUpcomingAppointmentsAttribute()
    {
        return $this->appointments()
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();
    }

    /**
     * Get past appointments.
     */
    public function getPastAppointmentsAttribute()
    {
        return $this->appointments()
            ->where('starts_at', '<', now())
            ->orderBy('starts_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Record portal login.
     */
    public function recordPortalLogin(): void
    {
        $this->update([
            'last_portal_login_at' => now(),
        ]);
    }
}