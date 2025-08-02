<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebugBillingController extends BaseApiController
{
    /**
     * Debug authentication for billing API
     */
    public function debugAuth(Request $request)
    {
        $sessionData = [
            'session_id' => session()->getId(),
            'session_driver' => session()->getDefaultDriver(),
            'session_config' => [
                'cookie' => config('session.cookie'),
                'path' => config('session.path'),
                'domain' => config('session.domain'),
            ],
            'portal_session_config' => [
                'cookie' => config('session_portal.cookie'),
                'path' => config('session_portal.path'),
                'domain' => config('session_portal.domain'),
            ],
        ];
        
        // Check various auth methods
        $authData = [
            'portal_guard_check' => Auth::guard('portal')->check(),
            'portal_guard_user' => Auth::guard('portal')->user() ? [
                'id' => Auth::guard('portal')->user()->id,
                'email' => Auth::guard('portal')->user()->email,
                'company_id' => Auth::guard('portal')->user()->company_id,
            ] : null,
            'web_guard_check' => Auth::guard('web')->check(),
            'web_guard_user' => Auth::guard('web')->user() ? [
                'id' => Auth::guard('web')->user()->id,
                'email' => Auth::guard('web')->user()->email,
            ] : null,
        ];
        
        // Check session keys
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        $sessionKeys = [
            'portal_session_key' => $portalSessionKey,
            'portal_session_value' => session($portalSessionKey),
            'portal_user_id' => session('portal_user_id'),
            'is_admin_viewing' => session('is_admin_viewing'),
            'admin_impersonation' => session('admin_impersonation'),
            'all_session_keys' => array_keys(session()->all()),
        ];
        
        // Check request data
        $requestData = [
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => [
                'accept' => $request->header('Accept'),
                'content-type' => $request->header('Content-Type'),
                'referer' => $request->header('Referer'),
                'x-requested-with' => $request->header('X-Requested-With'),
            ],
            'cookies' => $request->cookies->all(),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
        ];
        
        // Check company context
        $companyData = [
            'app_has_company_id' => app()->has('current_company_id'),
            'current_company_id' => app()->has('current_company_id') ? app('current_company_id') : null,
            'getCompany_result' => $this->getCompany() ? [
                'id' => $this->getCompany()->id,
                'name' => $this->getCompany()->name,
            ] : null,
        ];
        
        // Check middleware
        $middlewareData = [
            'route_middleware' => $request->route() ? $request->route()->middleware() : [],
            'route_action' => $request->route() ? $request->route()->getAction() : [],
        ];
        
        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'session' => $sessionData,
            'auth' => $authData,
            'session_keys' => $sessionKeys,
            'request' => $requestData,
            'company' => $companyData,
            'middleware' => $middlewareData,
            'debug_message' => 'Use this info to debug authentication issues',
        ], 200, ['Content-Type' => 'application/json'], JSON_PRETTY_PRINT);
    }
}