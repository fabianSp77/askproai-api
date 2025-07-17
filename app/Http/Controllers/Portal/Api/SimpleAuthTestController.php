<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class SimpleAuthTestController extends Controller
{
    public function test(Request $request)
    {
        // Get the session key that Laravel uses for portal auth
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        $sessionData = [
            'session_id' => session()->getId(),
            'portal_user_id' => session('portal_user_id'),
            'portal_login' => session('portal_login'),
            'laravel_portal_auth' => session($portalSessionKey),
        ];
        
        // Try to manually restore the auth
        $userId = session($portalSessionKey) ?? session('portal_user_id');
        $restored = false;
        $user = null;
        
        if ($userId) {
            $user = PortalUser::withoutGlobalScopes()->find($userId);
            if ($user) {
                Auth::guard('portal')->login($user);
                $restored = true;
                
                // Set company context
                app()->instance('current_company_id', $user->company_id);
            }
        }
        
        return response()->json([
            'session' => $sessionData,
            'restored' => $restored,
            'auth_check' => Auth::guard('portal')->check(),
            'user' => Auth::guard('portal')->user() ? [
                'id' => Auth::guard('portal')->user()->id,
                'email' => Auth::guard('portal')->user()->email,
            ] : null,
            'guards' => [
                'web' => Auth::guard('web')->check(),
                'portal' => Auth::guard('portal')->check(),
            ]
        ]);
    }
}