<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleLoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Log successful login
            \Log::info('Successful login', [
                'user' => Auth::user()->email,
                'session_id' => session()->getId(),
                'redirect_to' => '/admin'
            ]);
            
            return redirect('/admin');
        }
        
        \Log::warning('Failed login attempt', [
            'email' => $request->email
        ]);
        
        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }
}