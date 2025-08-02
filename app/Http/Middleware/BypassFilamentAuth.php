<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BypassFilamentAuth
{
    /**
     * Force authentication for Filament admin panel.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if we're in the admin panel
        if ($request->is('admin*')) {
            // Force login demo user if not authenticated
            if (! Auth::check()) {
                $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
                if ($user) {
                    Auth::login($user);
                }
            }
        }

        return $next($request);
    }
}
