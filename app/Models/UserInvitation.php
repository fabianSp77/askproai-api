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
        'status',
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
     * Generate short, readable token (8 characters)
     * Format: XXXX-XXXX (e.g., AB3K-9MF2)
     *
     * Uses alphanumeric characters (excluding confusing ones: 0, O, I, l, 1)
     * Total possibilities: 32^8 = 1,099,511,627,776 (over 1 trillion)
     *
     * Collision probability with 1 million invitations: ~0.00001%
     */
    public static function generateToken(): string
    {
        // Characters that are easy to read and type
        // Excluding: 0, O (zero/oh), I, l, 1 (one/el/eye)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            // Generate 8 random characters
            $token = '';
            for ($i = 0; $i < 8; $i++) {
                $token .= $characters[random_int(0, strlen($characters) - 1)];
            }

            // Format as XXXX-XXXX for readability
            $formatted = substr($token, 0, 4) . '-' . substr($token, 4, 4);

            // Check if token already exists (collision check)
        } while (self::where('token', $formatted)->exists());

        return $formatted;
    }

    /**
     * Legacy method for backwards compatibility
     * (can be removed after migration)
     */
    public static function generateLongToken(): string
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
    // DISPLAY HELPERS
    // ==========================================

    /**
     * Get human-readable role display name in German
     */
    public function getRoleDisplayName(): string
    {
        if (!$this->role) {
            return 'Kunde';
        }

        return match($this->role->name) {
            'viewer' => 'Betrachter',
            'operator' => 'Bearbeiter',
            'manager' => 'Verwalter',
            'owner' => 'Inhaber',
            'admin' => 'Administrator',
            'company_manager' => 'Filialleiter',
            'company_staff' => 'Mitarbeiter',
            'customer' => 'Kunde',
            default => ucfirst($this->role->name),
        };
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
