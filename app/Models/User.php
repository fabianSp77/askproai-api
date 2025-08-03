<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use App\Scopes\OptimizedTenantScope;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, TwoFactorAuthenticatable;
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Use OptimizedTenantScope that prevents memory exhaustion during authentication
        // This scope intelligently skips authentication operations to avoid circular dependencies
        static::addGlobalScope(new OptimizedTenantScope);
    }
    
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'fname', 'lname', 'name', 'email', 'password', 'username', 'tenant_id', 'company_id',
        'date_created', 'date_updated', 'email_verified_at',
        'two_factor_enforced', 'two_factor_method', 'two_factor_phone_number', 'two_factor_phone_verified',
        'interface_language', 'content_language', 'auto_translate_content',
        // New fields from portal_users
        'phone', 'portal_role', 'legacy_permissions', 'is_active',
        'can_access_child_companies', 'accessible_company_ids',
        'settings', 'notification_preferences', 'call_notification_preferences',
        'preferred_language', 'timezone', 'last_login_at', 'last_login_ip',
        'failed_login_attempts', 'locked_until'
    ];
    
    // Removed company_id from appends to prevent memory exhaustion
    protected $appends = [];
    
    protected $hidden = [
        'password', 'remember_token', 'salt', 'legacypassword',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_enforced' => 'boolean',
        'two_factor_phone_verified' => 'boolean',
        'auto_translate_content' => 'boolean',
        // New casts from portal_users
        'legacy_permissions' => 'array',
        'settings' => 'array',
        'notification_preferences' => 'array',
        'call_notification_preferences' => 'array',
        'accessible_company_ids' => 'array',
        'is_active' => 'boolean',
        'can_access_child_companies' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
    
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id', 'id');
    }
    
    public function getCompanyIdAttribute()
    {
        // First check if we have a real company_id in the database
        if (isset($this->attributes['company_id']) && $this->attributes['company_id']) {
            return $this->attributes['company_id'];
        }
        
        // Otherwise, get company through tenant relationship
        if ($this->tenant_id) {
            // The correct relationship is Company hasMany Tenants
            // So we need to find the company that owns this tenant
            $tenant = \App\Models\Tenant::find($this->tenant_id);
            if ($tenant && $tenant->company_id) {
                return $tenant->company_id;
            }
        }
        
        // Last resort: first active company
        $company = \App\Models\Company::where('is_active', true)->first();
        return $company?->id;
    }
    
    public function canAccessPanel(Panel $panel): bool
    {
        // Check panel ID to determine access
        if ($panel->getId() === 'admin') {
            // Admin panel: Only for super admins and system administrators
            return $this->hasAnyRole(['Super Admin', 'super_admin', 'Admin']);
        }
        
        if ($panel->getId() === 'business') {
            // Business panel: For company users
            return $this->hasAnyRole(['company_owner', 'company_admin', 'company_manager', 'company_staff']);
        }
        
        // Allow authenticated users by default
        return true;
    }
    
    /**
     * Get the default URL for Filament
     */
    public function getFilamentDefaultUrl(): string
    {
        // Redirect to appropriate panel based on role
        if ($this->hasAnyRole(['Super Admin', 'super_admin', 'Admin'])) {
            return '/admin';
        }
        
        if ($this->hasAnyRole(['company_owner', 'company_admin', 'company_manager', 'company_staff'])) {
            return '/business';
        }
        
        // Default to admin panel
        return '/admin';
    }
    
    /**
     * Check if 2FA is enforced for this user
     */
    public function isTwoFactorEnforced(): bool
    {
        return $this->two_factor_enforced;
    }
    
    /**
     * Check if user has configured 2FA
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }
    
    /**
     * Get the user's preferred 2FA method
     */
    public function getTwoFactorMethod(): string
    {
        return $this->two_factor_method ?? 'authenticator';
    }
    
    /**
     * Check if user needs to setup 2FA
     */
    public function needsTwoFactorSetup(): bool
    {
        return $this->isTwoFactorEnforced() && !$this->hasEnabledTwoFactorAuthentication();
    }
    
    /**
     * Force enable 2FA for this user (admin action)
     */
    public function enforceTwoFactor(): void
    {
        $this->update(['two_factor_enforced' => true]);
    }
    
    /**
     * Disable 2FA enforcement for this user (admin action)
     */
    public function disableTwoFactorEnforcement(): void
    {
        $this->update(['two_factor_enforced' => false]);
    }
    
    /**
     * Favorite commands
     */
    public function favoriteCommands(): BelongsToMany
    {
        return $this->belongsToMany(CommandTemplate::class, 'command_favorites')
            ->withTimestamps();
    }
    
    /**
     * Favorite workflows
     */
    public function favoriteWorkflows(): BelongsToMany
    {
        return $this->belongsToMany(CommandWorkflow::class, 'workflow_favorites')
            ->withTimestamps();
    }
    
    /**
     * Created commands
     */
    public function createdCommands(): HasMany
    {
        return $this->hasMany(CommandTemplate::class, 'created_by');
    }
    
    /**
     * Created workflows
     */
    public function createdWorkflows(): HasMany
    {
        return $this->hasMany(CommandWorkflow::class, 'created_by');
    }
    
    /**
     * Command executions
     */
    public function commandExecutions(): HasMany
    {
        return $this->hasMany(CommandExecution::class);
    }
    
    /**
     * Workflow executions
     */
    public function workflowExecutions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }
    
    /**
     * Check if user has a specific permission (company-level)
     */
    public function hasCompanyPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->hasRole('Super Admin') || $this->hasRole('super_admin')) {
            return true;
        }
        
        // Company owner has all company permissions
        if ($this->hasRole('company_owner')) {
            return true;
        }
        
        // Check through Spatie permissions
        return $this->hasPermissionTo($permission);
    }
    
    /**
     * Check if user can view all company data
     */
    public function canViewAllCompanyData(): bool
    {
        return $this->hasAnyRole(['Super Admin', 'super_admin', 'company_owner', 'company_admin']);
    }
    
    /**
     * Check if user can manage team
     */
    public function canManageTeam(): bool
    {
        return $this->hasCompanyPermission('company.team.manage');
    }
    
    /**
     * Check if user can view billing
     */
    public function canViewBilling(): bool
    {
        return $this->hasCompanyPermission('company.billing.view');
    }
    
    /**
     * Check if user can manage billing
     */
    public function canManageBilling(): bool
    {
        return $this->hasCompanyPermission('company.billing.manage');
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
                'appointments' => $this->company?->needsAppointmentBooking() ? ['reminder_24h'] : [],
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
    public function recordLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? '127.0.0.1',
        ]);
    }
    
    /**
     * Check if 2FA is required based on role
     */
    public function requires2FA(): bool
    {
        // Enforced by role
        if ($this->hasAnyRole(['Super Admin', 'super_admin', 'company_owner', 'company_admin'])) {
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
     * Scope by portal role (for backward compatibility during migration)
     */
    public function scopePortalRole($query, string $role)
    {
        return $query->where('portal_role', $role);
    }
    
    /**
     * Get team members (for managers)
     */
    public function teamMembers()
    {
        if (!$this->hasAnyRole(['Super Admin', 'super_admin', 'company_owner', 'company_admin', 'company_manager'])) {
            return collect();
        }

        return static::where('company_id', $this->company_id)
            ->when($this->hasRole('company_manager'), function ($query) {
                // Managers can only see staff
                $query->role('company_staff');
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
        return $this->hasMany(\App\Models\BalanceTransaction::class, 'created_by');
    }

    /**
     * Balance topups initiated by this user
     */
    public function initiatedTopups()
    {
        return $this->hasMany(\App\Models\BalanceTopup::class, 'initiated_by');
    }
    
    /**
     * Check if user has access to multiple companies
     */
    public function hasMultiCompanyAccess(): bool
    {
        return $this->can_access_child_companies && !empty($this->accessible_company_ids);
    }
    
    /**
     * Get accessible company IDs
     */
    public function getAccessibleCompanyIds(): array
    {
        if ($this->hasRole(['Super Admin', 'super_admin'])) {
            return \App\Models\Company::pluck('id')->toArray();
        }
        
        if ($this->hasMultiCompanyAccess()) {
            return array_merge([$this->company_id], $this->accessible_company_ids ?? []);
        }
        
        return [$this->company_id];
    }
}
