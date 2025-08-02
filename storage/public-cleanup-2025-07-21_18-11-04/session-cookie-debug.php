<?php
/**
 * Session Cookie Debug - The REAL problem solver
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

header('Content-Type: text/plain');

echo "=== SESSION COOKIE DEBUG ===\n\n";

// 1. Current Cookies
echo "1. CURRENT COOKIES:\n";
foreach ($_COOKIE as $name => $value) {
    echo "   - $name: " . substr($value, 0, 40) . "...\n";
}
echo "\n";

// 2. Session Configuration
echo "2. SESSION CONFIGURATION:\n";
echo "   - Default driver: " . config('session.driver') . "\n";
echo "   - Default cookie: " . config('session.cookie') . "\n";
echo "   - Default path: " . config('session.path') . "\n";
echo "   - Portal cookie: " . config('session_portal.cookie') . "\n";
echo "   - Portal path: " . config('session_portal.path') . "\n";
echo "\n";

// 3. Current Session
echo "3. CURRENT SESSION:\n";
echo "   - Session ID: " . session()->getId() . "\n";
echo "   - Session Name: " . session()->getName() . "\n";
echo "   - Is Started: " . (session()->isStarted() ? 'Yes' : 'No') . "\n";
echo "\n";

// 4. Session Data
echo "4. SESSION DATA:\n";
$sessionData = session()->all();
foreach ($sessionData as $key => $value) {
    if (is_array($value) || is_object($value)) {
        echo "   - $key: " . json_encode($value) . "\n";
    } else {
        echo "   - $key: $value\n";
    }
}
echo "\n";

// 5. Auth Status
echo "5. AUTH STATUS:\n";
echo "   - Web Guard: " . (Auth::guard('web')->check() ? 'Authenticated' : 'Not authenticated') . "\n";
echo "   - Portal Guard: " . (Auth::guard('portal')->check() ? 'Authenticated' : 'Not authenticated') . "\n";
echo "   - Customer Guard: " . (Auth::guard('customer')->check() ? 'Authenticated' : 'Not authenticated') . "\n";

if (Auth::guard('portal')->check()) {
    $user = Auth::guard('portal')->user();
    echo "   - Portal User: " . $user->email . " (ID: " . $user->id . ")\n";
}
echo "\n";

// 6. Request Info
echo "6. REQUEST INFO:\n";
echo "   - Current URL: " . request()->fullUrl() . "\n";
echo "   - HTTP Method: " . request()->method() . "\n";
echo "   - User Agent: " . request()->userAgent() . "\n";
echo "\n";

// 7. Test Session Write
echo "7. TEST SESSION WRITE:\n";
$testKey = 'debug_test_' . time();
session([$testKey => 'Session write test']);
session()->save();
echo "   - Wrote key: $testKey\n";
echo "   - Can read back: " . (session($testKey) === 'Session write test' ? 'Yes' : 'No') . "\n";
?>