<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession as BaseStartSession;
use Illuminate\Http\Request;

class PortalStartSession extends BaseStartSession
{
    /**
     * Get the session implementation from the manager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    public function getSession(Request $request)
    {
        // Configure portal-specific session settings before starting session
        config([
            'session.cookie' => env('PORTAL_SESSION_COOKIE', 'askproai_portal_session'),
            'session.table' => 'portal_sessions',
            'session.lifetime' => 480,
            'session.domain' => '.askproai.de',
            'session.secure' => true,
            'session.same_site' => 'lax',
        ]);
        
        return parent::getSession($request);
    }
}