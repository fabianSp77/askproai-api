<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Only exclude specific API endpoints that need to be accessed externally
        'api/retell/webhook',
        'api/calcom/webhook',
        'api/calcom/direct-webhook',
        // API Login endpoints
        'api/login',
        'api/logout',
        'api/user',
        'api/user/tokens',
        // Debug endpoints temporarily
        'debug/*',
    ];
}
