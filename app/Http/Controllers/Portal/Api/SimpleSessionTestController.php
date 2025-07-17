<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SimpleSessionTestController extends Controller
{
    public function test(Request $request)
    {
        // Force session start
        if (!session()->isStarted()) {
            session()->start();
        }
        
        // Get the correct Laravel session key
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        // Get all session data
        $allSessionData = session()->all();
        
        // Get session from database
        $sessionId = session()->getId();
        $dbSession = DB::table('sessions')
            ->where('id', $sessionId)
            ->first();
            
        // Try to check if Laravel session key exists
        $hasPortalAuth = isset($allSessionData[$portalSessionKey]);
        
        // Check if we can authenticate from session
        $canAuthenticate = false;
        $userId = $allSessionData[$portalSessionKey] ?? $allSessionData['portal_user_id'] ?? null;
        if ($userId) {
            $user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);
            if ($user && $user->is_active) {
                $canAuthenticate = true;
            }
        }
        
        return response()->json([
            'session_info' => [
                'id' => $sessionId,
                'driver' => config('session.driver'),
                'cookie_name' => config('session.cookie'),
                'has_started' => session()->isStarted(),
            ],
            'session_data' => [
                'portal_session_key' => $portalSessionKey,
                'portal_session_value' => $allSessionData[$portalSessionKey] ?? null,
                'portal_user_id' => $allSessionData['portal_user_id'] ?? null,
                'portal_login' => $allSessionData['portal_login'] ?? null,
                'all_keys' => array_keys($allSessionData),
            ],
            'auth_status' => [
                'portal' => Auth::guard('portal')->check(),
                'web' => Auth::guard('web')->check(),
            ],
            'db_session' => $dbSession ? [
                'user_id' => $dbSession->user_id,
                'ip_address' => $dbSession->ip_address,
                'last_activity' => $dbSession->last_activity,
                'payload_size' => strlen($dbSession->payload),
            ] : null,
            'can_authenticate' => $canAuthenticate,
            'has_portal_auth_key' => $hasPortalAuth,
            'cookies' => [
                'session_cookie' => $request->cookie(config('session.cookie')),
                'all_cookies' => array_keys($request->cookies->all()),
            ],
        ]);
    }
}