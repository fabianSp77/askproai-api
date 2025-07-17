<?php

use App\Support\InertiaFacade;

if (!function_exists('inertia')) {
    function inertia($component = null, $props = [])
    {
        if (is_null($component)) {
            return app(InertiaFacade::class);
        }
        
        return InertiaFacade::render($component, $props);
    }
}

if (!function_exists('isAdminViewingPortal')) {
    /**
     * Check if the current user is an admin viewing the business portal
     * 
     * @return bool
     */
    function isAdminViewingPortal(): bool
    {
        // Check if session indicates admin is viewing
        if (session('is_admin_viewing')) {
            return true;
        }
        
        // Check if we have admin impersonation details
        $adminImpersonation = session('admin_impersonation');
        if ($adminImpersonation && isset($adminImpersonation['admin_id'])) {
            return true;
        }
        
        // Check if web guard has an admin user (Super Admin role)
        $webUser = \Illuminate\Support\Facades\Auth::guard('web')->user();
        if ($webUser && $webUser->hasRole('Super Admin')) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('isPortalUser')) {
    /**
     * Check if the current user is a regular business portal user (not admin)
     * 
     * @return bool
     */
    function isPortalUser(): bool
    {
        // If admin is viewing, they are not a regular portal user
        if (isAdminViewingPortal()) {
            return false;
        }
        
        // Check if portal guard has a user
        return \Illuminate\Support\Facades\Auth::guard('portal')->check();
    }
}

if (!function_exists('canDeleteBusinessData')) {
    /**
     * Check if the current user can delete business data (appointments, customers, calls)
     * Only admins can delete this data
     * 
     * @return bool
     */
    function canDeleteBusinessData(): bool
    {
        return isAdminViewingPortal();
    }
}