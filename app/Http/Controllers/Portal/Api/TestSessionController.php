<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestSessionController extends BaseApiController
{
    public function test(Request $request)
    {
        // Force session start
        if (!session()->isStarted()) {
            session()->start();
        }
        
        $sessionId = session()->getId();
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        // Get all session data
        $sessionData = session()->all();
        
        // Check database session
        $dbSession = DB::table('sessions')->where('id', $sessionId)->first();
        
        return response()->json([
            'current_session_id' => $sessionId,
            'cookie_session_id' => $request->cookie(config('session.cookie')),
            'session_driver' => config('session.driver'),
            'session_cookie_name' => config('session.cookie'),
            'all_cookies' => $request->cookies->all(),
            'session_data' => $sessionData,
            'portal_auth_key' => $portalSessionKey,
            'portal_user_id' => $sessionData[$portalSessionKey] ?? null,
            'is_admin_viewing' => $sessionData['is_admin_viewing'] ?? false,
            'admin_impersonation' => $sessionData['admin_impersonation'] ?? null,
            'guards' => [
                'portal' => Auth::guard('portal')->check(),
                'web' => Auth::guard('web')->check(),
            ],
            'users' => [
                'portal' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->id : null,
                'web' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
            ],
            'db_session' => $dbSession ? [
                'id' => $dbSession->id,
                'user_id' => $dbSession->user_id,
                'last_activity' => date('Y-m-d H:i:s', $dbSession->last_activity),
            ] : null,
            'request_headers' => [
                'cookie' => $request->header('Cookie'),
                'user_agent' => $request->header('User-Agent'),
                'referer' => $request->header('Referer'),
            ],
        ]);
    }
}