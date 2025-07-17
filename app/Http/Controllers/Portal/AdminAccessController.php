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
     * Handle admin access to business portal with proper session isolation
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
        
        // Get company
        $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($tokenData['company_id']);
        if (!$company) {
            abort(404, 'Firma nicht gefunden.');
        }
        
        // Delete the token (one-time use)
        cache()->forget('admin_portal_access_' . $token);
        
        // CRITICAL: Perform clean portal switch
        $this->performCleanPortalSwitch($admin, $company, $tokenData['redirect_to'] ?? null);
    }
    
    /**
     * Perform a clean portal switch with proper session isolation
     */
    protected function performCleanPortalSwitch(User $admin, Company $company, ?string $redirectTo)
    {
        // 1. Save critical data before session flush
        $adminId = $admin->id;
        $adminEmail = $admin->email;
        $companyId = $company->id;
        $companyName = $company->name;
        
        // 2. Complete logout from all guards
        Auth::guard('web')->logout();
        Auth::guard('portal')->logout();
        
        // 3. Flush ALL session data to prevent pollution
        Session::flush();
        
        // 4. Regenerate session ID for security
        Session::regenerate(true);
        
        // 5. Set portal context FIRST
        Session::put('current_portal', 'business');
        Session::put('active_company_id', $companyId);
        Session::put('portal_company_id', $companyId);
        
        // 6. Set admin impersonation flags
        Session::put('admin_impersonation', [
            'admin_id' => $adminId,
            'admin_email' => $adminEmail,
            'company_id' => $companyId,
            'company_name' => $companyName,
            'started_at' => now()->toIso8601String(),
            'from_portal' => 'admin',
        ]);
        
        Session::put('is_admin_viewing', true);
        Session::put('admin_viewing_company', $companyName);
        
        // 7. Force session save before creating portal user
        Session::save();
        
        // 8. Create or get portal user
        $portalUser = $this->getOrCreatePortalUser($company);
        
        // 9. Login as portal user
        Auth::guard('portal')->login($portalUser, true);
        
        // 10. Set portal-specific session data
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        Session::put($portalSessionKey, $portalUser->id);
        Session::put('portal_user_id', $portalUser->id);
        
        // 11. Regenerate CSRF token for new session
        Session::regenerateToken();
        
        // 12. Final session save
        Session::save();
        
        // 13. Log the switch
        \Log::info('Clean portal switch completed', [
            'admin_id' => $adminId,
            'company_id' => $companyId,
            'portal_user_id' => $portalUser->id,
            'session_id' => Session::getId(),
            'portal_auth' => Auth::guard('portal')->check(),
        ]);
        
        // 14. Redirect to business portal
        return redirect($redirectTo ?: route('business.dashboard'))
            ->with('portal_switched', true);
    }
    
    /**
     * Get or create portal user for admin access
     */
    protected function getOrCreatePortalUser(Company $company): PortalUser
    {
        $email = 'admin+' . $company->id . '@askproai.de';
        
        // Check if exists using raw query to bypass scopes
        $existing = DB::table('portal_users')
            ->where('email', $email)
            ->where('company_id', $company->id)
            ->first();
        
        if ($existing) {
            // Update last login
            DB::table('portal_users')
                ->where('id', $existing->id)
                ->update([
                    'last_login_at' => now(),
                    'is_active' => true,
                ]);
            
            return PortalUser::withoutGlobalScopes()->find($existing->id);
        }
        
        // Create new portal user
        $userId = DB::table('portal_users')->insertGetId([
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
            'last_login_at' => now(),
        ]);
        
        return PortalUser::withoutGlobalScopes()->find($userId);
    }
    
    /**
     * Exit admin access with clean session restoration
     */
    public function exitAdminAccess()
    {
        $impersonation = Session::get('admin_impersonation');
        
        if (!$impersonation) {
            return redirect('/admin');
        }
        
        // 1. Get admin ID before clearing
        $adminId = $impersonation['admin_id'] ?? null;
        
        // 2. Logout from portal
        Auth::guard('portal')->logout();
        
        // 3. Flush portal session
        Session::flush();
        
        // 4. Regenerate session for admin
        Session::regenerate(true);
        
        // 5. Set admin portal context
        Session::put('current_portal', 'admin');
        
        // 6. Re-login as admin if we have the ID
        if ($adminId) {
            $admin = User::find($adminId);
            if ($admin) {
                Auth::guard('web')->login($admin, true);
                
                // Set admin session data
                Session::put('is_admin', true);
                Session::regenerateToken();
            }
        }
        
        // 7. Save session
        Session::save();
        
        // 8. Redirect to admin panel
        return redirect('/admin/business-portal-admin')
            ->with('success', 'Admin-Zugriff beendet.')
            ->with('portal_exited', true);
    }
}