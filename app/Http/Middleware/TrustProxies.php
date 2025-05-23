<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /** vertraue allen Proxy-IPs (oder trage dein Netz ein) */
    protected $proxies = '*';

    /** alle X-Forwarded-Header auswerten */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
