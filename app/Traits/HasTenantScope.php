<?php

namespace App\Traits;

use App\Scopes\TenantScope;

/**
 * Trait for models that belong to a tenant (company)
 * Automatically applies tenant filtering
 */
trait HasTenantScope
{
    /**
     * Boot the trait and add the global scope
     */
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Automatically set company_id on create if not set
        static::creating(function ($model) {
            if (!$model->company_id && app()->bound('current_company_id')) {
                $model->company_id = app('current_company_id');
            }
        });
    }
    
    /**
     * Get the company this model belongs to
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
    
    /**
     * Scope to include all tenants (bypass tenant filtering)
     */
    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
    
    /**
     * Scope to filter by specific tenant
     */
    public function scopeForTenant($query, $companyId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
                     ->where('company_id', $companyId);
    }
}