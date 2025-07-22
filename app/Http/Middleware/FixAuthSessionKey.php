<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FixAuthSessionKey
{
    /**
     * Fix session key mismatch by ensuring auth data is available under all possible keys
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only process if we have a session
        if (!$request->hasSession()) {
            return $response;
        }
        
        $session = $request->session();
        $sessionData = $session->all();
        
        // Find any auth data in session
        $authUserId = null;
        $authKey = null;
        
        foreach ($sessionData as $key => $value) {
            if (strpos($key, 'login_web_') === 0 && is_numeric($value)) {
                $authUserId = $value;
                $authKey = $key;
                break;
            }
        }
        
        // If we found auth data but Auth::check() is false, we have a key mismatch
        if ($authUserId && !Auth::check()) {
            // Get the expected key for the current guard
            $guardName = Auth::getDefaultDriver();
            $guard = Auth::guard($guardName);
            $expectedKey = 'login_' . $guardName . '_' . sha1(get_class($guard));
            
            // If the key doesn't match, copy the auth data to the expected key
            if ($authKey !== $expectedKey) {
                $session->put($expectedKey, $authUserId);
                
                // Also ensure password hash exists if needed
                $passwordHashKey = 'password_hash_' . $guardName;
                if (!$session->has($passwordHashKey)) {
                    $user = \App\Models\User::find($authUserId);
                    if ($user) {
                        $session->put($passwordHashKey, $user->getAuthPassword());
                    }
                }
                
                // Force save
                $session->save();
                
                // Try to restore auth state
                if (!Auth::check()) {
                    $user = \App\Models\User::find($authUserId);
                    if ($user) {
                        Auth::login($user);
                    }
                }
            }
        }
        
        return $response;
    }
}