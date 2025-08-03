<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalAuthFixed
{
    public function handle(Request $request, Closure $next)
    {
        // Get the portal guard
        $guard = auth()->guard('portal');
        
        // Check if user is authenticated
        if (!$guard->check()) {
            // Try to restore from session
            $sessionKey = $guard->getName();
            if (session()->has($sessionKey)) {
                $userId = session($sessionKey);
                try {
                    $user = \App\Models\PortalUser::find($userId);
                    if ($user && $user->is_active) {
                        $guard->login($user);
                        \Log::info('PortalAuthFixed - Restored user from session', [
                            'user_id' => $userId,
                            'email' => $user->email,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('PortalAuthFixed - Failed to restore user', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // If still not authenticated, redirect to login
        if (!$guard->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('business.login');
        }
        
        // Set company context
        $user = $guard->user();
        if ($user && $user->company_id) {
            app()->instance('current_company_id', $user->company_id);
            app()->instance('company_context_source', 'portal_auth');
        }
        
        return $next($request);
    }
}