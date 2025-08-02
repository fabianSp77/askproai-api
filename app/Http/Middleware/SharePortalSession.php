<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Share Portal Session between Web and API.
 *
 * This middleware ensures that the portal session is available
 * for API requests by manually restoring the authenticated user
 * from the session data.
 */
class SharePortalSession
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If already authenticated, continue
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            if ($user && $user->company_id) {
                app()->instance('current_company_id', $user->company_id);
            }

            return $next($request);
        }

        // For portal routes, try to restore auth from session
        if ($request->is('business/*') || $request->is('business/api/*')) {
            $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
            
            if (session()->has($sessionKey)) {
                $userId = session($sessionKey);
                
                \Log::debug('SharePortalSession: Attempting to restore user from session', [
                    'user_id' => $userId,
                    'session_id' => session()->getId(),
                    'url' => $request->url(),
                ]);
                
                try {
                    $user = \App\Models\PortalUser::find($userId);
                    if ($user && $user->is_active) {
                        Auth::guard('portal')->loginUsingId($userId, false);
                        
                        // Set company context
                        app()->instance('current_company_id', $user->company_id);
                        app()->instance('company_context_source', 'portal_session_restore');
                        
                        \Log::info('SharePortalSession: Successfully restored user from session', [
                            'user_id' => $userId,
                            'email' => $user->email,
                            'company_id' => $user->company_id,
                        ]);
                        
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    \Log::error('SharePortalSession: Failed to restore user', [
                        'error' => $e->getMessage(),
                        'user_id' => $userId,
                    ]);
                }
            } else {
                \Log::debug('SharePortalSession: No session key found', [
                    'session_id' => session()->getId(),
                    'session_keys' => array_keys(session()->all()),
                    'url' => $request->url(),
                ]);
            }
        }

        // Also check for admin viewing
        if (! Auth::guard('portal')->check() && session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            if ($companyId) {
                app()->instance('current_company_id', $companyId);
            }
        }

        return $next($request);
    }
}
