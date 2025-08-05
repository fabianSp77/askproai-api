<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AjaxLoginController extends Controller
{
    /**
     * Handle AJAX login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('business')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'redirect' => route('business.dashboard'),
                'user' => Auth::guard('business')->user()->only(['id', 'name', 'email'])
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Die angegebenen Zugangsdaten sind ungültig.'
        ], 401);
    }

    /**
     * Check authentication status.
     */
    public function check()
    {
        $authenticated = Auth::guard('business')->check();
        
        return response()->json([
            'authenticated' => $authenticated,
            'user' => $authenticated ? Auth::guard('business')->user()->only(['id', 'name', 'email']) : null
        ]);
    }

    /**
     * Handle AJAX logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('business')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'redirect' => route('business.login')
        ]);
    }

    /**
     * Refresh authentication session.
     */
    public function refresh(Request $request)
    {
        if (Auth::guard('business')->check()) {
            $request->session()->regenerate();
            
            return response()->json([
                'success' => true,
                'message' => 'Session refreshed'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Not authenticated'
        ], 401);
    }
}