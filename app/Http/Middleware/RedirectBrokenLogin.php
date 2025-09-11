<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectBrokenLogin
{
    /**
     * Handle an incoming request.
     * Redirect from broken main login to working backup login
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if we're on the main login page
        if ($request->is('admin/login') && $request->isMethod('get')) {
            // Check if login form is broken (no input fields)
            $response = $next($request);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getContent();
                
                // Check if form inputs are missing
                $hasEmailInput = str_contains($content, 'type="email"');
                $hasPasswordInput = str_contains($content, 'type="password"');
                
                if (!$hasEmailInput || !$hasPasswordInput) {
                    // Redirect to working backup login
                    return redirect('/admin/login-fix');
                }
            }
            
            return $response;
        }
        
        return $next($request);
    }
}