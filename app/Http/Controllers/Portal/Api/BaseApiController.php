<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;

abstract class BaseApiController extends Controller
{
    /**
     * Get the company for the current user/admin
     */
    protected function getCompany(): ?Company
    {
        // Check if we have a company context set by middleware
        if (app()->has('current_company_id')) {
            $companyId = app('current_company_id');
            return Company::withoutGlobalScopes()->find($companyId);
        }
        
        $user = auth()->guard('portal')->user();
        $webUser = auth()->guard('web')->user();
        
        if (!$user && !$webUser) {
            return null;
        }
        
        // Handle admin viewing
        if (!$user && $webUser && session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            return Company::withoutGlobalScopes()->find($companyId);
        } else if ($user) {
            return $user->company;
        }
        
        return null;
    }
    
    /**
     * Get the current user (portal or admin)
     */
    protected function getCurrentUser()
    {
        $user = auth()->guard('portal')->user();
        if ($user) {
            return $user;
        }
        
        $webUser = auth()->guard('web')->user();
        if ($webUser && session('is_admin_viewing')) {
            return $webUser;
        }
        
        return null;
    }
}