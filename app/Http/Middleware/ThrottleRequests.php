<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Illuminate\Http\Request;

class ThrottleRequests extends BaseThrottleRequests
{
    /**
     * Resolve the number of attempts if the user is authenticated or not.
     */
    protected function resolveMaxAttempts($request, $maxAttempts)
    {
        // Different limits for authenticated vs unauthenticated users
        if ($request->user()) {
            return $maxAttempts * 2; // Authenticated users get double the limit
        }

        return $maxAttempts;
    }

    /**
     * Resolve request signature based on route and user
     */
    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        // For API routes, use API key if present
        if ($request->hasHeader('X-API-Key')) {
            return sha1($request->header('X-API-Key'));
        }

        // Fallback to IP
        return sha1($request->ip());
    }
}