<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Security headers to apply to all responses
     */
    private array $headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        'Cross-Origin-Resource-Policy' => 'same-origin',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Apply security headers
        foreach ($this->headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        // Content Security Policy (CSP) - Strict but allowing Livewire
        $csp = $this->buildCSP($request);
        $response->headers->set('Content-Security-Policy', $csp);

        // Strict Transport Security (only for HTTPS)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    /**
     * Build Content Security Policy
     */
    private function buildCSP(Request $request): string
    {
        $nonce = base64_encode(random_bytes(16));
        session(['csp_nonce' => $nonce]);

        $policies = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net", // Allow Livewire and Alpine
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com", // Allow inline styles for Livewire
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' wss://*.askproai.de", // Allow WebSocket for Livewire
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        // Add report URI if configured
        if ($reportUri = config('app.csp_report_uri')) {
            $policies[] = "report-uri {$reportUri}";
        }

        return implode('; ', $policies);
    }
}