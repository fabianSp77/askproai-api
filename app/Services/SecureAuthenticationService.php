<?php

// WARNING: Potential circular reference detected. Consider using method injection instead of constructor injection.

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Services\TwoFactorService;
use App\Scopes\SecureTenantScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Secure Authentication Service
 * 
 * Handles all authentication operations with strict security measures:
 * - Rate limiting and brute force protection
 * - Secure tenant isolation
 * - Complete 2FA implementation
 * - Audit logging for all operations
 * - No fallback to arbitrary companies
 */
class SecureAuthenticationService
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Authenticate user with secure multi-tenant isolation
     */
    public function authenticate(string $email, string $password, bool $remember = false, ?string $guard = 'web'): array
    {
        // Rate limiting check
        $this->checkRateLimit($email);

        // Find user WITHOUT tenant scope during authentication
        $user = $this->findUserForAuthentication($email);

        if (!$user) {
            $this->recordFailedAttempt($email, 'user_not_found');
            $this->incrementRateLimit($email);
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            $this->recordFailedAttempt($email, 'invalid_password', $user->id);
            $this->incrementRateLimit($email);
            $this->incrementUserFailedAttempts($user);
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            $this->recordFailedAttempt($email, 'user_inactive', $user->id);
            
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.']
            ]);
        }

        // Check if user is locked due to too many failed attempts
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $this->recordFailedAttempt($email, 'user_locked', $user->id);
            
            throw ValidationException::withMessages([
                'email' => ['Your account is temporarily locked. Please try again later.']
            ]);
        }

        // Validate company context
        $this->validateUserCompanyContext($user);

        // Clear failed attempts on successful password verification
        $this->clearUserFailedAttempts($user);
        $this->clearRateLimit($email);

        // Check 2FA requirements
        $requires2FA = $this->checkTwoFactorRequirements($user);

        if ($requires2FA['required'] && !$requires2FA['setup_complete']) {
            // Need to setup 2FA first
            return [
                'status' => 'requires_2fa_setup',
                'user_id' => $user->id,
                'next_step' => 'setup_2fa'
            ];
        }

        if ($requires2FA['required'] && $requires2FA['setup_complete']) {
            // Need 2FA verification
            return [
                'status' => 'requires_2fa_verification',
                'user_id' => $user->id,
                'next_step' => 'verify_2fa',
                'methods' => $this->getAvailable2FAMethods($user)
            ];
        }

        // Complete authentication
        return $this->completeAuthentication($user, $remember, $guard);
    }

    /**
     * Verify 2FA code and complete authentication
     */
    public function verifyTwoFactorAndAuthenticate(int $userId, string $code, bool $remember = false, ?string $guard = 'web'): array
    {
        $user = $this->findUserById($userId);
        
        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authentication session.']
            ]);
        }

        // Rate limit 2FA attempts
        $this->checkTwoFactorRateLimit($user);

        $isValid = false;

        // Try authenticator code first
        if ($user->two_factor_secret) {
            $isValid = $this->twoFactorService->verifyCode($user, $code);
        }

        // Try recovery code if authenticator failed
        if (!$isValid && $user->two_factor_recovery_codes) {
            $isValid = $this->twoFactorService->verifyRecoveryCode($user, $code);
        }

        if (!$isValid) {
            $this->recordFailedTwoFactorAttempt($user, $code);
            $this->incrementTwoFactorRateLimit($user);
            
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.']
            ]);
        }

        // Clear 2FA rate limits
        $this->clearTwoFactorRateLimit($user);

        // Complete authentication
        return $this->completeAuthentication($user, $remember, $guard);
    }

    /**
     * Setup 2FA for user
     */
    public function setupTwoFactor(int $userId): array
    {
        $user = $this->findUserById($userId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        // Generate secret if not exists
        if (!$user->two_factor_secret) {
            $secret = $this->twoFactorService->generateSecretKey();
            $user->two_factor_secret = encrypt($secret);
            $user->save();
        }

        // Generate recovery codes
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->save();

        return [
            'qr_code' => $this->twoFactorService->generateQrCode($user),
            'manual_entry_key' => $this->twoFactorService->getManualEntryKey($user),
            'recovery_codes' => $recoveryCodes
        ];
    }

    /**
     * Confirm 2FA setup
     */
    public function confirmTwoFactorSetup(int $userId, string $code): bool
    {
        $user = $this->findUserById($userId);
        
        if (!$user || !$user->two_factor_secret) {
            return false;
        }

        if ($this->twoFactorService->verifyCode($user, $code)) {
            $user->two_factor_confirmed_at = now();
            $user->save();
            
            Log::info('2FA setup completed', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Complete the authentication process
     */
    protected function completeAuthentication(User $user, bool $remember, string $guard): array
    {
        // Set secure company context BEFORE login
        if ($user->company_id) {
            SecureTenantScope::setCompanyContext($user->company_id);
            session(['company_id' => $user->company_id]);
        } else {
            Log::critical('User has no company_id during authentication', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            throw ValidationException::withMessages([
                'email' => ['Account configuration error. Please contact support.']
            ]);
        }

        // Login user
        Auth::guard($guard)->login($user, $remember);

        // Record successful login
        $this->recordSuccessfulLogin($user);

        // Regenerate session ID for security
        Session::regenerate();

        Log::info('User authenticated successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'guard' => $guard,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        return [
            'status' => 'authenticated',
            'user' => $user,
            'company_id' => $user->company_id,
            'redirect_url' => $this->getRedirectUrl($user)
        ];
    }

    /**
     * Find user for authentication (bypassing tenant scope)
     */
    protected function findUserForAuthentication(string $email): ?User
    {
        // Bypass tenant scope for authentication
        return User::withoutGlobalScopes()->where('email', $email)->first();
    }

    /**
     * Find user by ID (bypassing tenant scope for auth flows)
     */
    protected function findUserById(int $userId): ?User
    {
        return User::withoutGlobalScopes()->find($userId);
    }

    /**
     * Validate that user has proper company context
     */
    protected function validateUserCompanyContext(User $user): void
    {
        if (!$user->company_id) {
            Log::critical('User has no company_id', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            throw ValidationException::withMessages([
                'email' => ['Account configuration error. Please contact support.']
            ]);
        }

        // Verify company exists and is active
        $company = Company::find($user->company_id);
        if (!$company || !$company->is_active) {
            Log::critical('User belongs to inactive or non-existent company', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'company_exists' => $company ? 'yes' : 'no',
                'company_active' => $company?->is_active ? 'yes' : 'no'
            ]);
            
            throw ValidationException::withMessages([
                'email' => ['Account configuration error. Please contact support.']
            ]);
        }
    }

    /**
     * Check two-factor authentication requirements
     */
    protected function checkTwoFactorRequirements(User $user): array
    {
        $required = $user->requires2FA();
        $setupComplete = $user->hasEnabledTwoFactorAuthentication();

        return [
            'required' => $required,
            'setup_complete' => $setupComplete,
            'enforced' => $user->two_factor_enforced,
            'role_required' => $user->hasAnyRole(['Super Admin', 'super_admin', 'company_owner', 'company_admin'])
        ];
    }

    /**
     * Get available 2FA methods for user
     */
    protected function getAvailable2FAMethods(User $user): array
    {
        $methods = [];

        if ($user->two_factor_secret) {
            $methods[] = 'authenticator';
        }

        if ($user->two_factor_recovery_codes) {
            $methods[] = 'recovery_code';
        }

        if ($user->two_factor_phone_verified) {
            $methods[] = 'sms'; // Future implementation
        }

        return $methods;
    }

    /**
     * Rate limiting methods
     */
    protected function checkRateLimit(string $email): void
    {
        $key = 'login_attempts:' . $email;
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."]
            ]);
        }
    }

    protected function incrementRateLimit(string $email): void
    {
        $key = 'login_attempts:' . $email;
        RateLimiter::hit($key, 900); // 15 minutes
    }

    protected function clearRateLimit(string $email): void
    {
        $key = 'login_attempts:' . $email;
        RateLimiter::clear($key);
    }

    protected function checkTwoFactorRateLimit(User $user): void
    {
        $key = '2fa_attempts:' . $user->id;
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            
            throw ValidationException::withMessages([
                'code' => ["Too many 2FA attempts. Please try again in {$seconds} seconds."]
            ]);
        }
    }

    protected function incrementTwoFactorRateLimit(User $user): void
    {
        $key = '2fa_attempts:' . $user->id;
        RateLimiter::hit($key, 300); // 5 minutes
    }

    protected function clearTwoFactorRateLimit(User $user): void
    {
        $key = '2fa_attempts:' . $user->id;
        RateLimiter::clear($key);
    }

    /**
     * User lockout methods
     */
    protected function incrementUserFailedAttempts(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;
        $updates = ['failed_login_attempts' => $attempts];

        // Lock account after 10 failed attempts
        if ($attempts >= 10) {
            $updates['locked_until'] = Carbon::now()->addHours(1);
            
            Log::warning('User account locked due to failed attempts', [
                'user_id' => $user->id,
                'email' => $user->email,
                'attempts' => $attempts
            ]);
        }

        $user->update($updates);
    }

    protected function clearUserFailedAttempts(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * Audit logging methods
     */
    protected function recordFailedAttempt(string $email, string $reason, ?int $userId = null): void
    {
        Log::warning('Authentication failed', [
            'email' => $email,
            'user_id' => $userId,
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

    protected function recordFailedTwoFactorAttempt(User $user, string $code): void
    {
        Log::warning('2FA verification failed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'code_length' => strlen($code),
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString()
        ]);
    }

    protected function recordSuccessfulLogin(User $user): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip()
        ]);

        Log::info('Successful authentication', [
            'user_id' => $user->id,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get redirect URL based on user's portal type and roles
     */
    protected function getRedirectUrl(User $user): string
    {
        return $user->getDefaultPortalRoute();
    }

    /**
     * Logout user and clear all sessions
     */
    public function logout(?string $guard = 'web'): void
    {
        $user = Auth::guard($guard)->user();
        
        if ($user) {
            Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip()
            ]);
        }

        Auth::guard($guard)->logout();
        SecureTenantScope::clearCompanyContext();
        Session::invalidate();
        Session::regenerateToken();
    }
}