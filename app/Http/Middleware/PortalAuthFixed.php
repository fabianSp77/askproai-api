<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class PortalAuthFixed
{
    public function handle(Request $request, Closure $next)
    {
        // For business routes, ensure we're using portal session
        if ($request->is('business/*')) {
            // Get the portal session ID from cookie
            $portalSessionId = $request->cookie('askproai_portal_session');
            
            if ($portalSessionId && !Auth::guard('portal')->check()) {
                // Try to read session file directly
                $sessionFile = storage_path('framework/sessions/portal/' . $portalSessionId);
                
                if (file_exists($sessionFile)) {
                    try {
                        $sessionData = unserialize(file_get_contents($sessionFile));
                        
                        // Look for login key
                        $loginKey = 'login_portal_' . sha1(PortalUser::class);
                        
                        if (isset($sessionData[$loginKey])) {
                            $userId = $sessionData[$loginKey];
                            $user = PortalUser::find($userId);
                            
                            if ($user && $user->is_active) {
                                // Manually login the user
                                Auth::guard('portal')->loginUsingId($userId);
                                
                                // Set company context
                                app()->instance('current_company_id', $user->company_id);
                                
                                \Log::info('Portal user restored from session file', [
                                    'user_id' => $userId,
                                    'email' => $user->email,
                                    'session_id' => $portalSessionId,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to read portal session file', [
                            'error' => $e->getMessage(),
                            'session_id' => $portalSessionId,
                        ]);
                    }
                }
            }
        }
        
        // Check if user is authenticated after trying to restore from session
        if (Auth::guard('portal')->check()) {
            // Set company context for authenticated user
            $user = Auth::guard('portal')->user();
            if ($user && $user->company_id) {
                app()->instance('current_company_id', $user->company_id);
            }
            return $next($request);
        }
        
        // Log authentication failure
        \Log::warning('Portal authentication failed', [
            'url' => $request->url(),
            'session_cookie' => $request->cookie('askproai_portal_session'),
            'is_api' => $request->is('business/api/*'),
            'expects_json' => $request->expectsJson(),
        ]);
        
        // For API calls, return JSON error
        if ($request->is('business/api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'session_debug' => config('app.debug') ? [
                    'portal_session_cookie' => $request->cookie('askproai_portal_session'),
                    'has_portal_session' => !empty($request->cookie('askproai_portal_session')),
                ] : null,
            ], 401);
        }
        
        // Don't redirect if it's the login page to avoid loops
        if ($request->is('business/login') || $request->is('business/login/*')) {
            return $next($request);
        }
        
        // Redirect to login
        return redirect()->route('business.login');
    }
}