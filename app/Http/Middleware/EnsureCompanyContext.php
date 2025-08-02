<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply in web context for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            
            // Set company context if user has company_id
            if ($user->company_id) {
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'web_auth');
                
                \Log::debug('EnsureCompanyContext: Set company context', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'path' => $request->path()
                ]);
            } elseif ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
                // Super admins might not have a company_id
                // Allow them to see all data by not setting a specific context
                \Log::debug('EnsureCompanyContext: Super admin without company_id', [
                    'user_id' => $user->id,
                    'path' => $request->path()
                ]);
            }
        }
        
        return $next($request);
    }
}