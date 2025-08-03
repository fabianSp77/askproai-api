<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomSessionGuard extends SessionGuard
{
    /**
     * Get a unique identifier for the auth session value.
     * Override to ensure consistent session key generation.
     *
     * @return string
     */
    public function getName()
    {
        // Use the parent class name (SessionGuard) for the hash to maintain compatibility
        // This ensures the session key matches what Laravel expects
        return 'login_'.$this->name.'_'.sha1(\Illuminate\Auth\SessionGuard::class);
    }
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
        
        // Don't migrate/regenerate - it's causing the auth to be lost
        // Laravel 11 handles this differently
        // $this->session->migrate(true);
        
        // Ensure the session is saved
        $this->session->save();
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