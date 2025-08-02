<?php
/**
 * Debug Auth Login Process
 * 
 * This tool debugs why Auth::login() isn't writing session keys
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

// Get user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('User not found');
}

echo "<h1>Auth Login Debug</h1>";
echo "<pre>";

// 1. Check guard
$guard = Auth::guard('web');
echo "1. Guard Class: " . get_class($guard) . "\n";

// 2. Get session name
$reflection = new ReflectionMethod($guard, 'getName');
$reflection->setAccessible(true);
$sessionKey = $reflection->invoke($guard);
echo "2. Session Key: " . $sessionKey . "\n";

// 3. Check session before login
$session = app('session.store');
echo "3. Session ID: " . $session->getId() . "\n";
echo "4. Session Data Before Login:\n";
print_r($session->all());

// 4. Try login
echo "\n5. Attempting Auth::login()...\n";
try {
    Auth::login($user, true);
    echo "   - Login executed without error\n";
} catch (\Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

// 5. Check auth state
echo "\n6. Auth State After Login:\n";
echo "   - Auth::check() = " . (Auth::check() ? 'TRUE' : 'FALSE') . "\n";
echo "   - Auth::id() = " . Auth::id() . "\n";

// 6. Check session after login
echo "\n7. Session Data After Login:\n";
print_r($session->all());

// 7. Check if updateSession was called
$updateSessionMethod = new ReflectionMethod($guard, 'updateSession');
$updateSessionMethod->setAccessible(true);
echo "\n8. Manually calling updateSession()...\n";
$updateSessionMethod->invoke($guard, $user->id);

echo "\n9. Session Data After updateSession:\n";
print_r($session->all());

// 8. Force save
$session->save();
echo "\n10. Session saved.\n";

// 9. Read from file
$sessionFile = storage_path('framework/sessions') . '/' . $session->getId();
if (file_exists($sessionFile)) {
    echo "\n11. Session File Contents:\n";
    $fileData = unserialize(file_get_contents($sessionFile));
    print_r($fileData);
} else {
    echo "\n11. Session file not found!\n";
}

// 10. Test if we're using CustomSessionGuard
echo "\n12. Guard Method Test:\n";
$methods = get_class_methods($guard);
echo "   - Has updateSession: " . (in_array('updateSession', $methods) ? 'YES' : 'NO') . "\n";
echo "   - Guard parent class: " . get_parent_class($guard) . "\n";

echo "</pre>";

echo '<div style="margin-top: 20px;">';
echo '<a href="/admin" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Go to Admin</a>';
echo '</div>';
?>