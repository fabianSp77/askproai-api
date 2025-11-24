<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * User Invitation Model
 *
 * SECURITY DESIGN:
 * - Cryptographically secure tokens (SHA256)
 * - Time-based expiry (24 hours default)
 * - Single-use tokens (accepted_at prevents reuse)
 * - Company-scoped (multi-tenant isolation)
 *
 * BUSINESS RULES:
 * - One pending invitation per email per company
 * - Cannot invite existing company users
 * - Cannot invite to higher privilege role
 * - Audit trail of inviter
 */
class UserInvitation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'email',
        'role_id',
        'invited_by',
        'token',
        'expires_at',
        'accepted_at',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'token', // Never expose in API responses
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function emailQueue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InvitationEmailQueue::class, 'user_invitation_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at');
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                     ->whereNull('accepted_at');
    }

    public function scopeValid($query)
    {
        return $query->whereNull('accepted_at')
                     ->where('expires_at', '>', now());
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ==========================================
    // BUSINESS LOGIC
    // ==========================================

    /**
     * Generate cryptographically secure token
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(64) . microtime(true));
    }

    /**
     * Check if invitation is valid
     */
    public function isValid(): bool
    {
        return $this->accepted_at === null
            && $this->expires_at->isFuture()
            && $this->deleted_at === null;
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->accepted_at === null
            && $this->expires_at->isPast();
    }

    /**
     * Check if invitation is already accepted
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Mark invitation as accepted
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'accepted_at' => now(),
        ]);
    }

    /**
     * Get expiry duration in hours
     */
    public function getExpiryDurationAttribute(): int
    {
        return $this->created_at->diffInHours($this->expires_at);
    }

    /**
     * Get remaining validity hours
     */
    public function getRemainingHoursAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInHours($this->expires_at);
    }

    // ==========================================
    // VALIDATION HELPERS
    // ==========================================

    /**
     * Check if email is already registered in company
     */
    public static function emailExistsInCompany(string $email, int $companyId): bool
    {
        return User::where('email', $email)
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * Check if there's a pending invitation for this email
     */
    public static function hasPendingInvitation(string $email, int $companyId): bool
    {
        return self::forCompany($companyId)
            ->where('email', $email)
            ->pending()
            ->where('expires_at', '>', now())
            ->exists();
    }

    // ==========================================
    // AUDIT TRAIL
    // ==========================================

    protected static function booted()
    {
        static::created(function (UserInvitation $invitation) {
            activity()
                ->performedOn($invitation)
                ->causedBy($invitation->inviter)
                ->withProperties([
                    'email' => $invitation->email,
                    'role' => $invitation->role->name,
                    'company' => $invitation->company->name,
                ])
                ->log('user_invited');
        });

        static::updated(function (UserInvitation $invitation) {
            if ($invitation->isDirty('accepted_at') && $invitation->accepted_at) {
                activity()
                    ->performedOn($invitation)
                    ->withProperties([
                        'email' => $invitation->email,
                        'role' => $invitation->role->name,
                    ])
                    ->log('invitation_accepted');
            }
        });
    }
}
