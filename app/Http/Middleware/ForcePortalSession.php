<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PortalUser;

class ForcePortalSession
{
    public function handle(Request $request, Closure $next)
    {
        // Only for business routes
        if (!$request->is('business/*')) {
            return $next($request);
        }

        // Check if already authenticated
        if (Auth::guard('portal')->check()) {
            return $next($request);
        }

        // Try to restore session from standard session storage
        $guard = Auth::guard('portal');
        $authKey = $guard->getName();
        
        Log::debug('ForcePortalSession attempting restore', [
            'session_id' => session()->getId(),
            'session_keys' => array_keys(session()->all()),
            'expected_key' => $authKey,
            'url' => $request->url(),
        ]);
        
        // Primary method: Check using guard's session key
        if (session()->has($authKey)) {
            $userId = session($authKey);
            $user = PortalUser::find($userId);
            
            if ($user && $user->is_active) {
                // Manually authenticate the user
                $guard->loginUsingId($userId);
                
                // Set company context
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'force_portal_session');
                
                Log::info('ForcePortalSession restored user from session', [
                    'user_id' => $userId,
                    'email' => $user->email,
                    'session_id' => session()->getId(),
                ]);
            }
        }
        // Fallback method: Check portal_user_id
        else if (session()->has('portal_user_id')) {
            $userId = session('portal_user_id');
            $user = PortalUser::find($userId);
            
            if ($user && $user->is_active) {
                // Manually authenticate the user
                $guard->loginUsingId($userId);
                
                // Set company context
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'force_portal_session_fallback');
                
                Log::info('ForcePortalSession restored user from fallback', [
                    'user_id' => $userId,
                    'email' => $user->email,
                    'session_id' => session()->getId(),
                ]);
            }
        }
        
        return $next($request);
    }
}