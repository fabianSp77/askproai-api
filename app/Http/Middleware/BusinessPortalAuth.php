<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BusinessPortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Get the business guard
        $guard = auth()->guard('business');
        
        // Check if user is authenticated
        if (!$guard->check()) {
            // Try to restore from session
            $sessionKey = $guard->getName();
            if (session()->has($sessionKey)) {
                $userId = session($sessionKey);
                try {
                    $user = \App\Models\User::find($userId);
                    if ($user && $user->hasRole('business')) {
                        $guard->login($user);
                        \Log::info('BusinessPortalAuth - Restored user from session', [
                            'user_id' => $userId,
                            'email' => $user->email,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('BusinessPortalAuth - Failed to restore user', [
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
        if ($user) {
            $company = $user->companies()->first();
            if ($company) {
                app()->instance('current_company_id', $company->id);
                app()->instance('company_context_source', 'business_auth');
            }
        }
        
        return $next($request);
    }
}