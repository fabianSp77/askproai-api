<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;
use App\Models\Branch;

class FixPortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        // If already authenticated, continue
        if (Auth::guard('portal')->check()) {
            $this->ensureCompanyContext(Auth::guard('portal')->user());
            return $next($request);
        }
        
        // Check session for portal user
        $userId = session('portal_user_id') ?? session('portal_login');
        
        if ($userId) {
            // Load user without scopes
            $user = PortalUser::withoutGlobalScopes()->find($userId);
            
            if ($user && $user->is_active) {
                // Login the user
                Auth::guard('portal')->login($user);
                
                // Ensure company context
                $this->ensureCompanyContext($user);
                
                return $next($request);
            }
        }
        
        // If API request and not authenticated, return 401
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json([
                'error' => 'Unauthenticated',
                'session_data' => [
                    'portal_user_id' => session('portal_user_id'),
                    'portal_login' => session('portal_login'),
                    'session_id' => session()->getId()
                ]
            ], 401);
        }
        
        return $next($request);
    }
    
    private function ensureCompanyContext($user)
    {
        // Set company context
        app()->instance('current_company_id', $user->company_id);
        
        // Set branch context if not set
        if (!session('current_branch_id')) {
            $branch = Branch::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->first();
                
            if ($branch) {
                session(['current_branch_id' => $branch->id]);
            }
        }
    }
}