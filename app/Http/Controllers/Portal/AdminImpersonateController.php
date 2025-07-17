<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminImpersonateController extends Controller
{
    public function impersonate(Request $request)
    {
        // Check if user is admin
        if (!Auth::guard('web')->check()) {
            return redirect()->route('business.login')
                ->with('error', 'You must be logged in as admin to impersonate portal users.');
        }
        
        $portalUserId = $request->input('portal_user_id', 22); // Default to demo user
        $portalUser = PortalUser::withoutGlobalScopes()->find($portalUserId);
        
        if (!$portalUser || !$portalUser->is_active) {
            return back()->with('error', 'Portal user not found or inactive.');
        }
        
        // Store impersonation in session
        session(['admin_impersonate_portal_user' => $portalUser->id]);
        
        // Login as portal user WITHOUT logging out admin
        Auth::guard('portal')->login($portalUser);
        
        // Store in session for API access
        session(['portal_user_id' => $portalUser->id]);
        
        // Set company context
        app()->instance('current_company_id', $portalUser->company_id);
        
        return redirect()->route('business.dashboard')
            ->with('success', 'Now viewing portal as ' . $portalUser->email);
    }
    
    public function stopImpersonate()
    {
        // Remove impersonation
        session()->forget('admin_impersonate_portal_user');
        session()->forget('portal_user_id');
        
        // Logout from portal guard
        Auth::guard('portal')->logout();
        
        return redirect()->route('business.admin-switch')
            ->with('success', 'Stopped impersonating portal user.');
    }
    
    public function showSwitch()
    {
        return view('portal.auth.admin-portal-switch');
    }
}