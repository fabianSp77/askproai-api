<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show login form.
     */
    public function showLoginForm()
    {
        if (Auth::guard('portal')->check()) {
            return redirect()->route('business.dashboard');
        }

        return view('portal.auth.login');
    }

    /**
     * Handle login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find user - must bypass CompanyScope since we're not authenticated yet
        $user = PortalUser::withoutGlobalScopes()->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            \Log::warning('Portal login failed', [
                'email' => $request->email,
                'user_found' => $user ? 'yes' : 'no',
                'password_valid' => $user ? (Hash::check($request->password, $user->password) ? 'yes' : 'no') : 'n/a',
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Die angegebenen Zugangsdaten sind ungültig.']);
        }

        // Check if active
        if (! $user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Ihr Konto wurde deaktiviert. Bitte kontaktieren Sie Ihren Administrator.']);
        }

        // Check if 2FA is required
        if ($user->requires2FA() && ! $user->two_factor_confirmed_at) {
            // Skip 2FA for demo users
            if ($user->email !== 'demo@example.com' && $user->email !== 'demo@askproai.de') {
                // Store user ID in session for 2FA setup
                session(['portal_2fa_user' => $user->id]);

                return redirect()->route('business.two-factor.setup');
            }
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

        // Laravel handles session management automatically
        // No need for manual session manipulation

        // Redirect to dashboard route which will load React SPA
        return redirect()->route('business.dashboard');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('portal')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('business.login');
    }
}
