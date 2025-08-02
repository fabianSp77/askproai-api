<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

// Force portal session config BEFORE bootstrapping
$app->make('config')->set([
    'session.cookie' => 'askproai_portal_session',
    'session.files' => storage_path('framework/sessions/portal'),
    'session.path' => '/',
    'session.domain' => null,
]);

// Create a simple request
$request = Illuminate\Http\Request::capture();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request);

// Get session info
$sessionManager = $app->make('session');
$session = $sessionManager->driver();

// Try to decode the portal session cookie
$encryptedSessionId = $_COOKIE['askproai_portal_session'] ?? null;
$decryptedSessionId = null;

if ($encryptedSessionId) {
    try {
        // Use Laravel's encrypter to decrypt the session ID
        $encrypter = $app->make('encrypter');
        $payload = json_decode(base64_decode($encryptedSessionId), true);
        
        if ($payload && isset($payload['payload'])) {
            $decryptedSessionId = $encrypter->decrypt($payload['payload'], false);
        }
    } catch (\Exception $e) {
        $decryptedSessionId = 'Decryption failed: ' . $e->getMessage();
    }
}

// Get session file path
$sessionId = $session->getId();
$sessionFile = storage_path('framework/sessions/portal/' . $sessionId);
$sessionFileExists = file_exists($sessionFile);

// Try to read session data
$sessionData = [];
if ($sessionFileExists) {
    try {
        $fileContent = file_get_contents($sessionFile);
        $sessionData = unserialize($fileContent);
    } catch (\Exception $e) {
        $sessionData = ['error' => $e->getMessage()];
    }
}

// Check auth
$portalAuth = auth()->guard('portal')->check();
$portalUser = auth()->guard('portal')->user();

$output = [
    'session' => [
        'id' => $sessionId,
        'name' => $session->getName(),
        'cookie_name' => config('session.cookie'),
        'is_started' => $session->isStarted(),
    ],
    'cookie' => [
        'encrypted_value' => substr($encryptedSessionId ?? 'NOT SET', 0, 50) . '...',
        'decrypted_id' => $decryptedSessionId,
        'matches_session_id' => $decryptedSessionId === $sessionId,
    ],
    'session_file' => [
        'path' => $sessionFile,
        'exists' => $sessionFileExists,
        'data' => $sessionData,
    ],
    'auth' => [
        'portal_check' => $portalAuth,
        'portal_user' => $portalUser ? [
            'id' => $portalUser->id,
            'email' => $portalUser->email,
        ] : null,
    ],
    'config' => [
        'driver' => config('session.driver'),
        'cookie' => config('session.cookie'),
        'files' => config('session.files'),
        'lifetime' => config('session.lifetime'),
    ],
];

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);