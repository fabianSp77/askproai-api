<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

class PortalBypassAuth
{
    /**
     * Handle an incoming request - Bypass normal auth if special cookie is set
     */
    public function handle(Request $request, Closure $next)
    {
        // Check for bypass cookie
        if ($request->cookie('portal_bypass_token')) {
            $token = $request->cookie('portal_bypass_token');
            
            // Simple token validation (in production, use proper JWT or encrypted tokens)
            if (strpos($token, 'bypass_') === 0) {
                $userId = str_replace('bypass_', '', $token);
                
                $user = PortalUser::withoutGlobalScopes()->find($userId);
                if ($user && $user->is_active) {
                    // Set user in auth system without session
                    Auth::guard('portal')->setUser($user);
                    
                    // Set company context
                    app()->instance('current_company_id', $user->company_id);
                    
                    // Add bypass flag to request
                    $request->attributes->set('portal_bypass', true);
                }
            }
        }
        
        return $next($request);
    }
}