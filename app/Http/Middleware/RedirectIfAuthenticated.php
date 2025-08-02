<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Customer guard removed - customer portal is disabled (Issue #464)
                
                // Handle portal guard
                if ($guard === 'portal') {
                    return redirect()->route('business.dashboard');
                }
                
                // For web guard (admin), only redirect if we're not already in admin
                if (!$request->is('admin/*')) {
                    return redirect('/admin');
                }
            }
        }

        return $next($request);
    }
}