<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Session\Session;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;

class PortalStartSession extends StartSession
{
    /**
     * Create a new session middleware.
     */
    public function __construct(SessionManager $manager, callable $cacheFactoryResolver = null)
    {
        parent::__construct($manager, $cacheFactoryResolver);
    }

    /**
     * Get the session implementation from the manager.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Session\Store
     */
    public function getSession($request)
    {
        return tap($this->manager->driver(), function ($session) use ($request) {
            // Configure the session specifically for portal
            $session->setName('askproai_portal_session');
        });
    }

    /**
     * Determine if the configuration odds hit the lottery.
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHitsLottery(array $config)
    {
        return random_int(1, $config['lottery'][1]) <= $config['lottery'][0];
    }

    /**
     * Store the current URL for the request if necessary.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Session\Store  $session
     * @return void
     */
    protected function storeCurrentUrl($request, $session)
    {
        if ($request->isMethod('GET') &&
            $request->route() instanceof \Illuminate\Routing\Route &&
            ! $request->ajax() &&
            ! $request->prefetch()) {
            $session->setPreviousUrl($request->fullUrl());
        }
    }
}