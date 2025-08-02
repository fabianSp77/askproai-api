<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyContextEarly
{
    /**
     * Ensure company context is set as early as possible in the request lifecycle
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only for admin routes
        if ($request->is('admin/*') || $request->is('livewire/*')) {
            // Get authenticated user
            $user = auth()->user();
            
            if ($user && $user->company_id) {
                // Set company context IMMEDIATELY
                app()->instance('current_company_id', $user->company_id);
                app()->instance('company_context_source', 'early_middleware');
                
                // Also set in config for widgets that might check config
                config(['app.current_company_id' => $user->company_id]);
                
                // Log for debugging
                \Log::debug('EnsureCompanyContextEarly: Set context', [
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'path' => $request->path(),
                    'is_livewire' => $request->is('livewire/*'),
                ]);
            }
        }
        
        return $next($request);
    }
}