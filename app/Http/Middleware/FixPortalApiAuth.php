<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FixPortalApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        // For API calls, ensure we have the right session configuration
        if ($request->is('business/api/*') || $request->is('business-api/*')) {
            // Log session state before restoration
            Log::debug('FixPortalApiAuth - Session state before restoration', [
                'session_id' => session()->getId(),
                'session_name' => session()->getName(),
                'has_session' => session()->isStarted(),
                'all_session_data' => session()->all(),
                'url' => $request->url(),
            ]);
            
            // If not authenticated but session has user ID, restore auth
            $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
            
            if (!auth()->guard('portal')->check()) {
                if (session()->has($sessionKey)) {
                    $userId = session($sessionKey);
                    $user = \App\Models\PortalUser::find($userId);
                    
                    if ($user && $user->is_active) {
                        auth()->guard('portal')->loginUsingId($userId);
                        
                        // Set company context
                        app()->instance('current_company_id', $user->company_id);
                        app()->instance('company_context_source', 'portal_auth');
                        
                        Log::info('FixPortalApiAuth - Successfully restored auth from session', [
                            'user_id' => $userId,
                            'email' => $user->email,
                            'company_id' => $user->company_id,
                            'session_id' => session()->getId(),
                        ]);
                    } else {
                        Log::warning('FixPortalApiAuth - User not found or inactive', [
                            'user_id' => $userId,
                            'session_id' => session()->getId(),
                        ]);
                    }
                } else {
                    Log::debug('FixPortalApiAuth - No session key found', [
                        'session_key' => $sessionKey,
                        'session_id' => session()->getId(),
                        'session_data' => session()->all(),
                    ]);
                }
            } else {
                Log::debug('FixPortalApiAuth - Already authenticated', [
                    'user_id' => auth()->guard('portal')->id(),
                    'session_id' => session()->getId(),
                ]);
            }
        }
        
        return $next($request);
    }
}