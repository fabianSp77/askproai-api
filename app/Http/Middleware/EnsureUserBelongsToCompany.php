<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(401, 'Unauthorized');
        }

        $user = auth()->user();

        // Super admin and admin can access everything
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $next($request);
        }

        // Check if accessing company-specific resource
        if ($request->route('company')) {
            $companyId = $request->route('company')->id ?? $request->route('company');

            if ($user->company_id != $companyId) {
                abort(403, 'You cannot access resources from another company.');
            }
        }

        // Check for company_id in request data
        if ($request->has('company_id') && $request->company_id != $user->company_id) {
            abort(403, 'You cannot create or modify resources for another company.');
        }

        return $next($request);
    }
}