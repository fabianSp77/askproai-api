<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            abort(401, 'Unauthorized');
        }

        $user = auth()->user();

        // Super admin can do everything
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check for specific permission
        if (!$user->hasPermissionTo($permission)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}