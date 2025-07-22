<?php

namespace App\Services\Portal;

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PortalAuthService
{
    /**
     * Attempt to authenticate a user.
     */
    public function authenticate(string $email, string $password, bool $remember = false): array
    {
        try {
            // Find user without global scopes to avoid tenant issues
            $user = PortalUser::withoutGlobalScopes()
                ->where('email', $email)
                ->first();

            if (! $user) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'code' => 'INVALID_CREDENTIALS',
                ];
            }

            // Verify password
            if (! Hash::check($password, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'code' => 'INVALID_CREDENTIALS',
                ];
            }

            // Check if user is active
            if (! $user->is_active) {
                return [
                    'success' => false,
                    'message' => 'Account is inactive',
                    'code' => 'ACCOUNT_INACTIVE',
                ];
            }

            // Set company context before login
            app()->instance('current_company_id', $user->company_id);

            // Login user
            Auth::guard('portal')->login($user, $remember);

            // Store session data
            $this->storeSessionData($user);

            // Generate session token for API authentication
            $sessionToken = $this->generateSessionToken($user);

            return [
                'success' => true,
                'user' => $this->getUserData($user),
                'session_token' => $sessionToken,
                'redirect' => '/business/dashboard',
            ];
        } catch (\Exception $e) {
            \Log::error('Portal authentication failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Authentication failed',
                'code' => 'AUTH_ERROR',
            ];
        }
    }

    /**
     * Logout the current user.
     */
    public function logout(): void
    {
        $user = Auth::guard('portal')->user();

        if ($user) {
            // Clear session token
            $this->clearSessionToken($user);

            // Logout
            Auth::guard('portal')->logout();

            // Clear all session data
            Session::flush();
            Session::regenerate();

            // Clear cookies
            Cookie::queue(Cookie::forget('portal_session_backup'));
            Cookie::queue(Cookie::forget('portal_remember'));
        }
    }

    /**
     * Check if user is authenticated.
     */
    public function check(): bool
    {
        return Auth::guard('portal')->check();
    }

    /**
     * Get current authenticated user.
     */
    public function user(): ?PortalUser
    {
        return Auth::guard('portal')->user();
    }

    /**
     * Verify session token for API requests.
     */
    public function verifySessionToken(string $token): ?PortalUser
    {
        // Check cache for token
        $userId = Cache::get("portal_session_token:{$token}");

        if (! $userId) {
            return null;
        }

        // Get user
        $user = PortalUser::withoutGlobalScopes()->find($userId);

        if (! $user || ! $user->is_active) {
            return null;
        }

        // Set company context
        app()->instance('current_company_id', $user->company_id);

        // Extend token expiry
        Cache::put("portal_session_token:{$token}", $userId, now()->addHours(24));

        return $user;
    }

    /**
     * Store session data.
     */
    protected function storeSessionData(PortalUser $user): void
    {
        Session::put('portal_user_id', $user->id);
        Session::put('portal_company_id', $user->company_id);
        Session::put('portal_authenticated', true);
        Session::put('portal_login_time', now()->toIso8601String());

        // Store user permissions
        $permissions = $this->getUserPermissions($user);
        Session::put('portal_permissions', $permissions);

        // Create backup cookie
        Cookie::queue('portal_session_backup', encrypt([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'authenticated' => true,
            'login_time' => now()->toIso8601String(),
        ]), 60 * 24 * 7); // 7 days
    }

    /**
     * Generate session token for API authentication.
     */
    protected function generateSessionToken(PortalUser $user): string
    {
        $token = Str::random(64);

        // Store in cache with 24 hour expiry
        Cache::put("portal_session_token:{$token}", $user->id, now()->addHours(24));

        return $token;
    }

    /**
     * Clear session token.
     */
    protected function clearSessionToken(PortalUser $user): void
    {
        // Find and clear all tokens for this user
        // In production, you'd want to track these in database
        // For now, we'll just clear the current session
        $token = Session::get('api_session_token');
        if ($token) {
            Cache::forget("portal_session_token:{$token}");
        }
    }

    /**
     * Get user data for response.
     */
    protected function getUserData(PortalUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'role' => $user->role ?? 'user',
            'permissions' => $this->getUserPermissions($user),
            'preferences' => $user->preferences ?? [],
            'two_factor_enabled' => ! empty($user->two_factor_secret),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }

    /**
     * Get user permissions.
     */
    protected function getUserPermissions(PortalUser $user): array
    {
        // For now, return role-based permissions
        // In production, this would query the permissions table
        $rolePermissions = [
            'admin' => ['*'], // All permissions
            'manager' => [
                'dashboard.view',
                'calls.view_all',
                'calls.edit_all',
                'appointments.view_all',
                'appointments.edit_all',
                'customers.view_all',
                'customers.edit_all',
                'team.view',
                'team.manage',
                'billing.view',
                'billing.manage',
                'analytics.view',
                'settings.view',
                'settings.edit',
            ],
            'user' => [
                'dashboard.view',
                'calls.view_own',
                'calls.edit_own',
                'appointments.view_own',
                'appointments.edit_own',
                'customers.view_own',
                'team.view',
                'billing.view',
                'analytics.view_own',
                'settings.view',
                'settings.edit_own',
            ],
        ];

        $role = $user->role ?? 'user';

        return $rolePermissions[$role] ?? $rolePermissions['user'];
    }

    /**
     * Refresh user session.
     */
    public function refreshSession(): ?array
    {
        $user = $this->user();

        if (! $user) {
            return null;
        }

        // Refresh session data
        $this->storeSessionData($user);

        return $this->getUserData($user);
    }

    /**
     * Verify CSRF token for AJAX requests.
     */
    public function verifyCsrfToken(string $token): bool
    {
        return hash_equals(
            Session::token(),
            $token
        );
    }
}
