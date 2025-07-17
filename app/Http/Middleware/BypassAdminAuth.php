<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class BypassAdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check if accessing admin area
        if ($request->is('admin/*') || $request->is('admin')) {
            // Check for bypass cookie
            if ($request->cookie('admin_bypass_auth')) {
                $userId = decrypt($request->cookie('admin_bypass_auth'));
                $user = User::find($userId);
                
                if ($user) {
                    Auth::guard('web')->loginUsingId($user->id);
                }
            }
            
            // If not authenticated and on login page, auto-login first admin
            if (!Auth::check() && $request->is('admin/login')) {
                // Find first admin user
                $admin = User::where('email', 'admin@askproai.de')
                    ->orWhere('email', 'fabian@askproai.de')
                    ->first();
                    
                if ($admin) {
                    Auth::guard('web')->loginUsingId($admin->id);
                    return redirect('/admin');
                }
            }
        }
        
        return $next($request);
    }
}