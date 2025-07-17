<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class BypassLoginController extends Controller
{
    public function login(Request $request)
    {
        // Create or update test user
        $user = PortalUser::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'bypass-test@askproai.de'],
            [
                'name' => 'Bypass Test User',
                'password' => Hash::make('bypass123'),
                'company_id' => 1,
                'is_active' => true,
                'role' => 'admin',
                'permissions' => json_encode([
                    'calls.view_all' => true,
                    'billing.view' => true,
                    'billing.manage' => true,
                    'appointments.view_all' => true,
                    'customers.view_all' => true
                ])
            ]
        );
        
        // Force login without password check
        Auth::guard('portal')->login($user, true);
        
        // Record login
        $user->recordLogin($request->ip());
        
        // Set additional session data
        session(['portal_bypass_login' => true]);
        session(['portal_user_id' => $user->id]);
        session()->save(); // Force session save
        
        // Return view instead of redirect to avoid middleware issues
        return view('portal.bypass-success', [
            'user' => $user,
            'sessionId' => session()->getId(),
            'authenticated' => Auth::guard('portal')->check()
        ]);
    }
    
    public function dashboard(Request $request)
    {
        // Special dashboard that doesn't require auth middleware
        $user = Auth::guard('portal')->user();
        
        if (!$user && session('portal_user_id')) {
            // Try to restore user from session
            $user = PortalUser::withoutGlobalScopes()->find(session('portal_user_id'));
            if ($user) {
                Auth::guard('portal')->login($user);
            }
        }
        
        return view('portal.bypass-dashboard', [
            'user' => $user,
            'authenticated' => Auth::guard('portal')->check(),
            'calls' => $user ? \App\Models\Call::where('company_id', $user->company_id)->latest()->limit(10)->get() : collect()
        ]);
    }
}