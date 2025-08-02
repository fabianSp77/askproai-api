<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;
use Illuminate\Http\Request;

class PortalStartSession extends StartSession
{
    /**
     * Get the session implementation from the manager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Session\SessionInterface
     */
    public function getSession(Request $request)
    {
        // Use portal-specific session configuration
        if ($request->is('business/*') || $request->is('business-api/*')) {
            return $this->manager->driver();
        }
        
        return parent::getSession($request);
    }
    
    /**
     * Start the session for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    protected function startSession(Request $request)
    {
        $session = $this->getSession($request);
        
        // For portal routes, check for portal session cookie
        if ($request->is('business/*') || $request->is('business-api/*')) {
            $sessionId = $request->cookies->get('askproai_portal_session');
            
            if ($sessionId) {
                $session->setId($sessionId);
                \Log::debug('PortalStartSession: Using existing portal session', [
                    'session_id' => $sessionId,
                    'url' => $request->url(),
                ]);
            }
        }
        
        return tap($session, function ($session) use ($request) {
            $session->setRequestOnHandler($request);
            $session->start();
        });
    }
}