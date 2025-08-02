<?php
// Complete Session Flow Test

echo "<h1>Complete Session Flow Test</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

// Step 1: Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Step 1: Configuration Check\n";
echo "- APP_URL: " . config('app.url') . "\n";
echo "- Session Driver: " . config('session.driver') . "\n";
echo "- Session Cookie: " . config('session.cookie') . "\n";
echo "- Session Domain: " . config('session.domain') . "\n";
echo "- Session Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "- Session HTTP Only: " . (config('session.http_only') ? 'Yes' : 'No') . "\n";
echo "- Session Same Site: " . config('session.same_site') . "\n";
echo "\n";

// Step 2: Test Login Page
echo "Step 2: Testing Login Page\n";
$loginRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/login', 'GET', [], [], [], [
    'HTTP_HOST' => 'api.askproai.de',
    'HTTPS' => 'on',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);

$loginResponse = $kernel->handle($loginRequest);
echo "- Login page status: " . $loginResponse->getStatusCode() . "\n";
echo "- Is secure request: " . ($loginRequest->secure() ? 'Yes' : 'No') . "\n";

// Check for set-cookie headers
$setCookieHeaders = $loginResponse->headers->get('set-cookie', null, false);
if ($setCookieHeaders) {
    echo "- Session cookie set: Yes\n";
    foreach ((array)$setCookieHeaders as $cookie) {
        if (strpos($cookie, config('session.cookie')) !== false) {
            echo "  Cookie details: " . substr($cookie, 0, 100) . "...\n";
            // Check for secure flag
            if (strpos($cookie, 'secure') !== false) {
                echo "  ✅ Cookie has secure flag\n";
            } else {
                echo "  ❌ Cookie missing secure flag\n";
            }
            // Check for httponly flag
            if (strpos($cookie, 'httponly') !== false) {
                echo "  ✅ Cookie has httponly flag\n";
            } else {
                echo "  ❌ Cookie missing httponly flag\n";
            }
        }
    }
} else {
    echo "- Session cookie set: No\n";
}

echo "\n";

// Step 3: Test Admin Calls Page (should redirect to login)
echo "Step 3: Testing Admin Calls Page (unauthenticated)\n";
$callsRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
    'HTTP_HOST' => 'api.askproai.de',
    'HTTPS' => 'on',
    'HTTP_X_FORWARDED_PROTO' => 'https',
]);

$callsResponse = $kernel->handle($callsRequest);
echo "- Calls page status: " . $callsResponse->getStatusCode() . "\n";
if ($callsResponse->isRedirect()) {
    echo "- Redirects to: " . $callsResponse->headers->get('Location') . "\n";
    echo "✅ Correctly redirects unauthenticated users to login\n";
} else {
    echo "⚠️  Did not redirect - might be accessible without auth\n";
}

echo "\n";

// Step 4: Test with authenticated user
echo "Step 4: Testing with authenticated user\n";
try {
    // Find a user
    $user = \App\Models\User::where('is_admin', true)->first();
    if ($user) {
        echo "- Found admin user: " . $user->email . "\n";
        echo "- Company ID: " . $user->company_id . "\n";
        
        // Manually authenticate user
        auth()->login($user);
        
        // Create new request with auth
        $authRequest = Illuminate\Http\Request::create('https://api.askproai.de/admin/calls', 'GET', [], [], [], [
            'HTTP_HOST' => 'api.askproai.de',
            'HTTPS' => 'on',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        
        // Set the authenticated user on the request
        $authRequest->setUserResolver(function () use ($user) {
            return $user;
        });
        
        $app->instance('request', $authRequest);
        
        $authResponse = $kernel->handle($authRequest);
        echo "- Authenticated calls page status: " . $authResponse->getStatusCode() . "\n";
        
        if ($authResponse->getStatusCode() == 200) {
            echo "✅ Page loads successfully for authenticated user\n";
        } elseif ($authResponse->getStatusCode() == 500) {
            echo "❌ Still getting 500 error for authenticated user\n";
            // Try to extract error
            $content = $authResponse->getContent();
            if (preg_match('/<div class="message"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
                echo "  Error: " . strip_tags($matches[1]) . "\n";
            }
        }
        
        // Check if company context was set
        echo "- Company context set: " . ($app->has('current_company_id') ? 'Yes' : 'No') . "\n";
        if ($app->has('current_company_id')) {
            echo "- Current company ID: " . $app->get('current_company_id') . "\n";
        }
        
    } else {
        echo "❌ No admin user found in database\n";
    }
} catch (\Exception $e) {
    echo "❌ Error during auth test: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 5: Summary
echo "Step 5: Summary\n";
echo str_repeat('-', 40) . "\n";
if (config('session.secure')) {
    echo "✅ Session secure cookie is enabled\n";
} else {
    echo "❌ Session secure cookie is disabled\n";
}

if ($loginResponse->getStatusCode() == 200) {
    echo "✅ Login page is accessible\n";
} else {
    echo "❌ Login page returns error: " . $loginResponse->getStatusCode() . "\n";
}

echo "\nRECOMMENDATIONS:\n";
echo "1. Clear browser cookies and cache\n";
echo "2. Try logging in with a fresh browser session\n";
echo "3. Check browser console for JavaScript errors\n";
echo "4. If still getting 500 error, check Laravel logs: tail -f storage/logs/laravel.log\n";

$kernel->terminate($callsRequest, $callsResponse);

echo "</pre>";