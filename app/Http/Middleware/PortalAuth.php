<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Session configuration is already handled by ConfigurePortalSession middleware
        // which runs BEFORE StartSession. Don't duplicate that logic here.
        
        // Get session key
        $sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
        
        // Debug logging
        \Log::debug('PortalAuth middleware START', [
            'url' => $request->url(),
            'session_id' => session()->getId(),
            'cookie_session' => $request->cookie('askproai_portal_session'),
            'portal_auth_check' => auth()->guard('portal')->check(),
            'session_has_key' => session()->has($sessionKey),
            'session_user_id' => session($sessionKey),
            'all_cookies' => array_keys($request->cookies->all()),
        ]);

        // Try to restore auth from session if not authenticated
        if (!auth()->guard('portal')->check() && session()->has($sessionKey)) {
            $userId = session($sessionKey);
            try {
                $user = \App\Models\PortalUser::find($userId);
                if ($user && $user->is_active) {
                    auth()->guard('portal')->loginUsingId($userId, false); // Don't use remember
                    \Log::info('PortalAuth - Restored user from session', [
                        'user_id' => $userId,
                        'email' => $user->email,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('PortalAuth - Failed to restore user', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);
            }
        }

        // Check if user is authenticated
        if (auth()->guard('portal')->check()) {
            // Set company context for authenticated user
            $user = auth()->guard('portal')->user();
            if ($user && $user->company_id) {
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'portal_auth');
            }
            \Log::debug('PortalAuth - User authenticated', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            return $next($request);
        }

        // For API calls, return JSON error
        if ($request->is('business/api/*') || $request->is('api/business/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'debug' => config('app.debug') ? [
                    'session_id' => session()->getId(),
                    'has_session' => $request->hasSession(),
                    'session_keys' => array_keys(session()->all()),
                ] : null,
            ], 401);
        }

        // REMOVED: Dangerous auto-login for demo user

        // Only redirect if not expecting JSON
        if (! $request->expectsJson()) {
            \Log::debug('PortalAuth redirecting to login', [
                'from' => $request->url()
            ]);
            return redirect('/business/login');
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
