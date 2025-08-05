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

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, TwoFactorAuthenticatable;
    
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'fname', 'lname', 'name', 'email', 'password', 'username', 'tenant_id', 'company_id',
        'date_created', 'date_updated', 'email_verified_at',
        'two_factor_enforced', 'two_factor_method', 'two_factor_phone_number', 'two_factor_phone_verified'
    ];
    
    protected $appends = ['company_id'];
    
    protected $hidden = [
        'password', 'remember_token', 'salt', 'legacypassword',
        'two_factor_secret', 'two_factor_recovery_codes',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_enforced' => 'boolean',
        'two_factor_phone_verified' => 'boolean',
    ];
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
    
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id', 'id')
            ->withDefault(function () {
                // If no company_id is set, try to find one based on tenant
                if ($this->tenant_id) {
                    // The correct relationship is Company hasMany Tenants
                    // So we need to find the company that owns this tenant
                    $tenant = \App\Models\Tenant::find($this->tenant_id);
                    if ($tenant && $tenant->company_id) {
                        $company = \App\Models\Company::find($tenant->company_id);
                        if ($company) {
                            return $company;
                        }
                    }
                }
                
                // Fallback to first active company
                return \App\Models\Company::where('is_active', true)->first() ?: new \App\Models\Company();
            });
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
        // Allow all authenticated users to access the admin panel
        // Since Gate::before() was removed, we need to ensure access
        return true;
    }
    
    /**
     * Get the default URL for Filament
     */
    public function getFilamentDefaultUrl(): string
    {
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
}
