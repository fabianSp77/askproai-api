<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST Business Portal API Direct ===\n\n";

// 1. Get call 232
$callId = 232;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

if (!$call) {
    echo "❌ Call $callId not found!\n";
    exit(1);
}

echo "1. Call Details:\n";
echo "   ID: " . $call->id . "\n";
echo "   Company ID: " . $call->company_id . "\n";
echo "   Phone: " . $call->from_number . "\n\n";

// 2. Authenticate as portal user
$portalUser = \App\Models\PortalUser::first();
if (!$portalUser) {
    echo "❌ No portal user found!\n";
    exit(1);
}

\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
echo "2. Authenticated as: " . $portalUser->email . "\n\n";

// 3. Test the actual API endpoint as called from frontend
echo "3. Testing API endpoint /business/api/calls/{$callId}/send-summary:\n";

$request = \Illuminate\Http\Request::create(
    "/business/api/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true
    ],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
    ],
    json_encode([
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true
    ])
);

$request->headers->set('Content-Type', 'application/json');
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Get the controller through route
try {
    $router = app('router');
    $response = $router->dispatch($request);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    echo "   Response: " . $content . "\n";
    
    if ($response->getStatusCode() == 200) {
        echo "   ✅ API call successful\n";
    } else {
        echo "   ❌ API call failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// 4. Check if email was added to queue
echo "\n4. Checking email queue:\n";
$redis = app('redis');
$emailsInQueue = $redis->llen("queues:emails");
echo "   Emails in queue: $emailsInQueue\n";

// 5. Check failed jobs
$failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
echo "   Failed jobs: $failedJobs\n";

// 6. Check activities
$activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->first();

if ($activities) {
    echo "   Last email activity: " . $activities->created_at . "\n";
}

echo "\n=== DIAGNOSIS ===\n";
echo "If email is not sent, check:\n";
echo "1. Portal auth middleware\n";
echo "2. CSRF token validation\n";
echo "3. Queue processing\n";
echo "4. Email service configuration\n";