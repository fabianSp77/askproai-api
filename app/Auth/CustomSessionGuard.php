<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomSessionGuard extends SessionGuard
{
    /**
     * Log a user into the application.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        // Call parent login first
        parent::login($user, $remember);
        
        // Ensure session is saved immediately
        $this->ensureSessionIsPersisted();
    }
    
    /**
     * Update the session with the given ID.
     * Override to prevent session migration that destroys data.
     *
     * @param  string  $id
     * @return void
     */
    protected function updateSession($id)
    {
        // SECURITY FIX: Store the auth identifier
        $this->session->put($this->getName(), $id);
        
        // Always regenerate session ID on login to prevent session fixation
        // This is critical for security - it invalidates any pre-existing session ID
        // Use false to preserve session data including CSRF token
        $this->session->regenerate(false);
    }
    
    /**
     * Ensure the session data is persisted.
     *
     * @return void
     */
    protected function ensureSessionIsPersisted()
    {
        if ($this->user) {
            // Make sure auth key is set
            $this->session->put($this->getName(), $this->user->getAuthIdentifier());
            
            // Password hash storage removed for security
            
            // Force save
            $this->session->save();
        }
    }
}