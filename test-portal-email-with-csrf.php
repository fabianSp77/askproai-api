<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST Business Portal Email with CSRF ===\n\n";

// Start a session
session()->start();
$csrfToken = csrf_token();

echo "1. Session & CSRF Setup:\n";
echo "   Session ID: " . session()->getId() . "\n";
echo "   CSRF Token: " . substr($csrfToken, 0, 20) . "...\n\n";

// Get portal user
$portalUser = \App\Models\PortalUser::first();
if (!$portalUser) {
    echo "❌ No portal user found!\n";
    exit(1);
}

// Login the user
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
session(['login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal') => $portalUser->id]);
session(['portal_user_id' => $portalUser->id]);

echo "2. Portal User Login:\n";
echo "   User: " . $portalUser->email . "\n";
echo "   Company ID: " . $portalUser->company_id . "\n\n";

// Get call 232
$callId = 232;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

if (!$call) {
    echo "❌ Call $callId not found!\n";
    exit(1);
}

echo "3. Call Details:\n";
echo "   ID: " . $call->id . "\n";
echo "   Company ID: " . $call->company_id . "\n\n";

// Create request with CSRF token
echo "4. Testing API with CSRF Token:\n";

$request = \Illuminate\Http\Request::create(
    "/business/api/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true
    ]
);

// Set headers
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');
$request->headers->set('X-CSRF-TOKEN', $csrfToken);
$request->headers->set('X-Requested-With', 'XMLHttpRequest');

// Set session
$request->setLaravelSession(session());

// Set user resolver
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

try {
    // Dispatch through the application
    $response = app()->handle($request);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    echo "   Response: " . substr($content, 0, 200) . "...\n";
    
    if ($response->getStatusCode() == 200) {
        echo "   ✅ API call successful\n";
        
        // Check if email was queued
        $redis = app('redis');
        $emailsInQueue = $redis->llen("queues:emails");
        echo "   Emails in queue: $emailsInQueue\n";
    } else {
        echo "   ❌ API call failed\n";
        echo "   Full response:\n" . $content . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== EXPECTED RESULT ===\n";
echo "You should receive an email at fabianspitzer@icloud.com\n";
echo "The email should have:\n";
echo "- Professional design\n";
echo "- Call summary for call #232\n";
echo "- CSV attachment\n";
echo "- Links to askproai.de (not api.askproai.de)\n";