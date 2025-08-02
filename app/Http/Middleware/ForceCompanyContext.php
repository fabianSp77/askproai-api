<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForceCompanyContext
{
    /**
     * Force company context for all admin requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always try to set company context for authenticated users
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user && $user->company_id) {
                // Force set company context
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'web_auth');
                
                // Also set in session as backup
                session(['current_company_id' => $user->company_id]);
                
                // Double-check it's set
                if (!app()->has('current_company_id')) {
                    app()->instance('current_company_id', $user->company_id);
                }
                
                \Log::info('ForceCompanyContext: Set context', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'is_admin' => $request->is('admin/*'),
                    'is_livewire' => $request->is('livewire/*'),
                    'context_check' => app()->has('current_company_id')
                ]);
            }
        }
        
        $response = $next($request);
        
        // Double-check after response
        if (Auth::check() && Auth::user()->company_id && !app()->has('current_company_id')) {
            app()->instance('current_company_id', Auth::user()->company_id);
            \Log::warning('ForceCompanyContext: Had to set context AFTER request processing');
        }
        
        return $response;
    }
}