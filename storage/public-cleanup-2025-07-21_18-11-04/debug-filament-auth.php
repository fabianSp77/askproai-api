<?php
// Debug Filament authentication
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Initialize Filament
\Filament\Facades\Filament::setCurrentPanel(
    \Filament\Facades\Filament::getPanel('admin')
);

echo "<pre>";
echo "Filament Auth Debug:\n";
echo "===================\n\n";

echo "1. Auth Guard Configuration:\n";
echo "   - Default guard: " . config('auth.defaults.guard') . "\n";
echo "   - Filament guard: " . \Filament\Facades\Filament::getAuthGuard() . "\n";
echo "   - Current guard: " . Auth::getDefaultDriver() . "\n\n";

echo "2. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Cookie: " . config('session.cookie') . "\n";
echo "   - Admin cookie: " . config('session_admin.cookie') . "\n\n";

echo "3. Available Guards:\n";
foreach (config('auth.guards') as $name => $config) {
    echo "   - $name: " . $config['driver'] . "\n";
}
echo "\n";

// Check if sessions are properly configured
$sessionId = 'JOEXlBmJRdX2bjveR0H4tHbXWyKpKFSGBNNWtsTW';
$session = DB::table('sessions')->where('id', $sessionId)->first();

echo "4. Test Session Check:\n";
if ($session) {
    echo "   - Session found in DB\n";
    echo "   - User ID: " . $session->user_id . "\n";
    echo "   - Last activity: " . date('Y-m-d H:i:s', $session->last_activity) . "\n";
    
    // Decode payload
    $payload = unserialize(base64_decode($session->payload));
    echo "   - Session keys: " . implode(', ', array_keys($payload)) . "\n";
    
    // Try to authenticate with this session data
    if (isset($payload['login.web'])) {
        echo "   - login.web value: " . $payload['login.web'] . "\n";
    }
} else {
    echo "   - Session NOT found in DB\n";
}

echo "\n5. Filament Panel Info:\n";
$panel = \Filament\Facades\Filament::getPanel('admin');
echo "   - ID: " . $panel->getId() . "\n";
echo "   - Path: " . $panel->getPath() . "\n";
echo "   - Auth guard: " . $panel->getAuthGuard() . "\n";

echo "\n6. Testing Authentication:\n";
// Test if we can authenticate manually
if ($session && isset($payload['login.web'])) {
    $user = \App\Models\User::find($payload['login.web']);
    if ($user) {
        echo "   - Found user: " . $user->email . "\n";
        echo "   - User role: " . $user->role . "\n";
        echo "   - Company: " . ($user->company->name ?? 'None') . "\n";
        
        // Check if user can access panel
        if ($user instanceof \Filament\Models\Contracts\FilamentUser) {
            echo "   - Can access panel: " . ($user->canAccessPanel($panel) ? 'Yes' : 'No') . "\n";
        } else {
            echo "   - User does not implement FilamentUser interface\n";
        }
    }
}

echo "</pre>";
?>