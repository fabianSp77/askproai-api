<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // Allow admin viewing without checking permissions
        if (session('is_admin_viewing') && session('admin_impersonation')) {
            return $next($request);
        }
        
        $user = Auth::guard('portal')->user();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('business.login');
        }
        
        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            
            return abort(403, 'Sie haben keine Berechtigung für diese Aktion.');
        }
        
        return $next($request);
    }
}