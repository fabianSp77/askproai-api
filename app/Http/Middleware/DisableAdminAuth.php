<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DisableAdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Automatisch als Admin einloggen wenn /admin aufgerufen wird
        if ($request->is('admin/*') || $request->is('admin')) {
            if (!Auth::check()) {
                // Ersten Admin-User finden
                $admin = User::where('email', 'admin@askproai.de')
                    ->orWhere('email', 'fabian@askproai.de')
                    ->first();
                    
                if ($admin) {
                    Auth::guard('web')->loginUsingId($admin->id, true);
                    session()->regenerate();
                }
            }
        }
        
        return $next($request);
    }
}