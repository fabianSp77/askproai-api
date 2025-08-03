<?php
echo "=== Fixing Business Portal Authentication ===\n\n";

// 1. Check session configuration
echo "1. Checking session configuration files...\n";
$sessionConfig = file_get_contents(__DIR__ . '/config/session.php');
$portalSessionConfig = file_get_contents(__DIR__ . '/config/session_portal.php');

// Check environment variables
$envContent = file_get_contents(__DIR__ . '/.env');
preg_match('/SESSION_COOKIE=(.*)/', $envContent, $mainCookie);
preg_match('/PORTAL_SESSION_COOKIE=(.*)/', $envContent, $portalCookie);
echo "   Main session cookie: " . ($mainCookie[1] ?? 'askproai_session') . "\n";
echo "   Portal session cookie: " . ($portalCookie[1] ?? 'askproai_portal_session') . "\n";

// 2. Verify PortalUserProvider is registered
echo "\n2. Checking PortalUserProvider registration...\n";
$authServiceProvider = file_get_contents(__DIR__ . '/app/Providers/AuthServiceProvider.php');
if (strpos($authServiceProvider, "portal_eloquent") !== false) {
    echo "   ✓ PortalUserProvider is registered\n";
} else {
    echo "   ✗ PortalUserProvider NOT registered\n";
}

// 3. Fix the main issue - ensure session persistence
echo "\n3. Creating comprehensive session fix...\n";

// Create a fixed version of PortalAuth middleware
$portalAuthFixed = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PortalAuthFixed
{
    public function handle(Request $request, Closure $next)
    {
        // Get the portal guard
        $guard = auth()->guard('portal');
        
        // Check if user is authenticated
        if (!$guard->check()) {
            // Try to restore from session
            $sessionKey = $guard->getName();
            if (session()->has($sessionKey)) {
                $userId = session($sessionKey);
                try {
                    $user = \App\Models\PortalUser::find($userId);
                    if ($user && $user->is_active) {
                        $guard->login($user);
                        \Log::info('PortalAuthFixed - Restored user from session', [
                            'user_id' => $userId,
                            'email' => $user->email,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('PortalAuthFixed - Failed to restore user', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        // If still not authenticated, redirect to login
        if (!$guard->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('business.login');
        }
        
        // Set company context
        $user = $guard->user();
        if ($user && $user->company_id) {
            app()->instance('current_company_id', $user->company_id);
            app()->instance('company_context_source', 'portal_auth');
        }
        
        return $next($request);
    }
}
PHP;

file_put_contents(__DIR__ . '/app/Http/Middleware/PortalAuthFixed.php', $portalAuthFixed);
echo "   Created PortalAuthFixed middleware\n";

// 4. Update Kernel to use the fixed middleware
echo "\n4. Updating middleware registration...\n";
$kernelPath = __DIR__ . '/app/Http/Kernel.php';
$kernel = file_get_contents($kernelPath);

// Add the new middleware alias if not exists
if (strpos($kernel, "'portal.auth.fixed'") === false) {
    $kernel = preg_replace(
        "/'portal.auth'\s*=>\s*Middleware\\\\PortalAuth::class,/",
        "'portal.auth' => Middleware\\PortalAuth::class,\n        'portal.auth.fixed' => Middleware\\PortalAuthFixed::class,",
        $kernel
    );
    file_put_contents($kernelPath, $kernel);
    echo "   Added portal.auth.fixed middleware alias\n";
}

// 5. Fix CustomSessionGuard to ensure consistent key generation
echo "\n5. Fixing CustomSessionGuard getName() method...\n";
$guardPath = __DIR__ . '/app/Auth/CustomSessionGuard.php';
$guardContent = file_get_contents($guardPath);

// Ensure getName() returns consistent key
if (strpos($guardContent, 'public function getName()') === false) {
    // getName method doesn't exist, add it
    $guardContent = str_replace(
        'class CustomSessionGuard extends SessionGuard
{',
        'class CustomSessionGuard extends SessionGuard
{
    /**
     * Get a unique identifier for the auth session value.
     * Override to ensure consistent session key generation.
     *
     * @return string
     */
    public function getName()
    {
        // Use parent class name for consistent key generation across guards
        return \'login_\'.$this->name.\'_\'.sha1(\\Illuminate\\Auth\\SessionGuard::class);
    }',
        $guardContent
    );
    file_put_contents($guardPath, $guardContent);
    echo "   ✓ Fixed getName() method in CustomSessionGuard\n";
} else {
    echo "   getName() method already exists\n";
}

// 6. Clear config cache
echo "\n6. Clearing configuration cache...\n";
exec('php artisan config:clear 2>&1', $output);
echo "   " . implode("\n   ", $output) . "\n";

// 7. Create test script
$testScript = <<<'PHP'
<?php
// Test business portal login directly

$url = 'https://api.askproai.de';
$cookieFile = tempnam(sys_get_temp_dir(), 'portal_test_');

echo "Testing Business Portal Login...\n\n";

// 1. Get login page
$ch = curl_init($url . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$response = curl_exec($ch);
curl_close($ch);

// Extract CSRF
preg_match('/name="_token"\s+value="([^"]+)"/', $response, $matches);
$csrf = $matches[1] ?? null;

// 2. Login
$ch = curl_init($url . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrf,
    'email' => 'demo@askproai.de',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Login response: {$info['http_code']}\n";

// 3. Check auth
$ch = curl_init($url . '/business/api/auth/check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Auth check: {$info['http_code']}\n";
if ($info['http_code'] == 200) {
    $data = json_decode($response, true);
    echo "Authenticated: " . ($data['authenticated'] ?? 'unknown') . "\n";
} else {
    echo "Response: $response\n";
}

unlink($cookieFile);
PHP;

file_put_contents(__DIR__ . '/test-portal-auth-final.php', $testScript);
echo "\n7. Created test script: test-portal-auth-final.php\n";

echo "\n=== Fix Complete ===\n";
echo "Now test with: php test-portal-auth-final.php\n";
echo "Or visit: https://api.askproai.de/business-login-simple.html\n";