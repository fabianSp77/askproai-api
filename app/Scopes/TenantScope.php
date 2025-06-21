<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant Scope für Multi-Mandanten-System
 * Filtert automatisch alle Queries nach company_id
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Skip for super admins and resellers (only for admin users)
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('reseller'))) {
                return; // Don't apply any filtering
            }
        }
        
        // Mehrere Möglichkeiten, die aktuelle Company zu bestimmen
        $companyId = $this->getCurrentCompanyId();
        
        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        } else {
            // CRITICAL: If no company context is set, throw exception
            // This prevents silent failures and data issues
            if (app()->environment(['production', 'staging'])) {
                throw new \App\Exceptions\MissingTenantException(
                    'No company context found for model: ' . get_class($model)
                );
            }
            
            // In development/testing, just return no records
            $builder->where($model->getTable() . '.id', '<', 0); // This will never match any records
        }
    }
    
    /**
     * Ermittelt die aktuelle Company ID aus verschiedenen Quellen
     */
    private function getCurrentCompanyId(): ?int
    {
        // 1. Explizit gesetzter Tenant (z.B. für Background Jobs)
        if (app()->bound('current_company_id')) {
            return app('current_company_id');
        }
        
        // 2. Aus dem authentifizierten User
        if (Auth::check()) {
            $user = Auth::user();
            // Use the company_id attribute
            if (isset($user->company_id) && $user->company_id) {
                return $user->company_id;
            }
        }
        
        // 2b. Check customer auth guard specifically
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            if (isset($customer->company_id) && $customer->company_id) {
                return $customer->company_id;
            }
        }
        
        // 3. Aus der Session (für Web-Requests)
        if (session()->has('current_company_id')) {
            return session('current_company_id');
        }
        
        // 4. Aus dem Request Header (für API-Requests)
        if (request()->hasHeader('X-Company-ID')) {
            return (int) request()->header('X-Company-ID');
        }
        
        // 5. Aus der Subdomain (für Multi-Tenant per Subdomain)
        if (app()->bound('tenant')) {
            $tenant = app('tenant');
            return $tenant?->id;
        }
        
        return null;
    }
}
