<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PortalCompanyContext
{
    /**
     * Ensure company context is set for portal requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check portal guard
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            
            if ($user && $user->company_id) {
                // Force set company context
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'portal_auth');
                
                // Also set in session as backup
                session(['current_company_id' => $user->company_id]);
                session(['portal_user_id' => $user->id]);
                
                \Log::info('PortalCompanyContext: Set context', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'session_id' => session()->getId()
                ]);
            }
        } elseif (session()->has('current_company_id')) {
            // Restore from session if available
            app()->instance('current_company_id', session('current_company_id'));
            app()->instance('company_context_source', 'session_restore');
            
            \Log::info('PortalCompanyContext: Restored from session', [
                'company_id' => session('current_company_id'),
                'path' => $request->path()
            ]);
        }
        
        $response = $next($request);
        
        // Ensure context persists
        if (Auth::guard('portal')->check()) {
            $user = Auth::guard('portal')->user();
            if ($user && $user->company_id && !app()->has('current_company_id')) {
                app()->instance('current_company_id', $user->company_id);
                \Log::warning('PortalCompanyContext: Had to set context AFTER request processing');
            }
        }
        
        return $response;
    }
}