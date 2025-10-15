<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request for multi-tenant operations.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get tenant from user, header, or subdomain
        $user = $request->user();

        if ($user && $user->company_id) {
            // Set tenant context for the request
            $request->merge(['company_id' => $user->company_id]);

            // Optional: Set tenant context globally for models
            config(['tenant.current_company_id' => $user->company_id]);

            // 🔒 SECURITY FIX (RISK-004): Validate X-Company-ID header override
            // Only super_admin can override company context via header
            if ($request->header('X-Company-ID')) {
                $requestedCompanyId = $request->header('X-Company-ID');

                // Regular users can ONLY access their own company
                if (!$user->hasRole('super_admin')) {
                    if ($requestedCompanyId != $user->company_id) {
                        abort(403, 'Unauthorized company access attempt. Only super admins can access other companies.');
                    }
                }

                // Super admin or same company - allow override
                $request->merge(['company_id' => $requestedCompanyId]);
                config(['tenant.current_company_id' => $requestedCompanyId]);

                // Log security event for audit trail
                logger()->warning('Company context override via X-Company-ID header', [
                    'user_id' => $user->id,
                    'user_company_id' => $user->company_id,
                    'requested_company_id' => $requestedCompanyId,
                    'is_super_admin' => $user->hasRole('super_admin'),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } elseif ($request->header('X-Company-ID')) {
            // No authenticated user but X-Company-ID header present
            // This is a security risk - reject the request
            abort(401, 'Authentication required for company context override');
        }

        return $next($request);
    }
}