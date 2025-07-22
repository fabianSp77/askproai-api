<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventSessionMigration
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
        // Override the session migrate method to prevent data loss
        if (app()->bound('session')) {
            $session = app('session');
            
            // Store the original migrate method
            $originalMigrate = \Closure::bind(function() {
                return $this->migrate;
            }, $session, get_class($session));
            
            // Override migrate to not destroy data
            $session->macro('migrate', function($destroy = false) use ($session) {
                // If destroy is true, we'll prevent it and just regenerate the ID
                if ($destroy) {
                    // Get current data
                    $data = $session->all();
                    
                    // Regenerate ID without destroying data
                    session_regenerate_id(false);
                    
                    // Put all data back
                    foreach ($data as $key => $value) {
                        $session->put($key, $value);
                    }
                    
                    return true;
                }
                
                // Otherwise, normal migrate
                return session_regenerate_id($destroy);
            });
        }
        
        return $next($request);
    }
}