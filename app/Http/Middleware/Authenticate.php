<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Authenticate Middleware
 *
 * Erweitert die Laravel-Standard-Authenticate-Middleware.
 * Bestimmt, wohin nicht-authentifizierte Benutzer weitergeleitet werden.
 */
class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Prüfe ob es sich um eine Filament-Panel-Route handelt
        if ($request->is('admin/*') || $request->is('admin')) {
            return route('filament.admin.auth.login');
        }

        if ($request->is('customer/*') || $request->is('customer')) {
            return route('filament.customer.auth.login');
        }

        // Standard: Admin-Login
        return route('filament.admin.auth.login');
    }
}
