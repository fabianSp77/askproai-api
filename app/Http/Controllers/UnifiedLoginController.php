<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UnifiedLoginController extends Controller
{
    /**
     * Show the unified login form
     */
    public function showLoginForm()
    {
        // If already authenticated, redirect to appropriate panel
        if (Auth::check()) {
            return redirect($this->getRedirectPath());
        }
        
        return view('auth.unified-login');
    }
    
    /**
     * Handle the login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');
        
        // Find user
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput($request->only('email'));
        }
        
        // Check if user is active
        if ($user->is_active === false) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput($request->only('email'));
        }
        
        // Attempt login
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Record login
            $user->recordLogin($request->ip());
            
            // Check if 2FA is required
            if ($user->requires2FA() && $user->hasEnabledTwoFactorAuthentication()) {
                // TODO: Implement 2FA challenge
                // For now, continue with login
            }
            
            return redirect()->intended($this->getRedirectPath());
        }
        
        return back()->withErrors([
            'password' => 'The provided password is incorrect.',
        ])->withInput($request->only('email'));
    }
    
    /**
     * Get the redirect path based on user role
     */
    protected function getRedirectPath(): string
    {
        $user = Auth::user();
        
        // Super admins go to admin panel
        if ($user->hasAnyRole(['Super Admin', 'super_admin', 'Admin'])) {
            return '/admin';
        }
        
        // Company users go to business panel
        if ($user->hasAnyRole(['company_owner', 'company_admin', 'company_manager', 'company_staff'])) {
            return '/business';
        }
        
        // Default fallback
        return '/admin';
    }
    
    /**
     * Log the user out
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}