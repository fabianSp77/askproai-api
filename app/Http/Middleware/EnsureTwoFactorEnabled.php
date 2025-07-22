<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('portal');

        // Skip 2FA for demo users
        if ($user && in_array($user->email, ['demo2025@askproai.de', 'demo@askproai.de'])) {
            return $next($request);
        }

        // If no user, proceed
        if (! $user) {
            return $next($request);
        }

        // Check if user needs to setup 2FA
        if ($user->needsTwoFactorSetup && method_exists($user, 'needsTwoFactorSetup') && $user->needsTwoFactorSetup()) {
            // Allow access to 2FA setup page and logout
            if ($request->routeIs('filament.admin.pages.two-factor-authentication') ||
                $request->routeIs('filament.admin.auth.logout') ||
                $request->path() === 'livewire/update') {
                return $next($request);
            }

            // Redirect to 2FA setup page
            return redirect()->route('filament.admin.pages.two-factor-authentication')
                ->with('warning', 'You must enable two-factor authentication to continue.');
        }

        return $next($request);
    }
}
