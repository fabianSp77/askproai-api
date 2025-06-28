<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\UnifiedAlertingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RecordSecurityEvent
{
    private UnifiedAlertingService $alertingService;

    public function __construct(UnifiedAlertingService $alertingService)
    {
        $this->alertingService = $alertingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Record security events based on response status
        if ($response->getStatusCode() === 401) {
            $this->recordSecurityEvent($request, 'unauthorized_access');
        } elseif ($response->getStatusCode() === 403) {
            $this->recordSecurityEvent($request, 'forbidden_access');
        } elseif ($response->getStatusCode() === 429) {
            $this->recordSecurityEvent($request, 'rate_limit_exceeded');
        }

        // Check for suspicious patterns
        if ($this->isSuspiciousRequest($request)) {
            $this->recordSecurityEvent($request, 'suspicious_activity');
        }

        return $response;
    }

    /**
     * Record a security event.
     */
    private function recordSecurityEvent(Request $request, string $type): void
    {
        $event = [
            'type' => $type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'created_at' => now(),
        ];

        try {
            DB::table('security_logs')->insert($event);

            // For breach attempts, record event for alerting
            if (in_array($type, ['unauthorized_access', 'forbidden_access', 'suspicious_activity'])) {
                $this->alertingService->recordEvent('security_breach_attempt');
            }

            Log::channel('security')->warning('Security event recorded', $event);
        } catch (\Exception $e) {
            Log::error('Failed to record security event', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }

    /**
     * Check if request appears suspicious.
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $suspiciousPatterns = [
            // SQL injection patterns
            '/(\bunion\b.*\bselect\b|\bselect\b.*\bfrom\b.*\bwhere\b)/i',
            '/(\bdrop\b.*\btable\b|\bdelete\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b.*\bvalues\b)/i',
            '/(\bupdate\b.*\bset\b.*\bwhere\b)/i',

            // XSS patterns
            '/<script[^>]*>.*?<\/script>/si',
            '/javascript:\s*[^"\']+/i',
            '/on\w+\s*=\s*["\'][^"\']+["\']/i',

            // Path traversal
            '/\.\.\/|\.\.\\\\/',

            // Command injection
            '/[;&|]\s*(cat|ls|pwd|whoami|id|uname)/i',

            // Common vulnerability scanners
            '/nikto|sqlmap|nmap|burp|zap|acunetix/i',
        ];

        $checkString = $request->fullUrl() . ' ' .
                      json_encode($request->all()) . ' ' .
                      $request->userAgent();

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $checkString)) {
                return true;
            }
        }

        return false;
    }
}
