<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableCsrfForLivewire
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Disable CSRF check for Livewire routes
        if ($request->is('livewire/*')) {
            // Skip CSRF verification by removing the token requirement
            $request->session()->put('_token', $request->input('_token', ''));
        }

        return $next($request);
    }
}