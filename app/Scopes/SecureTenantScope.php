<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SecureTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // EMERGENCY FIX: Skip ALL scoping for admin panel
        if (request() && (
            request()->is('admin/*') || 
            request()->is('livewire/*') ||
            request()->is('admin') ||
            strpos(request()->path(), 'admin') === 0
        )) {
            return;
        }
        
        // Skip in console
        if (app()->runningInConsole()) {
            return;
        }
        
        // Only apply if we have a company context
        $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : null;
        
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }
}