<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Share Portal Session between Web and API.
 *
 * This middleware ensures that the portal session is available
 * for API requests by manually restoring the authenticated user
 * from the session data.
 */
class SharePortalSession
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If already authenticated, continue
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            if ($user && $user->company_id) {
                app()->instance('current_company_id', $user->company_id);
            }

            return $next($request);
        }

        // Do not auto-restore users - let Laravel handle session authentication normally
        // The portal guard's session driver will handle this automatically

        // Just ensure company context is set if user is authenticated
        $user = Auth::guard('portal')->user();
        if ($user && $user->company_id) {
            app()->instance('current_company_id', $user->company_id);
        }

        // Also check for admin viewing
        if (! Auth::guard('portal')->check() && session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
            if ($companyId) {
                app()->instance('current_company_id', $companyId);
            }
        }

        return $next($request);
    }
}
