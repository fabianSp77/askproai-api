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
        // Skip if tenant scope is disabled (e.g., for admin API)
        if (app()->has('disable_tenant_scope') && app('disable_tenant_scope') === true) {
            return;
        }
        
        // Skip for webhooks and API routes (no user context)
        if (request()->is('api/retell/*') || request()->is('api/webhook*') || request()->is('api/*/webhook*')) {
            return; // Don't apply tenant filtering for webhooks
        }
        
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
     * Vereinfachte Version mit klaren Prioritäten
     */
    private function getCurrentCompanyId(): ?string
    {
        // PRIORITY 1: Explizit gesetzter Tenant (für Background Jobs, Commands, etc.)
        if (app()->bound('current_company_id')) {
            return app('current_company_id');
        }
        
        // PRIORITY 2: Authentifizierter User (Guard-spezifisch)
        // 2a. Portal Guard (Business Portal)
        if (Auth::guard('portal')->check()) {
            $portalUser = Auth::guard('portal')->user();
            if (isset($portalUser->company_id) && $portalUser->company_id) {
                return $portalUser->company_id;
            }
        }
        
        // 2b. Web Guard (Admin Portal) - check for admin impersonation
        if (Auth::guard('web')->check()) {
            // Check for admin impersonation session
            if (session()->has('admin_impersonation')) {
                $adminImpersonation = session('admin_impersonation');
                if (isset($adminImpersonation['company_id'])) {
                    return $adminImpersonation['company_id'];
                }
            }
            
            // Regular admin user with company_id
            $user = Auth::guard('web')->user();
            if (isset($user->company_id) && $user->company_id) {
                return $user->company_id;
            }
        }
        
        // 2c. Customer Guard (Customer Portal)
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();
            if (isset($customer->company_id) && $customer->company_id) {
                return $customer->company_id;
            }
        }
        
        // PRIORITY 3: Session fallback (nur für Web-Requests, kein API)
        if (!request()->is('api/*') && session()->has('current_company_id')) {
            return session('current_company_id');
        }
        
        // No fallback to headers or subdomains - these should be handled explicitly
        
        return null;
    }
}
