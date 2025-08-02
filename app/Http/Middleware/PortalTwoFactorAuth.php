<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalTwoFactorAuth
{
    /**
     * Handle an incoming request for portal 2FA.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('portal');

        // If no user, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Skip 2FA for demo users
        if (in_array($user->email, ['demo@askproai.de', 'demo@example.com', 'demo2025@askproai.de'])) {
            return $next($request);
        }

        // Skip 2FA check for 2FA-related routes
        if ($request->routeIs('business.two-factor.*') || 
            $request->routeIs('business.2fa.*') ||
            $request->routeIs('business.logout')) {
            return $next($request);
        }

        // Check if user has 2FA enabled but not verified in this session
        if ($user->two_factor_secret && !session('portal_2fa_verified')) {
            return redirect()->route('business.two-factor.challenge');
        }

        // Check if user needs to setup 2FA (if required by company policy)
        if (method_exists($user, 'requires2FA') && $user->requires2FA() && !$user->two_factor_secret) {
            return redirect()->route('business.two-factor.setup');
        }

        return $next($request);
    }
}