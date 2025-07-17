<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebugAuthController extends Controller
{
    public function checkAuth(Request $request)
    {
        $portalUser = Auth::guard('portal')->user();
        $webUser = Auth::guard('web')->user();
        
        return response()->json([
            'portal_auth' => [
                'authenticated' => Auth::guard('portal')->check(),
                'user' => $portalUser ? [
                    'id' => $portalUser->id,
                    'email' => $portalUser->email,
                    'company_id' => $portalUser->company_id,
                    'role' => $portalUser->role
                ] : null
            ],
            'web_auth' => [
                'authenticated' => Auth::guard('web')->check(),
                'user' => $webUser ? [
                    'id' => $webUser->id,
                    'email' => $webUser->email
                ] : null
            ],
            'session' => [
                'portal_user_id' => session('portal_user_id'),
                'portal_login' => session('portal_login'),
                'admin_impersonate_portal_user' => session('admin_impersonate_portal_user'),
                'session_id' => session()->getId()
            ],
            'company_context' => [
                'current_company_id' => app()->has('current_company_id') ? app('current_company_id') : null,
                'current_branch_id' => session('current_branch_id')
            ],
            'request_info' => [
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
                'method' => $request->method()
            ]
        ]);
    }
    
    public function debug(Request $request)
    {
        // Get the Laravel auth session key for portal
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        // Debug information
        $debugInfo = [
            'timestamp' => now()->toIso8601String(),
            'auth_checks' => [
                'portal' => Auth::guard('portal')->check(),
                'web' => Auth::guard('web')->check(),
            ],
            'session_data' => [
                'id' => session()->getId(),
                'is_started' => session()->isStarted(),
                'portal_session_key' => session($portalSessionKey),
                'portal_user_id' => session('portal_user_id'),
                'portal_login' => session('portal_login'),
            ],
            'request_info' => [
                'has_cookie' => $request->hasCookie(config('session.cookie')),
                'is_ajax' => $request->ajax(),
                'headers' => [
                    'x-requested-with' => $request->header('X-Requested-With'),
                    'accept' => $request->header('Accept'),
                ],
            ],
        ];
        
        // Try to authenticate if session exists
        if (!Auth::guard('portal')->check()) {
            $userId = session($portalSessionKey) ?? session('portal_user_id');
            if ($userId) {
                $user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);
                if ($user && $user->is_active) {
                    Auth::guard('portal')->login($user);
                    $debugInfo['manual_auth'] = true;
                    $debugInfo['user_found'] = true;
                }
            }
        }
        
        return response()->json($debugInfo);
    }
}