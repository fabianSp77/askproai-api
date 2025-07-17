<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINAL EMAIL DIAGNOSIS ===\n\n";

// 1. Create a real portal user session
$portalUser = \App\Models\PortalUser::first();
$sessionManager = app('session');
$sessionManager->start();
$session = $sessionManager->driver();

// Login
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
$session->put('login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal'), $portalUser->id);
$session->put('portal_user_id', $portalUser->id);
$session->put('current_company_id', $portalUser->company_id);
$session->save();

$sessionId = $session->getId();
$csrfToken = $session->token();

echo "1. Session Setup:\n";
echo "   User: " . $portalUser->email . "\n";
echo "   Session ID: $sessionId\n";
echo "   CSRF Token: " . substr($csrfToken, 0, 20) . "...\n";
echo "   Company ID: " . $portalUser->company_id . "\n\n";

// 2. Test different API call methods
$callId = 232;
$testEmail = 'fabianspitzer@icloud.com';

// Clear recent activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(10))
    ->delete();

echo "2. Testing Different Request Methods:\n\n";

// Method 1: Direct Controller (what my tests do)
echo "   A) Direct Controller Call:\n";
try {
    $controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);
    $request = \Illuminate\Http\Request::create(
        "/business/api/calls/{$callId}/send-summary",
        'POST',
        [
            'recipients' => [$testEmail],
            'include_transcript' => true,
            'include_csv' => true,
            'message' => 'Test A - Direct Controller'
        ]
    );
    $request->setLaravelSession($session);
    $request->setUserResolver(function () use ($portalUser) {
        return $portalUser;
    });
    
    $call = \App\Models\Call::findOrFail($callId);
    $response = $controller->sendSummary($request, $call);
    echo "      Status: " . $response->getStatusCode() . "\n";
    echo "      ✅ SUCCESS\n";
} catch (\Exception $e) {
    echo "      ❌ ERROR: " . $e->getMessage() . "\n";
}

// Method 2: Through Router (closer to real browser)
echo "\n   B) Through Laravel Router:\n";
try {
    $request = \Illuminate\Http\Request::create(
        "/business/api/calls/{$callId}/send-summary",
        'POST',
        [
            'recipients' => [$testEmail . '.test2'],
            'include_transcript' => true,
            'include_csv' => true
        ],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $csrfToken,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ],
        json_encode([
            'recipients' => [$testEmail . '.test2'],
            'include_transcript' => true,
            'include_csv' => true
        ])
    );
    
    $request->headers->set('Content-Type', 'application/json');
    $request->setLaravelSession($session);
    $request->setUserResolver(function () use ($portalUser) {
        return $portalUser;
    });
    
    // Set company context
    app()->instance('current_company_id', $portalUser->company_id);
    
    $response = app()->handle($request);
    echo "      Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        echo "      ✅ SUCCESS\n";
    } else {
        echo "      ❌ FAILED: " . $response->getContent() . "\n";
    }
} catch (\Exception $e) {
    echo "      ❌ ERROR: " . $e->getMessage() . "\n";
}

// 3. Check what happens in browser
echo "\n3. Browser Request Analysis:\n";
echo "   When you click in browser:\n";
echo "   1. JavaScript reads CSRF from: window.Laravel.csrfToken\n";
echo "   2. Makes fetch() to: /business/api/calls/{id}/send-summary\n";
echo "   3. Includes headers:\n";
echo "      - X-CSRF-TOKEN: {token}\n";
echo "      - Content-Type: application/json\n";
echo "      - Accept: application/json\n";
echo "      - X-Requested-With: XMLHttpRequest\n";
echo "   4. Uses credentials: 'include' (sends cookies)\n";

// 4. Check middleware
echo "\n4. Middleware Analysis:\n";
$route = app('router')->getRoutes()->match(
    \Illuminate\Http\Request::create('/business/api/calls/1/send-summary', 'POST')
);
if ($route) {
    $middleware = $route->middleware();
    foreach ($middleware as $mw) {
        echo "   - $mw";
        
        // Check specific middleware
        if ($mw === 'web') {
            echo " (includes CSRF protection)";
        } elseif ($mw === 'portal.auth.api') {
            echo " (portal authentication)";
        }
        echo "\n";
    }
}

// 5. Recent errors
echo "\n5. Recent Errors in Logs:\n";
$logs = shell_exec("tail -30 " . storage_path('logs/laravel.log') . " | grep -E 'ERROR|Exception|portal.email' | tail -5");
if ($logs) {
    echo $logs;
} else {
    echo "   No recent errors\n";
}

// 6. Final check
echo "\n6. Email Queue Status:\n";
$redis = app('redis');
echo "   Emails in queue: " . $redis->llen('queues:emails') . "\n";

$activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(5))
    ->count();
echo "   Recent email activities: $activities\n";

echo "\n=== WHAT TO CHECK IN BROWSER ===\n";
echo "1. Open Developer Tools (F12)\n";
echo "2. Go to Console tab\n";
echo "3. Type: window.Laravel.csrfToken\n";
echo "   Should show the token\n";
echo "4. Go to Network tab\n";
echo "5. Click send email button\n";
echo "6. Look for 'send-summary' request\n";
echo "7. Check:\n";
echo "   - Status code (should be 200)\n";
echo "   - Request headers (X-CSRF-TOKEN)\n";
echo "   - Response body\n";
echo "8. Share any red errors from console\n";