<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class PortalCsrfToken extends Middleware
{
    /**
     * Get the CSRF token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        // First try to get from standard locations
        $token = parent::getTokenFromRequest($request);
        
        if (!$token) {
            // Try to get from portal-specific cookie
            $token = $request->cookie('XSRF-TOKEN-PORTAL');
        }
        
        return $token;
    }
    
    /**
     * Add the CSRF token to the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');
        
        $response->headers->setCookie(
            $this->newCookie($request, $config)
        );
        
        return $response;
    }
}