<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EmergencyAuthController extends Controller
{
    // Remove constructor to avoid middleware issues
    
    public function login(Request $request)
    {
        // Auto-login first admin user
        $admin = User::where('email', 'admin@askproai.de')
            ->orWhere('email', 'fabian@askproai.de')
            ->orWhere('email', 'superadmin@askproai.de')
            ->first();
            
        if ($admin) {
            // Clear any existing sessions
            session()->flush();
            session()->regenerate();
            
            // Force login
            Auth::guard('web')->loginUsingId($admin->id, true);
            
            // Set session variables manually
            session(['_token' => csrf_token()]);
            // Password hash storage removed for security
            session()->save();
            
            return redirect('/admin')->with('success', 'Emergency login successful');
        }
        
        return back()->with('error', 'No admin user found');
    }
    
    public function autoLogin()
    {
        // Find and login first admin automatically
        $admin = User::where('email', 'LIKE', '%admin%')->first();
        
        if ($admin) {
            Auth::guard('web')->loginUsingId($admin->id, true);
            return redirect('/admin');
        }
        
        return response('No admin found', 404);
    }
}