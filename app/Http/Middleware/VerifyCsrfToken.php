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
        'admin/*',
        'admin-v2/api/*',
        'admin-v2/api/login',
        'admin-v2/api/logout',
        'admin-v2/api/check',
    ];
}
