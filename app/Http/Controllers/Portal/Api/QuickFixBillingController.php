<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class QuickFixBillingController extends BillingApiController
{
    /**
     * Override getUsage with session fix
     */
    public function getUsageFixed(Request $request)
    {
        // Manually check and restore session
        if (!Auth::guard('portal')->check()) {
            $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
            $userId = session($portalSessionKey) ?? session('portal_user_id');
            
            if ($userId) {
                $user = PortalUser::withoutGlobalScopes()->find($userId);
                if ($user && $user->is_active && $user->company && $user->company->is_active) {
                    Auth::guard('portal')->login($user);
                    app()->instance('current_company_id', $user->company_id);
                }
            }
        }
        
        // Now call the parent method
        return parent::getUsage($request);
    }
}