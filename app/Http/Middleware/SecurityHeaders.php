<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers to HTTP responses
        if (!$response instanceof \Illuminate\Http\Response && 
            !$response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        $headers = $this->getSecurityHeaders($request);

        foreach ($headers as $key => $value) {
            if ($value !== null) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }

    /**
     * Get security headers based on configuration.
     */
    private function getSecurityHeaders(Request $request): array
    {
        $headers = [];

        // Content Security Policy
        if (config('security.headers.csp_enabled', true)) {
            $cspDirectives = config('security.headers.csp_directives', []);
            $cspValue = $this->buildCspHeader($cspDirectives);
            if ($cspValue) {
                $headers['Content-Security-Policy'] = $cspValue;
            }
        }

        // HTTP Strict Transport Security
        if (config('security.headers.hsts_enabled', true) && $request->isSecure()) {
            $hstsValue = 'max-age=' . config('security.headers.hsts_max_age', 31536000);
            if (config('security.headers.hsts_include_subdomains', true)) {
                $hstsValue .= '; includeSubDomains';
            }
            $headers['Strict-Transport-Security'] = $hstsValue;
        }

        // X-Frame-Options
        $xFrameOptions = config('security.headers.x_frame_options', 'DENY');
        if ($xFrameOptions) {
            $headers['X-Frame-Options'] = $xFrameOptions;
        }

        // X-Content-Type-Options
        $xContentTypeOptions = config('security.headers.x_content_type_options', 'nosniff');
        if ($xContentTypeOptions) {
            $headers['X-Content-Type-Options'] = $xContentTypeOptions;
        }

        // X-XSS-Protection
        $xXssProtection = config('security.headers.x_xss_protection', '1; mode=block');
        if ($xXssProtection) {
            $headers['X-XSS-Protection'] = $xXssProtection;
        }

        // Referrer Policy
        $referrerPolicy = config('security.headers.referrer_policy', 'strict-origin-when-cross-origin');
        if ($referrerPolicy) {
            $headers['Referrer-Policy'] = $referrerPolicy;
        }

        // Permissions Policy
        $permissionsPolicy = config('security.headers.permissions_policy');
        if ($permissionsPolicy) {
            $headers['Permissions-Policy'] = $permissionsPolicy;
        }

        // Remove server information
        $headers['Server'] = 'AskProAI';

        return $headers;
    }

    /**
     * Build Content Security Policy header value from directives array.
     */
    private function buildCspHeader(array $directives): ?string
    {
        if (empty($directives)) {
            return null;
        }

        $cspParts = [];
        foreach ($directives as $directive => $sources) {
            if (is_array($sources)) {
                $sources = implode(' ', $sources);
            }
            $cspParts[] = $directive . ' ' . $sources;
        }

        return implode('; ', $cspParts);
    }
}