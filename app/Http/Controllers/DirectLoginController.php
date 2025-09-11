<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DirectLoginController extends Controller
{
    public function showLoginForm()
    {
        // Wenn bereits angemeldet, weiterleiten zum Dashboard
        if (Auth::check()) {
            return redirect('/admin');
        }
        
        return view('auth.filament-login');
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Setze Filament-spezifische Session-Daten
            session(['filament.auth.admin' => true]);
            session(['filament.id' => 'admin']);
            
            return redirect()->intended('/admin');
        }

        return back()->withErrors([
            'email' => 'Die angegebenen Anmeldedaten sind ungültig.',
        ])->onlyInput('email');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/admin/login');
    }
}