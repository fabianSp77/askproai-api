<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DebugLoginController extends Controller
{
    public function debugLogin(Request $request)
    {
        // Clear any existing portal auth
        Auth::guard('portal')->logout();
        
        // Find demo user
        $user = PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            return response()->json([
                'error' => 'Demo user not found',
                'available_users' => PortalUser::withoutGlobalScopes()->pluck('email')->toArray(),
            ]);
        }
        
        // Try to login
        Auth::guard('portal')->login($user);
        
        // Set session data
        $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
        session([$sessionKey => $user->id]);
        session(['portal_user_id' => $user->id]);
        session(['company_id' => $user->company_id]);
        
        // Force session save
        session()->save();
        
        return response()->json([
            'login_status' => 'success',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ],
            'session' => [
                'id' => session()->getId(),
                'name' => session()->getName(),
                'cookie' => config('session.cookie'),
                'domain' => config('session.domain'),
                'path' => config('session.path'),
                'all_keys' => array_keys(session()->all()),
            ],
            'auth' => [
                'portal_check' => auth()->guard('portal')->check(),
                'portal_id' => auth()->guard('portal')->id(),
            ],
            'cookies' => [
                'portal_session' => request()->cookie('askproai_portal_session'),
                'all' => array_keys(request()->cookies->all()),
            ],
            'redirect_url' => '/business/dashboard',
        ]);
    }
}