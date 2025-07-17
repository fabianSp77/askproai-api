<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINAL TEST Business Portal Email ===\n\n";

// 1. Simulate real browser session
$sessionManager = app('session');
$sessionManager->start();
$session = $sessionManager->driver();
$csrfToken = $session->token();

echo "1. Session Setup:\n";
echo "   Session ID: " . $session->getId() . "\n";
echo "   CSRF Token: " . substr($csrfToken, 0, 20) . "...\n\n";

// 2. Get portal user and login
$portalUser = \App\Models\PortalUser::first();
if (!$portalUser) {
    echo "❌ No portal user found!\n";
    exit(1);
}

// Proper portal login
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
$session->put('login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal'), $portalUser->id);
$session->put('portal_user_id', $portalUser->id);
$session->save();

echo "2. Authenticated as: " . $portalUser->email . "\n\n";

// 3. Get call 232
$callId = 232;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

echo "3. Testing send-summary for call #$callId:\n";

// 4. Create proper request
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

$request = \Illuminate\Http\Request::create(
    "/business/api/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true,
        'message' => 'Test aus Business Portal - ' . now()->format('H:i:s')
    ]
);

// Set proper session
$request->setLaravelSession($session);
$request->headers->set('X-CSRF-TOKEN', $csrfToken);
$request->headers->set('X-Requested-With', 'XMLHttpRequest');
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

try {
    // Call controller directly
    $response = $controller->sendSummary($request, $call);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() == 200) {
        echo "   ✅ Success: " . ($responseData['message'] ?? 'OK') . "\n";
    } else {
        echo "   ❌ Error: " . json_encode($responseData) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// 5. Check queue
echo "\n4. Checking email queue:\n";
$redis = app('redis');
$emailsInQueue = $redis->llen("queues:emails");
echo "   Emails in queue: $emailsInQueue\n";

// 6. Wait and check Horizon
sleep(2);

// 7. Check activities
$activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->get();

echo "\n5. Recent email activities:\n";
foreach ($activities as $activity) {
    echo "   - " . $activity->created_at->format('H:i:s') . ": " . ($activity->details['recipients'] ?? 'unknown') . "\n";
}

echo "\n=== EXPECTED RESULT ===\n";
echo "Email should be sent to: fabianspitzer@icloud.com\n";
echo "With professional template and CSV attachment\n";