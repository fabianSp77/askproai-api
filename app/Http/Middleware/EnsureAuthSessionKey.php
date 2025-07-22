<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAuthSessionKey
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
        // If user is authenticated but session key is missing, fix it
        if (Auth::check()) {
            $user = Auth::user();
            $guard = Auth::guard('web');
            
            // Get the correct session key
            $reflection = new \ReflectionMethod($guard, 'getName');
            $reflection->setAccessible(true);
            $sessionKey = $reflection->invoke($guard);
            
            $session = app('session.store');
            
            // Check if session has the auth key
            if (!$session->has($sessionKey)) {
                // Set it
                $session->put($sessionKey, $user->id);
                $session->put('password_hash_web', $user->password);
                $session->save();
            }
        } else {
            // Check if we have user ID in session but Auth doesn't recognize it
            $session = app('session.store');
            
            // Get the actual session key
            $guard = Auth::guard('web');
            $reflection = new \ReflectionMethod($guard, 'getName');
            $reflection->setAccessible(true);
            $sessionKey = $reflection->invoke($guard);
            
            if ($session->has($sessionKey)) {
                $userId = $session->get($sessionKey);
                $user = \App\Models\User::find($userId);
                
                if ($user) {
                    // Restore the auth
                    Auth::guard('web')->setUser($user);
                }
            }
        }
        
        return $next($request);
    }
}