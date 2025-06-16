<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixLivewireIssues
{
    public function handle(Request $request, Closure $next)
    {
        // Force HTTPS for Livewire URLs in production
        if ($request->isSecure() || config('app.env') === 'production') {
            $request->server->set('HTTPS', 'on');
            url()->forceScheme('https');
        }
        
        // Fix Livewire update URL issue
        if ($request->is('livewire/update') && $request->isMethod('GET')) {
            return response()->json([
                'error' => 'Method not allowed. Livewire update endpoint requires POST method.',
                'hint' => 'This might be a JavaScript issue. Clear your browser cache.'
            ], 405);
        }
        
        // Ensure session is started
        if (!$request->hasSession()) {
            $request->setLaravelSession(app('session.store'));
        }
        
        return $next($request);
    }
}