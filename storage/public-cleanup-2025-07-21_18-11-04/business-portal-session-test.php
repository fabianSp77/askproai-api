<?php
/**
 * Business Portal Session Test
 * Testet den kompletten Login-Flow und Session-Persistenz
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test credentials
$testEmail = 'demo@askproai.de';
$testPassword = 'password123';

$results = [];

// Step 1: Check if test user exists
try {
    $user = \App\Models\PortalUser::where('email', $testEmail)->first();
    if ($user) {
        $results['user_check'] = [
            'status' => 'success',
            'message' => 'Test user found',
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ];
    } else {
        $results['user_check'] = [
            'status' => 'error',
            'message' => 'Test user not found',
        ];
    }
} catch (\Exception $e) {
    $results['user_check'] = [
        'status' => 'error',
        'message' => $e->getMessage(),
    ];
}

// Step 2: Test login via API
$loginUrl = 'https://api.askproai.de/business/api/login';
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $testEmail,
    'password' => $testPassword,
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/portal_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/portal_cookies.txt');

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$results['login_test'] = [
    'status' => $httpCode === 200 ? 'success' : 'error',
    'http_code' => $httpCode,
    'response' => json_decode($body, true),
    'cookies_set' => strpos($headers, 'Set-Cookie') !== false,
    'headers' => $headers,
];

// Step 3: Test session persistence
sleep(1); // Give session time to save

$dashboardUrl = 'https://api.askproai.de/business/api/user';
$ch = curl_init($dashboardUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/portal_cookies.txt');

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$results['session_test'] = [
    'status' => $httpCode === 200 ? 'success' : 'error',
    'http_code' => $httpCode,
    'response' => json_decode($body, true),
];

// Step 4: Analyze cookies
$cookieContent = file_exists('/tmp/portal_cookies.txt') ? file_get_contents('/tmp/portal_cookies.txt') : '';
$results['cookie_analysis'] = [
    'file_exists' => file_exists('/tmp/portal_cookies.txt'),
    'content' => $cookieContent,
    'has_session_cookie' => strpos($cookieContent, 'askproai_session') !== false || strpos($cookieContent, 'askproai_portal_session') !== false,
];

// Step 5: Direct session check
session_start();
$results['php_session'] = [
    'id' => session_id(),
    'save_path' => session_save_path(),
    'data' => $_SESSION,
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal Session Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 3px solid #3490dc; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .test-result {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            border-left: 5px solid #48bb78;
            background: #f0fff4;
        }
        .error {
            border-left: 5px solid #f56565;
            background: #fff5f5;
        }
        .warning {
            border-left: 5px solid #ed8936;
            background: #fffaf0;
        }
        pre {
            background: #2d3748;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-success { background: #48bb78; color: white; }
        .status-error { background: #f56565; color: white; }
        .recommendation {
            background: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .recommendation h3 { margin-top: 0; color: #2d3748; }
        .code-block {
            background: #2d3748;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 Business Portal Session Test</h1>
        <p>Testing login flow and session persistence for the Business Portal</p>

        <h2>Test 1: User Check</h2>
        <div class="test-result <?= $results['user_check']['status'] === 'success' ? 'success' : 'error' ?>">
            <span class="status-badge status-<?= $results['user_check']['status'] ?>">
                <?= $results['user_check']['status'] ?>
            </span>
            <p><?= $results['user_check']['message'] ?></p>
            <?php if ($results['user_check']['status'] === 'success'): ?>
                <p>User ID: <?= $results['user_check']['user_id'] ?></p>
                <p>Company ID: <?= $results['user_check']['company_id'] ?></p>
            <?php endif; ?>
        </div>

        <h2>Test 2: Login API</h2>
        <div class="test-result <?= $results['login_test']['status'] === 'success' ? 'success' : 'error' ?>">
            <span class="status-badge status-<?= $results['login_test']['status'] ?>">
                <?= $results['login_test']['status'] ?>
            </span>
            <p>HTTP Code: <?= $results['login_test']['http_code'] ?></p>
            <p>Cookies Set: <?= $results['login_test']['cookies_set'] ? 'YES' : 'NO' ?></p>
            
            <h4>Response:</h4>
            <pre><?= htmlspecialchars(json_encode($results['login_test']['response'], JSON_PRETTY_PRINT)) ?></pre>
            
            <h4>Headers:</h4>
            <pre><?= htmlspecialchars($results['login_test']['headers']) ?></pre>
        </div>

        <h2>Test 3: Session Persistence</h2>
        <div class="test-result <?= $results['session_test']['status'] === 'success' ? 'success' : 'error' ?>">
            <span class="status-badge status-<?= $results['session_test']['status'] ?>">
                <?= $results['session_test']['status'] ?>
            </span>
            <p>HTTP Code: <?= $results['session_test']['http_code'] ?></p>
            
            <h4>Response:</h4>
            <pre><?= htmlspecialchars(json_encode($results['session_test']['response'], JSON_PRETTY_PRINT)) ?></pre>
        </div>

        <h2>Test 4: Cookie Analysis</h2>
        <div class="test-result <?= $results['cookie_analysis']['has_session_cookie'] ? 'success' : 'error' ?>">
            <p>Cookie File Exists: <?= $results['cookie_analysis']['file_exists'] ? 'YES' : 'NO' ?></p>
            <p>Has Session Cookie: <?= $results['cookie_analysis']['has_session_cookie'] ? 'YES' : 'NO' ?></p>
            
            <h4>Cookie Content:</h4>
            <pre><?= htmlspecialchars($results['cookie_analysis']['content'] ?: '(empty)') ?></pre>
        </div>

        <h2>🎯 Diagnosis</h2>
        <?php
        $loginSuccess = $results['login_test']['status'] === 'success';
        $sessionSuccess = $results['session_test']['status'] === 'success';
        $hasCookie = $results['cookie_analysis']['has_session_cookie'];
        ?>

        <?php if ($loginSuccess && $sessionSuccess && $hasCookie): ?>
            <div class="recommendation success">
                <h3>✅ Everything is working correctly!</h3>
                <p>The session is being created and persisted properly.</p>
            </div>
        <?php elseif ($loginSuccess && !$sessionSuccess): ?>
            <div class="recommendation error">
                <h3>❌ Session not persisting</h3>
                <p>Login succeeds but the session is not maintained for subsequent requests.</p>
                
                <h4>Possible causes:</h4>
                <ul>
                    <li>Session domain mismatch</li>
                    <li>Cookie security settings incompatible with current protocol</li>
                    <li>Session driver issues</li>
                    <li>Middleware not applying session correctly</li>
                </ul>

                <h4>Recommended fixes:</h4>
                <div class="code-block">
# Update .env file:
SESSION_DRIVER=file
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# Clear caches:
php artisan config:clear
php artisan cache:clear
                </div>
            </div>
        <?php elseif (!$loginSuccess): ?>
            <div class="recommendation error">
                <h3>❌ Login failing</h3>
                <p>The login request itself is failing.</p>
                
                <h4>Check:</h4>
                <ul>
                    <li>User credentials are correct</li>
                    <li>User exists in portal_users table</li>
                    <li>Password is hashed correctly</li>
                    <li>API route is accessible</li>
                </ul>
            </div>
        <?php endif; ?>

        <h2>🛠️ Quick Debug Commands</h2>
        <div class="code-block">
# Check current session configuration
php artisan tinker
>>> config('session')

# Test login directly
curl -c cookies.txt -X POST https://api.askproai.de/business/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@askproai.de","password":"password123"}'

# Test with saved cookies
curl -b cookies.txt https://api.askproai.de/business/api/user

# Check Laravel logs
tail -f storage/logs/laravel.log | grep -i session
        </div>

        <h2>📝 Environment Info</h2>
        <div class="test-result">
            <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
            <p><strong>Laravel Session Driver:</strong> <?= config('session.driver') ?></p>
            <p><strong>Session Domain:</strong> <?= config('session.domain') ?: '(not set)' ?></p>
            <p><strong>Secure Cookie:</strong> <?= config('session.secure') ? 'YES' : 'NO' ?></p>
            <p><strong>HTTP Only:</strong> <?= config('session.http_only') ? 'YES' : 'NO' ?></p>
            <p><strong>Same Site:</strong> <?= config('session.same_site') ?: '(not set)' ?></p>
        </div>
    </div>
</body>
</html>