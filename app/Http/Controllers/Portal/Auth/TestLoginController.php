<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TestLoginController extends Controller
{
    public function testLoginDirect(Request $request)
    {
        // Clear any existing sessions
        Auth::guard('portal')->logout();
        $request->session()->flush();
        
        // Find demo user
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Login user
        Auth::guard('portal')->login($user);
        
        // Store session data
        $request->session()->put('portal_user_id', $user->id);
        $request->session()->put('portal_login', $user->id);
        
        // Store Laravel auth key
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        $request->session()->put($portalSessionKey, $user->id);
        
        // Force save
        $request->session()->save();
        
        return response()->json([
            'login_success' => true,
            'user_id' => $user->id,
            'auth_check' => Auth::guard('portal')->check(),
            'session_id' => $request->session()->getId(),
            'session_data' => [
                'portal_user_id' => $request->session()->get('portal_user_id'),
                'portal_login' => $request->session()->get('portal_login'),
                'auth_key' => $request->session()->get($portalSessionKey),
                'all_keys' => array_keys($request->session()->all())
            ]
        ]);
    }
    
    public function checkSession(Request $request)
    {
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        return response()->json([
            'session_id' => $request->session()->getId(),
            'auth_check' => Auth::guard('portal')->check(),
            'user' => Auth::guard('portal')->user() ? [
                'id' => Auth::guard('portal')->user()->id,
                'email' => Auth::guard('portal')->user()->email
            ] : null,
            'session_data' => [
                'portal_user_id' => $request->session()->get('portal_user_id'),
                'portal_login' => $request->session()->get('portal_login'),
                'auth_key' => $request->session()->get($portalSessionKey),
                'all_keys' => array_keys($request->session()->all())
            ]
        ]);
    }
}