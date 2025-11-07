<?php
/**
 * Diagnostic Script: Login Issues
 *
 * Usage: php artisan tinker < scripts/diagnose_login_issues.php
 * Or: php scripts/diagnose_login_issues.php
 *
 * Checks:
 * 1. User exists in database
 * 2. Password hash is valid (bcrypt format)
 * 3. Password matches plaintext via Hash::check()
 * 4. Laravel Auth::attempt() works
 * 5. Filament access controls pass
 * 6. User has appropriate roles
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ Filament Login Diagnostic Tool                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Get email from argv or prompt
$email = $argv[1] ?? 'owner@friseur1test.local';
$password = $argv[2] ?? null;

echo "Email: $email\n";
if (!$password) {
    echo "ERROR: Password not provided. Usage: php diagnose_login_issues.php <email> <password>\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════
// 1. CHECK USER EXISTS
// ═══════════════════════════════════════════════════════════════
echo "\n[1] USER LOOKUP\n";
echo "─────────────────────────────────────────────────────────────\n";

$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User not found in database!\n";
    echo "   Email: $email\n";
    exit(1);
}

echo "✅ User found\n";
echo "   ID: {$user->id}\n";
echo "   Email: {$user->email}\n";
echo "   Name: {$user->name}\n";

// ═══════════════════════════════════════════════════════════════
// 2. CHECK USER STATUS
// ═══════════════════════════════════════════════════════════════
echo "\n[2] USER STATUS\n";
echo "─────────────────────────────────────────────────────────────\n";

$checks = [
    'is_active' => $user->is_active,
    'email_verified' => $user->email_verified_at !== null,
];

foreach ($checks as $check => $result) {
    echo ($result ? '✅' : '❌') . " $check: " . ($result ? 'Yes' : 'No') . "\n";
}

if (!$user->is_active) {
    echo "\n⚠️  WARNING: User account is inactive\n";
}

if (!$user->email_verified_at) {
    echo "\n⚠️  WARNING: User email not verified\n";
}

// ═══════════════════════════════════════════════════════════════
// 3. CHECK PASSWORD HASH
// ═══════════════════════════════════════════════════════════════
echo "\n[3] PASSWORD HASH ANALYSIS\n";
echo "─────────────────────────────────────────────────────────────\n";

$hash = $user->password;
echo "Hash: " . substr($hash, 0, 20) . "...\n";
echo "Length: " . strlen($hash) . "\n";

// Check if it's valid bcrypt
$isValidBcrypt = preg_match('/^\$2[aby]\$\d{2}\$.{53}$/', $hash) === 1;
echo ($isValidBcrypt ? '✅' : '❌') . " Valid bcrypt format: " . ($isValidBcrypt ? 'Yes' : 'No') . "\n";

// Check if password matches
$hashMatches = Hash::check($password, $hash);
echo ($hashMatches ? '✅' : '❌') . " Hash::check() with plaintext: " . ($hashMatches ? 'MATCH' : 'MISMATCH') . "\n";

if (!$hashMatches) {
    echo "\n⚠️  CRITICAL: Password hash does not match plaintext!\n";
    echo "   This is the likely cause of login failures.\n";
    echo "   Recommendation: Reset user password\n";
}

// ═══════════════════════════════════════════════════════════════
// 4. TEST LARAVEL AUTH
// ═══════════════════════════════════════════════════════════════
echo "\n[4] LARAVEL AUTHENTICATION\n";
echo "─────────────────────────────────────────────────────────────\n";

$authAttempt = Auth::attempt([
    'email' => $email,
    'password' => $password,
]);

echo ($authAttempt ? '✅' : '❌') . " Auth::attempt(): " . ($authAttempt ? 'SUCCESS' : 'FAILED') . "\n";

if (Auth::check()) {
    echo "✅ Auth::check(): Authenticated\n";
    echo "   Current User: " . Auth::user()->email . "\n";
    Auth::logout();
} else {
    echo "❌ Auth::check(): Not authenticated\n";
}

// Re-fetch user for role checks (Auth::logout() affects the query)
$user = User::find($user->id);

// ═══════════════════════════════════════════════════════════════
// 5. CHECK USER ROLES
// ═══════════════════════════════════════════════════════════════
echo "\n[5] USER ROLES & PERMISSIONS\n";
echo "─────────────────────────────────────────────────────────────\n";

$roles = $user->getRoleNames()->toArray();
echo "Roles: " . implode(', ', $roles) . "\n";

// Check if user can access admin panel
$adminRoles = ['super_admin', 'Admin', 'reseller_admin'];
$canAccessAdmin = $user->hasAnyRole($adminRoles);
echo ($canAccessAdmin ? '✅' : '❌') . " Can access Admin Panel: " . ($canAccessAdmin ? 'Yes' : 'No') . "\n";

if (!$canAccessAdmin) {
    echo "   User needs one of: " . implode(', ', $adminRoles) . "\n";
}

// Check if user implements FilamentUser
$implementsFilament = $user instanceof \Filament\Models\Contracts\FilamentUser;
echo ($implementsFilament ? '✅' : '❌') . " Implements FilamentUser: " . ($implementsFilament ? 'Yes' : 'No') . "\n";

// ═══════════════════════════════════════════════════════════════
// 6. SUMMARY & RECOMMENDATIONS
// ═══════════════════════════════════════════════════════════════
echo "\n[6] DIAGNOSIS SUMMARY\n";
echo "─────────────────────────────────────────────────────────────\n";

$issues = [];

if (!$user->is_active) {
    $issues[] = "❌ User account is inactive";
}

if (!$user->email_verified_at) {
    $issues[] = "⚠️  User email not verified (may block login)";
}

if (!$isValidBcrypt) {
    $issues[] = "❌ Password hash is not valid bcrypt format";
}

if (!$hashMatches) {
    $issues[] = "❌ Password hash does not match plaintext (PRIMARY ISSUE)";
}

if (!$authAttempt) {
    $issues[] = "❌ Auth::attempt() failed";
}

if (!$canAccessAdmin) {
    $issues[] = "⚠️  User cannot access admin panel (missing admin role)";
}

if (empty($issues)) {
    echo "✅ All checks passed!\n";
    echo "   User should be able to login successfully.\n";
} else {
    echo "❌ Issues detected:\n";
    foreach ($issues as $issue) {
        echo "   $issue\n";
    }
}

// ═══════════════════════════════════════════════════════════════
// 7. FIX RECOMMENDATIONS
// ═══════════════════════════════════════════════════════════════
echo "\n[7] RECOMMENDED FIXES\n";
echo "─────────────────────────────────────────────────────────────\n";

if (!$hashMatches) {
    echo "To reset password:\n\n";
    echo "php artisan tinker\n";
    echo "\$user = \\App\\Models\\User::find({$user->id});\n";
    echo "\$user->password = 'NewPassword123!';\n";
    echo "\$user->save();\n\n";
    echo "Or use SQL:\n";
    echo "UPDATE users SET password = '...' WHERE id = {$user->id};\n";
}

echo "\n";
