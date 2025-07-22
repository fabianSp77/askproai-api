<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Skip2FACheck
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('portal');
        
        // Skip 2FA for demo users
        if ($user && in_array($user->email, ['demo2025@askproai.de', 'demo@askproai.de'])) {
            // Always mark 2FA as not required for demo users
            if ($user->two_factor_enforced) {
                $user->two_factor_enforced = false;
                $user->save();
            }
            
            // Skip any 2FA related redirects
            if ($request->is('business/two-factor/*')) {
                return redirect('/business');
            }
            
            return $next($request);
        }
        
        // For non-demo users, just proceed normally
        return $next($request);
    }
}