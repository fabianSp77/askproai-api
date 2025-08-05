<?php

/**
 * Security Monitoring Script for Auth Audit Logs
 * Run this script to monitor authentication security events
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== AUTH SECURITY MONITORING ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check recent login failures
echo "1. Recent Failed Login Attempts (Last Hour):\n";
$failedLogins = DB::table('auth_audit_logs')
    ->where('event_type', 'login_failed')
    ->where('created_at', '>=', now()->subHour())
    ->select('email', 'ip_address', 'reason', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($failedLogins->isEmpty()) {
    echo "   ✅ No failed login attempts in the last hour\n";
} else {
    echo "   ⚠️  Found {$failedLogins->count()} failed attempts:\n";
    foreach ($failedLogins as $attempt) {
        echo "      - {$attempt->email} from {$attempt->ip_address} at {$attempt->created_at} ({$attempt->reason})\n";
    }
}

// Check locked accounts
echo "\n2. Currently Locked Accounts:\n";
$lockedAccounts = DB::table('users')
    ->where('locked_until', '>', now())
    ->select('email', 'locked_until', 'failed_login_attempts')
    ->get();

if ($lockedAccounts->isEmpty()) {
    echo "   ✅ No accounts currently locked\n";
} else {
    echo "   ⚠️  {$lockedAccounts->count()} accounts locked:\n";
    foreach ($lockedAccounts as $account) {
        echo "      - {$account->email} locked until {$account->locked_until} ({$account->failed_login_attempts} attempts)\n";
    }
}

// Check suspicious IPs
echo "\n3. Suspicious IP Addresses (>5 failures/hour):\n";
$suspiciousIPs = DB::table('auth_audit_logs')
    ->where('event_type', 'login_failed')
    ->where('created_at', '>=', now()->subHour())
    ->select('ip_address', DB::raw('COUNT(*) as attempt_count'))
    ->groupBy('ip_address')
    ->having('attempt_count', '>', 5)
    ->orderBy('attempt_count', 'desc')
    ->get();

if ($suspiciousIPs->isEmpty()) {
    echo "   ✅ No suspicious IP addresses detected\n";
} else {
    echo "   🚨 Found {$suspiciousIPs->count()} suspicious IPs:\n";
    foreach ($suspiciousIPs as $ip) {
        echo "      - {$ip->ip_address}: {$ip->attempt_count} failed attempts\n";
    }
}

// Check 2FA failures
echo "\n4. 2FA Verification Failures (Last 24h):\n";
$twoFAFailures = DB::table('auth_audit_logs')
    ->where('event_type', '2fa_failed')
    ->where('created_at', '>=', now()->subDay())
    ->select('email', 'ip_address', DB::raw('COUNT(*) as failure_count'))
    ->groupBy('email', 'ip_address')
    ->orderBy('failure_count', 'desc')
    ->limit(5)
    ->get();

if ($twoFAFailures->isEmpty()) {
    echo "   ✅ No 2FA failures in the last 24 hours\n";
} else {
    echo "   ⚠️  2FA failures detected:\n";
    foreach ($twoFAFailures as $failure) {
        echo "      - {$failure->email} from {$failure->ip_address}: {$failure->failure_count} failures\n";
    }
}

// Check successful logins from new IPs
echo "\n5. Successful Logins from New IPs (Last 24h):\n";
$newIPLogins = DB::table('auth_audit_logs as a1')
    ->where('a1.event_type', 'login_success')
    ->where('a1.created_at', '>=', now()->subDay())
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
            ->from('auth_audit_logs as a2')
            ->whereRaw('a2.user_id = a1.user_id')
            ->whereRaw('a2.ip_address = a1.ip_address')
            ->whereRaw('a2.created_at < a1.created_at - INTERVAL 1 DAY')
            ->where('a2.event_type', 'login_success');
    })
    ->select('a1.email', 'a1.ip_address', 'a1.created_at')
    ->orderBy('a1.created_at', 'desc')
    ->limit(10)
    ->get();

if ($newIPLogins->isEmpty()) {
    echo "   ✅ No logins from new IP addresses\n";
} else {
    echo "   ℹ️  {$newIPLogins->count()} logins from new IPs:\n";
    foreach ($newIPLogins as $login) {
        echo "      - {$login->email} from {$login->ip_address} at {$login->created_at}\n";
    }
}

// Overall statistics
echo "\n6. Overall Statistics (Last 24h):\n";
$stats = DB::table('auth_audit_logs')
    ->where('created_at', '>=', now()->subDay())
    ->select('event_type', DB::raw('COUNT(*) as count'))
    ->groupBy('event_type')
    ->get()
    ->pluck('count', 'event_type');

echo "   - Successful logins: " . ($stats['login_success'] ?? 0) . "\n";
echo "   - Failed logins: " . ($stats['login_failed'] ?? 0) . "\n";
echo "   - Blocked logins: " . ($stats['login_blocked'] ?? 0) . "\n";
echo "   - 2FA challenges: " . ($stats['2fa_required'] ?? 0) . "\n";
echo "   - 2FA successes: " . ($stats['2fa_success'] ?? 0) . "\n";
echo "   - 2FA failures: " . ($stats['2fa_failed'] ?? 0) . "\n";
echo "   - Logouts: " . ($stats['logout'] ?? 0) . "\n";

// Recommendations
echo "\n7. Security Recommendations:\n";
if (!$suspiciousIPs->isEmpty()) {
    echo "   🔴 Consider blocking these suspicious IPs at firewall level\n";
}
if (!$lockedAccounts->isEmpty()) {
    echo "   🟡 Review locked accounts for potential compromise\n";
}
if (!$twoFAFailures->isEmpty()) {
    echo "   🟡 Monitor users with multiple 2FA failures\n";
}
if ($stats['login_failed'] ?? 0 > 100) {
    echo "   🔴 High number of failed logins - possible brute force attack\n";
}
if ($suspiciousIPs->isEmpty() && $lockedAccounts->isEmpty() && $twoFAFailures->isEmpty()) {
    echo "   ✅ No immediate security concerns detected\n";
}

echo "\n=== END OF SECURITY REPORT ===\n\n";