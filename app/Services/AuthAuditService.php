<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthAuditService
{
    /**
     * Log a failed login attempt
     */
    public function logFailedLogin(string $email, string $ip, string $reason = 'invalid_credentials'): void
    {
        $this->log('login_failed', [
            'email' => $email,
            'ip_address' => $ip,
            'reason' => $reason,
            'result' => 'failed',
        ]);
    }
    
    /**
     * Log a successful login
     */
    public function logSuccessfulLogin(User $user, string $ip): void
    {
        $this->log('login_success', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'result' => 'success',
            'context' => [
                'portal_type' => $user->portal_type,
                'company_id' => $user->company_id,
            ],
        ]);
    }
    
    /**
     * Log a blocked login attempt
     */
    public function logBlockedLogin(User $user, string $reason, string $ip): void
    {
        $this->log('login_blocked', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'reason' => $reason,
            'result' => 'blocked',
        ]);
    }
    
    /**
     * Log 2FA required
     */
    public function log2FARequired(User $user, string $ip): void
    {
        $this->log('2fa_required', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
        ]);
    }
    
    /**
     * Log 2FA success
     */
    public function log2FASuccess(User $user, string $ip): void
    {
        $this->log('2fa_success', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'result' => 'success',
        ]);
    }
    
    /**
     * Log 2FA failure
     */
    public function log2FAFailed(User $user, string $ip): void
    {
        $this->log('2fa_failed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'result' => 'failed',
        ]);
    }
    
    /**
     * Log 2FA enabled
     */
    public function log2FAEnabled(User $user, string $ip): void
    {
        $this->log('2fa_enabled', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
        ]);
    }
    
    /**
     * Log logout
     */
    public function logLogout(User $user, string $ip): void
    {
        $this->log('logout', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
        ]);
    }
    
    /**
     * Log an authentication event
     */
    protected function log(string $eventType, array $data): void
    {
        try {
            DB::table('auth_audit_logs')->insert([
                'event_type' => $eventType,
                'user_id' => $data['user_id'] ?? null,
                'email' => $data['email'] ?? null,
                'ip_address' => $data['ip_address'],
                'user_agent' => request()->userAgent(),
                'context' => isset($data['context']) ? json_encode($data['context']) : null,
                'result' => $data['result'] ?? null,
                'reason' => $data['reason'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log to file if database fails
            Log::channel('security')->error("Failed to log auth event: {$eventType}", [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
    
    /**
     * Get recent authentication events for a user
     */
    public function getRecentEvents(User $user, int $limit = 10): array
    {
        return DB::table('auth_audit_logs')
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Get failed login attempts from an IP
     */
    public function getFailedAttemptsFromIP(string $ip, int $minutes = 60): int
    {
        return DB::table('auth_audit_logs')
            ->where('ip_address', $ip)
            ->where('event_type', 'login_failed')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }
    
    /**
     * Check if IP should be blocked
     */
    public function shouldBlockIP(string $ip): bool
    {
        // Block if more than 20 failed attempts in last hour
        return $this->getFailedAttemptsFromIP($ip, 60) > 20;
    }
}