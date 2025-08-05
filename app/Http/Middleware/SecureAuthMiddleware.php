<?php

namespace App\Http\Middleware;

use App\Scopes\SecureTenantScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Secure Authentication Middleware
 * 
 * Ensures proper authentication and tenant context for all protected routes:
 * - Validates user authentication
 * - Enforces company context
 * - Checks 2FA requirements
 * - Prevents session hijacking
 * - Logs security events
 */
class SecureAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        // Determine which guard to use
        $guard = $guards[0] ?? 'web';
        
        // Check if user is authenticated
        if (!Auth::guard($guard)->check()) {
            return $this->redirectToLogin($request, $guard);
        }

        $user = Auth::guard($guard)->user();

        // Validate session integrity
        if (!$this->validateSessionIntegrity($request, $user)) {
            Log::warning('Session integrity check failed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            
            return $this->redirectToLogin($request, $guard);
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('Inactive user attempted access', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            Auth::guard($guard)->logout();
            
            return $this->redirectToLogin($request, $guard, 'Your account has been deactivated.');
        }

        // Check if user is locked
        if ($user->locked_until && $user->locked_until->isFuture()) {
            Log::warning('Locked user attempted access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'locked_until' => $user->locked_until
            ]);
            
            Auth::guard($guard)->logout();
            
            return $this->redirectToLogin($request, $guard, 'Your account is temporarily locked.');
        }

        // Enforce company context
        if (!$this->enforceCompanyContext($user, $request)) {
            return $this->redirectToLogin($request, $guard, 'Account configuration error.');
        }

        // Check 2FA requirements for sensitive operations
        if ($this->requires2FAForRequest($request, $user) && !$this->is2FAVerified($request)) {
            return $this->redirectTo2FA($request, $guard);
        }

        // Update last activity
        $this->updateLastActivity($user, $request);

        // Continue with request
        $response = $next($request);

        // Add security headers
        return $this->addSecurityHeaders($response);
    }

    /**
     * Validate session integrity to prevent hijacking
     */
    protected function validateSessionIntegrity(Request $request, $user): bool
    {
        // Check if IP has changed significantly (different subnet)
        $lastIp = $user->last_login_ip;
        $currentIp = $request->ip();
        
        if ($lastIp && $currentIp !== $lastIp) {
            // Allow IP changes within same subnet or local networks
            if (!$this->isIPChangeAllowed($lastIp, $currentIp)) {
                return false;
            }
        }

        // Check session user ID consistency
        if (session('portal_user_id') && session('portal_user_id') != $user->id) {
            return false;
        }

        // Check company ID consistency
        if (session('company_id') && session('company_id') != $user->company_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if IP change is allowed (same subnet or local networks)
     */
    protected function isIPChangeAllowed(string $lastIp, string $currentIp): bool
    {
        // Allow localhost changes
        if (in_array($currentIp, ['127.0.0.1', '::1']) || in_array($lastIp, ['127.0.0.1', '::1'])) {
            return true;
        }

        // Allow private network changes
        if ($this->isPrivateIP($currentIp) && $this->isPrivateIP($lastIp)) {
            return true;
        }

        // Check if IPs are in same /24 subnet for public IPs
        $lastParts = explode('.', $lastIp);
        $currentParts = explode('.', $currentIp);
        
        if (count($lastParts) === 4 && count($currentParts) === 4) {
            // Same /24 subnet
            return $lastParts[0] === $currentParts[0] && 
                   $lastParts[1] === $currentParts[1] && 
                   $lastParts[2] === $currentParts[2];
        }

        return false;
    }

    /**
     * Check if IP is in private range
     */
    protected function isPrivateIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    /**
     * Enforce secure company context
     */
    protected function enforceCompanyContext($user, Request $request): bool
    {
        if (!$user->company_id) {
            Log::critical('User has no company_id in middleware', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->route()?->getName()
            ]);
            return false;
        }

        // Set company context for this request
        SecureTenantScope::setCompanyContext($user->company_id);
        
        // Ensure session has company_id
        if (!session('company_id') || session('company_id') != $user->company_id) {
            session(['company_id' => $user->company_id]);
        }

        return true;
    }

    /**
     * Check if request requires 2FA verification
     */
    protected function requires2FAForRequest(Request $request, $user): bool
    {
        // Skip 2FA check for 2FA setup/verification routes
        $route = $request->route()?->getName() ?? '';
        $sensitiveRoutes = [
            'two-factor',
            'logout'
        ];

        foreach ($sensitiveRoutes as $sensitiveRoute) {
            if (str_contains($route, $sensitiveRoute)) {
                return false;
            }
        }

        // Require 2FA for sensitive operations
        $sensitivePaths = [
            'admin/users',
            'admin/companies',
            'business/billing',
            'business/settings',
            'api/admin'
        ];

        $path = $request->path();
        foreach ($sensitivePaths as $sensitivePath) {
            if (str_contains($path, $sensitivePath)) {
                return $user->requires2FA();
            }
        }

        // Check if 2FA is enforced for user role
        if ($user->hasAnyRole(['Super Admin', 'super_admin', 'company_owner', 'company_admin'])) {
            return $user->requires2FA() && !$this->is2FAVerified($request);
        }

        return false;
    }

    /**
     * Check if 2FA is verified for this session
     */
    protected function is2FAVerified(Request $request): bool
    {
        // Check session for 2FA verification
        $verified = session('2fa_verified_at');
        
        if (!$verified) {
            return false;
        }

        // 2FA verification expires after 4 hours
        $verifiedAt = \Carbon\Carbon::parse($verified);
        return $verifiedAt->isAfter(now()->subHours(4));
    }

    /**
     * Update user's last activity
     */
    protected function updateLastActivity($user, Request $request): void
    {
        // Update every 5 minutes to avoid too many DB writes
        $lastUpdate = session('last_activity_update');
        if (!$lastUpdate || now()->diffInMinutes($lastUpdate) >= 5) {
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip()
            ]);
            
            session(['last_activity_update' => now()]);
        }
    }

    /**
     * Redirect to appropriate login page
     */
    protected function redirectToLogin(Request $request, string $guard, ?string $message = null)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message ?? 'Unauthenticated.',
                'redirect' => $this->getLoginUrl($guard)
            ], 401);
        }

        $loginUrl = $this->getLoginUrl($guard);
        
        if ($message) {
            return redirect($loginUrl)->withErrors(['email' => $message]);
        }
        
        return redirect()->guest($loginUrl);
    }

    /**
     * Redirect to 2FA verification
     */
    protected function redirectTo2FA(Request $request, string $guard)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication required.',
                'redirect' => route('auth.two-factor.challenge')
            ], 403);
        }

        return redirect()->route('auth.two-factor.challenge');
    }

    /**
     * Get login URL for guard
     */
    protected function getLoginUrl(string $guard): string
    {
        return match ($guard) {
            'web' => route('business.login'),
            'customer' => route('customer.login'),
            default => route('login')
        };
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(Response $response): Response
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }

        return $response;
    }
}