<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class FixedLoginController extends Controller
{
    /**
     * Handle login with proper session management
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Store session data before regeneration
        $sessionData = Session::all();
        
        if (Auth::attempt($credentials)) {
            // DO NOT regenerate session immediately
            // Instead, migrate the session data
            $user = Auth::user();
            
            // Manually set the session data
            Session::put('login_web_' . sha1(get_class($user)), $user->id);
            Session::put('password_hash_web', $user->password);
            
            // Restore previous session data
            foreach ($sessionData as $key => $value) {
                if (!in_array($key, ['_token', '_flash', '_previous'])) {
                    Session::put($key, $value);
                }
            }
            
            // Save session without regeneration
            Session::save();
            
            // Now regenerate AFTER saving
            Session::regenerate(true);
            
            return redirect()->intended('/admin');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }
    
    /**
     * Direct login for testing
     */
    public function directLogin()
    {
        $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
        
        if (!$user) {
            return response('User not found', 404);
        }
        
        // Login without session regeneration
        Auth::loginUsingId($user->id, true);
        
        // Manually set session data
        Session::put('login_web_' . sha1(get_class($user)), $user->id);
        Session::put('password_hash_web', $user->password);
        Session::put('manual_login', true);
        
        // Save without regeneration
        Session::save();
        
        return redirect('/admin');
    }
}