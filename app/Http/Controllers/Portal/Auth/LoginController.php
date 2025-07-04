<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        if (Auth::guard('portal')->check()) {
            return redirect()->route('business.dashboard');
        }
        
        return view('portal.auth.login');
    }
    
    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        // Find user
        $user = PortalUser::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Die angegebenen Zugangsdaten sind ungültig.'],
            ]);
        }
        
        // Check if active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Ihr Konto wurde deaktiviert. Bitte kontaktieren Sie Ihren Administrator.'],
            ]);
        }
        
        // Check if 2FA is required
        if ($user->requires2FA() && !$user->two_factor_confirmed_at) {
            // Store user ID in session for 2FA setup
            session(['portal_2fa_user' => $user->id]);
            return redirect()->route('business.two-factor.setup');
        }
        
        // Check if 2FA is enabled
        if ($user->two_factor_secret) {
            // Store user ID in session for 2FA challenge
            session(['portal_2fa_user' => $user->id]);
            return redirect()->route('business.two-factor.challenge');
        }
        
        // Login user
        Auth::guard('portal')->login($user, $request->boolean('remember'));
        
        // Record login
        $user->recordLogin($request->ip());
        
        // Redirect based on role
        if ($user->canViewBilling()) {
            return redirect()->intended(route('business.billing.index'));
        }
        
        return redirect()->intended(route('business.dashboard'));
    }
    
    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::guard('portal')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('business.login');
    }
}