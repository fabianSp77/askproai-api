<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Traits\BelongsToCompany;

class PortalUser extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable, TwoFactorAuthenticatable, BelongsToCompany;

    protected $table = 'portal_users';
    
    protected $fillable = [
        'company_id',
        'email',
        'password',
        'name',
        'phone',
        'role',
        'permissions',
        'is_active',
        'two_factor_enforced',
        'last_login_at',
        'last_login_ip',
        'settings',
        'notification_preferences',
        'call_notification_preferences',
        'preferred_language',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'permissions' => 'array',
        'settings' => 'array',
        'notification_preferences' => 'array',
        'call_notification_preferences' => 'array',
        'is_active' => 'boolean',
        'two_factor_enforced' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Role constants
    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_STAFF = 'staff';

    const ROLES = [
        self::ROLE_OWNER => 'Geschäftsführer',
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_STAFF => 'Mitarbeiter',
    ];

    // Default permissions per role
    const ROLE_PERMISSIONS = [
        self::ROLE_OWNER => [
            'calls.view_all', 'calls.edit_all', 'calls.export',
            'appointments.view_all', 'appointments.edit_all',
            'billing.view', 'billing.pay', 'billing.export', 'billing.manage',
            'analytics.view_all', 'analytics.export',
            'team.manage', 'settings.manage',
            'feedback.view_all', 'feedback.respond'
        ],
        self::ROLE_ADMIN => [
            'calls.view_all', 'calls.edit_all', 'calls.export',
            'appointments.view_all', 'appointments.edit_all',
            'billing.view', 'billing.pay', 'billing.export', 'billing.manage',
            'analytics.view_all', 'analytics.export',
            'feedback.view_all', 'feedback.respond'
        ],
        self::ROLE_MANAGER => [
            'calls.view_team', 'calls.edit_team', 'calls.export',
            'appointments.view_team', 'appointments.edit_team',
            'analytics.view_team',
            'team.view',
            'feedback.view_team'
        ],
        self::ROLE_STAFF => [
            'calls.view_own', 'calls.edit_own',
            'appointments.view_own', 'appointments.edit_own',
            'feedback.create'
        ],
    ];

    /**
     * Get display name for role
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
    
    /**
     * Get display name for role (alias)
     */
    public function getRoleDisplayAttribute(): string
    {
        return $this->getRoleDisplayNameAttribute();
    }

    /**
     * Portal permissions relationship
     */
    public function portalPermissions()
    {
        return $this->belongsToMany(PortalPermission::class, 'portal_user_permissions')
            ->withPivot(['granted_at', 'granted_by_user_id', 'granted_by_user_type'])
            ->withTimestamps();
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Owner has all permissions
        if ($this->role === self::ROLE_OWNER) {
            return true;
        }

        // Check new portal permissions system first
        if ($this->portalPermissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check custom permissions (legacy)
        if ($this->permissions) {
            // Ensure permissions is an array (handle edge cases with corrupted data)
            $permissions = $this->permissions;
            
            // If it's still a string after casting, try to decode it
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?: [];
            }
            
            // Only check if we have a valid array
            if (is_array($permissions) && in_array($permission, $permissions)) {
                return true;
            }
        }

        // Check role-based permissions
        $rolePermissions = self::ROLE_PERMISSIONS[$this->role] ?? [];
        return in_array($permission, $rolePermissions);
    }

    /**
     * Check if user can view all company data
     */
    public function canViewAllData(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * Check if user can manage team
     */
    public function canManageTeam(): bool
    {
        return $this->hasPermission('team.manage');
    }

    /**
     * Check if user can view billing
     */
    public function canViewBilling(): bool
    {
        return $this->hasPermission('billing.view');
    }

    /**
     * Get notification preferences with defaults
     */
    public function getNotificationPreferences(): array
    {
        $defaults = [
            'channels' => ['email'],
            'frequency' => 'daily',
            'time' => '09:00',
            'types' => [
                'calls' => ['daily_summary'],
                'appointments' => $this->company->needsAppointmentBooking() ? ['reminder_24h'] : [],
                'billing' => $this->canViewBilling() ? ['new_invoice'] : [],
            ],
        ];

        return array_merge($defaults, $this->notification_preferences ?? []);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(array $preferences): void
    {
        $this->notification_preferences = array_merge(
            $this->getNotificationPreferences(),
            $preferences
        );
        $this->save();
    }

    /**
     * Get user settings with defaults
     */
    public function getSettings(): array
    {
        $defaults = [
            'theme' => 'light',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'rows_per_page' => 25,
            'call_columns' => ['date', 'from', 'to', 'duration', 'status'],
        ];

        return array_merge($defaults, $this->settings ?? []);
    }

    /**
     * Update settings
     */
    public function updateSettings(array $settings): void
    {
        $this->settings = array_merge($this->getSettings(), $settings);
        $this->save();
    }

    /**
     * Record login
     */
    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    /**
     * Check if 2FA is required
     */
    public function requires2FA(): bool
    {
        // Enforced by role
        if (in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN])) {
            return true;
        }

        // Or manually enforced
        return $this->two_factor_enforced;
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by role
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get team members (for managers)
     */
    public function teamMembers()
    {
        if (!in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MANAGER])) {
            return collect();
        }

        return static::where('company_id', $this->company_id)
            ->when($this->role === self::ROLE_MANAGER, function ($query) {
                // Managers can only see staff
                $query->where('role', self::ROLE_STAFF);
            })
            ->where('id', '!=', $this->id)
            ->active()
            ->get();
    }

    /**
     * Balance transactions created by this user
     */
    public function createdBalanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class, 'created_by');
    }

    /**
     * Balance topups initiated by this user
     */
    public function initiatedTopups()
    {
        return $this->hasMany(BalanceTopup::class, 'initiated_by');
    }
    
    /**
     * Check if user can manage billing
     */
    public function canManageBilling(): bool
    {
        return $this->hasPermission('billing.manage');
    }
}