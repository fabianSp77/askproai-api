<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasTenancy
{
    /**
     * Boot the has tenancy trait for a model.
     */
    protected static function bootHasTenancy(): void
    {
        static::addGlobalScope(new TenantScope);

        // Automatically set tenant_id on create
        static::creating(function (Model $model) {
            if (Auth::check() && !$model->tenant_id) {
                $user = Auth::user();
                if ($user->tenant_id) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Scope a query to only include models of a given tenant.
     */
    public function scopeOfTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Determine if the model belongs to the given tenant.
     */
    public function belongsToTenant($tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }
}