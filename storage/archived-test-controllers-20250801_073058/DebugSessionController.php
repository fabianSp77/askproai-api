<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebugSessionController extends BaseApiController
{
    public function debug(Request $request)
    {
        // Get all session data
        $sessionData = session()->all();
        $sessionId = session()->getId();
        
        // Get the expected session keys
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        $webSessionKey = 'login_web_' . sha1('Illuminate\Auth\SessionGuard');
        
        // Check database for session
        $dbSession = DB::table('sessions')->where('id', $sessionId)->first();
        
        // Check what middleware set
        $companyId = app()->has('current_company_id') ? app('current_company_id') : null;
        
        return response()->json([
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'cookies' => $request->cookies->all(),
                'headers' => [
                    'cookie' => $request->header('Cookie'),
                    'csrf' => $request->header('X-CSRF-TOKEN'),
                ],
            ],
            'session' => [
                'id' => $sessionId,
                'driver' => config('session.driver'),
                'cookie_name' => config('session.cookie'),
                'all_data' => $sessionData,
                'portal_key' => $portalSessionKey,
                'web_key' => $webSessionKey,
                'portal_user_id' => $sessionData[$portalSessionKey] ?? null,
                'web_user_id' => $sessionData[$webSessionKey] ?? null,
                'is_admin_viewing' => $sessionData['is_admin_viewing'] ?? false,
                'admin_impersonation' => $sessionData['admin_impersonation'] ?? null,
            ],
            'auth' => [
                'portal_check' => Auth::guard('portal')->check(),
                'web_check' => Auth::guard('web')->check(),
                'portal_user' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->id : null,
                'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
            ],
            'context' => [
                'company_id' => $companyId,
                'branch_id' => session('current_branch_id'),
            ],
            'db_session' => $dbSession ? [
                'exists' => true,
                'user_id' => $dbSession->user_id,
                'ip' => $dbSession->ip_address,
                'user_agent' => $dbSession->user_agent,
                'last_activity' => date('Y-m-d H:i:s', $dbSession->last_activity),
            ] : ['exists' => false],
        ]);
    }
}