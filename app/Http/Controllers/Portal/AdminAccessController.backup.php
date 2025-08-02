<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class AdminAccessController extends Controller
{
    /**
     * Handle admin access to business portal
     */
    public function access(Request $request)
    {
        $token = $request->get('token');
        
        if (!$token) {
            abort(403, 'Kein Zugriffstoken bereitgestellt.');
        }
        
        // Retrieve token data from cache
        $tokenData = cache()->get('admin_portal_access_' . $token);
        
        if (!$tokenData) {
            abort(403, 'Ungültiges oder abgelaufenes Token.');
        }
        
        // Verify admin is still authenticated
        $admin = User::find($tokenData['admin_id']);
        if (!$admin || !$admin->hasRole('Super Admin')) {
            abort(403, 'Keine Berechtigung für Admin-Zugriff.');
        }
        
        // Get company - MUST use withoutGlobalScope for admin access
        $company = Company::find($tokenData['company_id']);
        if (!$company) {
            abort(404, 'Firma nicht gefunden.');
        }
        
        // Get redirect URL if provided
        $redirectTo = $tokenData['redirect_to'] ?? null;
        
        // Delete the token (one-time use)
        cache()->forget('admin_portal_access_' . $token);
        
        // IMPORTANT: Logout any existing portal user first
        Auth::guard('portal')->logout();
        
        // Create a temporary portal session for admin
        $this->createAdminPortalSession($admin, $company);
        
        // Force session to persist before redirect
        Session::save();
        
        // IMPORTANT: Ensure the portal auth persists across redirect
        // Store portal user ID in the correct session key
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        Session::put($portalSessionKey, Auth::guard('portal')->id());
        Session::put('portal_user_id', Auth::guard('portal')->id());
        
        // Force another save after setting portal session
        Session::save();
        
        // Log for debugging
        \Log::info('AdminAccessController - Redirecting to portal', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'session_id' => Session::getId(),
            'admin_impersonation' => Session::get('admin_impersonation'),
            'is_admin_viewing' => Session::get('is_admin_viewing'),
            'portal_auth_check' => Auth::guard('portal')->check(),
            'portal_user' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->email : 'none',
            'portal_session_key' => Session::get($portalSessionKey),
            'redirect_to' => $redirectTo,
        ]);
        
        // Redirect to requested page or dashboard
        return redirect($redirectTo ?: route('business.dashboard'));
    }
    
    /**
     * Create a portal session for admin access
     */
    protected function createAdminPortalSession(User $admin, Company $company)
    {
        // Store current admin session (temporarily - will be updated with portal_user_id)
        Session::put('admin_impersonation', [
            'admin_id' => $admin->id,
            'company_id' => $company->id,
            'admin_session' => Auth::guard('web')->user() ? true : false,
            'started_at' => now(),
        ]);
        
        // Use raw DB queries to bypass all scopes and model events
        $email = 'admin+' . $company->id . '@askproai.de';
        
        // Check if portal user exists
        $existingUser = \DB::table('portal_users')
            ->where('email', $email)
            ->where('company_id', $company->id)
            ->first();
        
        if (!$existingUser) {
            // Create new portal user using raw insert
            $userId = \DB::table('portal_users')->insertGetId([
                'email' => $email,
                'company_id' => $company->id,
                'name' => 'Admin Access',
                'password' => bcrypt(bin2hex(random_bytes(32))),
                'role' => 'admin',
                'permissions' => json_encode([
                    'full_access' => true,
                    'billing.view' => true,
                    'billing.pay' => true,
                    'calls.view_all' => true,
                    'calls.edit_all' => true,
                    'appointments.view_all' => true,
                    'analytics.view_all' => true,
                    'team.manage' => true,
                ]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $portalUser = (object) ['id' => $userId];
        } else {
            $portalUser = $existingUser;
        }
        
        // Update last login using raw query
        \DB::table('portal_users')
            ->where('id', $portalUser->id)
            ->update(['last_login_at' => now()]);
        
        // Add admin indicator to session BEFORE login
        Session::put('is_admin_viewing', true);
        Session::put('admin_viewing_company', $company->name);
        
        // Update admin_impersonation with portal_user_id
        $impersonation = Session::get('admin_impersonation', []);
        $impersonation['portal_user_id'] = $portalUser->id;
        Session::put('admin_impersonation', $impersonation);
        
        // Force session save
        Session::save();
        
        // IMPORTANT: Actually login as the portal user so the React app can see them
        // First get the full portal user object
        $fullPortalUser = \App\Models\PortalUser::withoutGlobalScopes()->find($portalUser->id);
        if ($fullPortalUser) {
            // Ensure the user is active
            $fullPortalUser->is_active = true;
            $fullPortalUser->save();
            
            // Login with remember option to ensure session persistence
            Auth::guard('portal')->login($fullPortalUser, true);
            
            // Double-check the login worked
            if (!Auth::guard('portal')->check()) {
                \Log::error('Portal login failed even after login attempt', [
                    'portal_user_id' => $fullPortalUser->id,
                    'portal_user_email' => $fullPortalUser->email,
                ]);
            }
            
            // Log successful login
            \Log::info('Admin portal login successful', [
                'portal_user_id' => $fullPortalUser->id,
                'portal_user_email' => $fullPortalUser->email,
                'company_id' => $company->id,
                'auth_check' => Auth::guard('portal')->check(),
            ]);
        }
    }
    
    /**
     * Exit admin access and return to admin panel
     */
    public function exitAdminAccess()
    {
        $impersonation = Session::get('admin_impersonation');
        
        if (!$impersonation) {
            return redirect('/admin');
        }
        
        // Logout from portal
        Auth::guard('portal')->logout();
        
        // Clear impersonation session
        Session::forget('admin_impersonation');
        Session::forget('is_admin_viewing');
        Session::forget('admin_viewing_company');
        
        // Return to admin panel
        return redirect('/admin/business-portal-admin')
            ->with('success', 'Admin-Zugriff beendet.');
    }
}