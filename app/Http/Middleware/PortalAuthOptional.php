<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAuthOptional
{
    /**
     * Handle an incoming request - but make auth optional for demo
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to authenticate
        $portalUser = Auth::guard('portal')->user();
        
        if (!$portalUser) {
            // For demo: Try to load user 22 (fabianspitzer@icloud.com)
            $demoUser = \App\Models\PortalUser::find(22);
            if ($demoUser && $demoUser->email === 'fabianspitzer@icloud.com') {
                Auth::guard('portal')->login($demoUser);
                $portalUser = $demoUser;
            }
        }
        
        // Set company context if we have a user
        if ($portalUser && $portalUser->company_id) {
            app()->instance('current_company_id', $portalUser->company_id);
        }
        
        return $next($request);
    }
}