<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;
    
    protected $table = 'laravel_users';
    protected $primaryKey = 'id';
    public $timestamps = true;
    
    protected $fillable = [
        'fname', 'lname', 'name', 'email', 'password', 'username', 'tenant_id', 'company_id',
        'date_created', 'date_updated', 'email_verified_at'
    ];
    
    protected $appends = ['company_id'];
    
    protected $hidden = [
        'password', 'remember_token', 'salt', 'legacypassword',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
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
}
