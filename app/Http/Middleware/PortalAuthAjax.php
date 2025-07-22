<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Portal\PortalAuthService;

class PortalAuthAjax
{
    protected $authService;

    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request for AJAX/API calls
     */
    public function handle(Request $request, Closure $next)
    {
        // First check standard session authentication
        if ($this->authService->check()) {
            return $next($request);
        }

        // Check for API session token in header
        $token = $request->header('X-Session-Token');
        if ($token) {
            $user = $this->authService->verifySessionToken($token);
            if ($user) {
                // Set user in auth guard for this request
                auth()->guard('portal')->setUser($user);
                return $next($request);
            }
        }

        // Check backup cookie as fallback
        if ($request->hasCookie('portal_session_backup')) {
            try {
                $data = decrypt($request->cookie('portal_session_backup'));
                if (isset($data['user_id']) && $data['authenticated']) {
                    $user = \App\Models\PortalUser::withoutGlobalScopes()->find($data['user_id']);
                    if ($user && $user->is_active) {
                        app()->instance('current_company_id', $user->company_id);
                        auth()->guard('portal')->login($user);
                        return $next($request);
                    }
                }
            } catch (\Exception $e) {
                // Invalid cookie, ignore
            }
        }

        // Not authenticated
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
            'code' => 'UNAUTHENTICATED'
        ], 401);
    }
}