<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class ForceAuthController extends Controller
{
    public function forceAuth(Request $request)
    {
        // Get the Laravel auth session key for portal
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        // Get user ID from session
        $userId = session($portalSessionKey) ?? session('portal_user_id');
        
        $debugInfo = [
            'before' => [
                'portal_auth' => Auth::guard('portal')->check(),
                'portal_user' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->id : null,
                'web_auth' => Auth::guard('web')->check(),
                'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
            ],
            'session' => [
                'portal_key' => $portalSessionKey,
                'portal_value' => session($portalSessionKey),
                'portal_user_id' => session('portal_user_id'),
            ],
        ];
        
        if ($userId) {
            // Load user without scopes
            $user = PortalUser::withoutGlobalScopes()->find($userId);
            
            if ($user && $user->is_active) {
                // Force logout from web guard if needed
                if (Auth::guard('web')->check()) {
                    Auth::guard('web')->logout();
                    $debugInfo['web_logout'] = true;
                }
                
                // Force login to portal guard
                Auth::guard('portal')->login($user);
                
                // Set company context
                app()->instance('current_company_id', $user->company_id);
                
                // Set branch context
                if (!session('current_branch_id')) {
                    $branch = \App\Models\Branch::withoutGlobalScopes()
                        ->where('company_id', $user->company_id)
                        ->first();
                    if ($branch) {
                        session(['current_branch_id' => $branch->id]);
                    }
                }
                
                $debugInfo['after'] = [
                    'portal_auth' => Auth::guard('portal')->check(),
                    'portal_user' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->id : null,
                    'web_auth' => Auth::guard('web')->check(),
                    'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
                ];
                
                $debugInfo['success'] = true;
                $debugInfo['user'] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                ];
                
                // Now try to call the dashboard API
                $dashboardController = new DashboardApiController();
                try {
                    $dashboardResponse = $dashboardController->index($request);
                    $debugInfo['dashboard_test'] = [
                        'status' => $dashboardResponse->status(),
                        'has_data' => $dashboardResponse->status() === 200,
                    ];
                } catch (\Exception $e) {
                    $debugInfo['dashboard_test'] = [
                        'error' => $e->getMessage(),
                    ];
                }
            } else {
                $debugInfo['error'] = 'User not found or not active';
            }
        } else {
            $debugInfo['error'] = 'No user ID in session';
        }
        
        return response()->json($debugInfo);
    }
}