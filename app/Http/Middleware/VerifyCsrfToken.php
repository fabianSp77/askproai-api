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
        'webhooks/*',
        'webhooks/retell',  // 🔥 FIX: Explicit exception for Retell webhook
        'webhooks/calcom',
        'api/webhooks/*',
        'api/calcom/webhook',
    ];
}
