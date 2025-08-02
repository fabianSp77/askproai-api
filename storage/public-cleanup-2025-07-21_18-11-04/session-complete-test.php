<?php
// Initialize Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Create a test route handler
$app->make('router')->get('/session-test-internal', function () use ($request) {
    $session = $request->session();
    
    // Initialize counter
    if (!$session->has('test_counter')) {
        $session->put('test_counter', 0);
        $session->put('created_at', now()->toString());
    }
    
    // Increment counter
    $counter = $session->get('test_counter', 0) + 1;
    $session->put('test_counter', $counter);
    $session->put('last_visit', now()->toString());
    
    // Force save
    $session->save();
    
    return response()->json([
        'session_id' => $session->getId(),
        'counter' => $counter,
        'created_at' => $session->get('created_at'),
        'last_visit' => $session->get('last_visit'),
        'all_data' => $session->all()
    ]);
});

// Handle the request
$response = $kernel->handle($request);
$content = json_decode($response->getContent(), true);

// Terminate properly
$kernel->terminate($request, $response);

// Also test native PHP session
session_start();
if (!isset($_SESSION['native_counter'])) {
    $_SESSION['native_counter'] = 0;
}
$_SESSION['native_counter']++;

// Collect diagnostics
$issues = [];
$warnings = [];
$successes = [];

// Check session configuration
if (config('session.driver') !== 'file') {
    $warnings[] = "Session driver is '" . config('session.driver') . "', expected 'file'";
}

// Check session file existence
$sessionId = $content['session_id'] ?? null;
if ($sessionId) {
    $sessionFile = storage_path('framework/sessions/' . $sessionId);
    if (file_exists($sessionFile)) {
        $successes[] = "Laravel session file exists at: " . $sessionFile;
    } else {
        $issues[] = "Laravel session file missing at: " . $sessionFile;
    }
} else {
    $issues[] = "No Laravel session ID returned";
}

// Check session persistence
if (($content['counter'] ?? 0) > 1) {
    $successes[] = "Laravel session is persisting (counter: " . $content['counter'] . ")";
} else if (($content['counter'] ?? 0) == 1) {
    $warnings[] = "First Laravel session visit - refresh to test persistence";
} else {
    $issues[] = "Laravel session counter not working";
}

// Check native PHP session
if ($_SESSION['native_counter'] > 1) {
    $successes[] = "Native PHP session is persisting (counter: " . $_SESSION['native_counter'] . ")";
} else if ($_SESSION['native_counter'] == 1) {
    $warnings[] = "First PHP session visit - refresh to test persistence";
}

// Check cookies
$cookies = $_COOKIE;
$laravelCookie = $_COOKIE[config('session.cookie')] ?? null;
$phpCookie = $_COOKIE[session_name()] ?? null;

if ($laravelCookie) {
    $successes[] = "Laravel session cookie found: " . config('session.cookie');
} else {
    $issues[] = "Laravel session cookie missing: " . config('session.cookie');
}

if ($phpCookie) {
    $successes[] = "PHP session cookie found: " . session_name();
} else {
    $warnings[] = "PHP session cookie missing: " . session_name();
}

// Check response headers
$responseHeaders = [];
foreach ($response->headers->all() as $name => $values) {
    $responseHeaders[$name] = implode(', ', $values);
}

$hasCookieHeader = isset($responseHeaders['set-cookie']);
if ($hasCookieHeader) {
    $successes[] = "Response contains Set-Cookie header";
} else {
    $warnings[] = "No Set-Cookie header in response";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Session Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2 {
            margin-top: 0;
            color: #333;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .status-item {
            padding: 15px;
            border-radius: 5px;
            border: 1px solid;
        }
        .success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }
        code {
            background: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }
        .button-group {
            margin: 20px 0;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover {
            background: #0056b3;
        }
        .counter {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .counter.laravel {
            color: #f05340;
        }
        .counter.php {
            color: #8892be;
        }
        ul {
            margin: 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>Complete Session Test</h1>

    <div class="card">
        <h2>Session Status Overview</h2>
        <div class="status-grid">
            <div class="status-item <?= empty($issues) ? 'success' : 'error' ?>">
                <h3>Issues (<?= count($issues) ?>)</h3>
                <?php if (empty($issues)): ?>
                    <p>No critical issues found!</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($issues as $issue): ?>
                            <li><?= htmlspecialchars($issue) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="status-item <?= empty($warnings) ? 'info' : 'warning' ?>">
                <h3>Warnings (<?= count($warnings) ?>)</h3>
                <?php if (empty($warnings)): ?>
                    <p>No warnings</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($warnings as $warning): ?>
                            <li><?= htmlspecialchars($warning) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <div class="status-item success">
                <h3>Working (<?= count($successes) ?>)</h3>
                <ul>
                    <?php foreach ($successes as $success): ?>
                        <li><?= htmlspecialchars($success) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Session Counters</h2>
        <div class="status-grid">
            <div>
                <h3>Laravel Session</h3>
                <div class="counter laravel"><?= $content['counter'] ?? 0 ?></div>
                <p>Session ID: <code><?= htmlspecialchars($content['session_id'] ?? 'none') ?></code></p>
                <p>Created: <?= htmlspecialchars($content['created_at'] ?? 'N/A') ?></p>
                <p>Last Visit: <?= htmlspecialchars($content['last_visit'] ?? 'N/A') ?></p>
            </div>
            
            <div>
                <h3>PHP Native Session</h3>
                <div class="counter php"><?= $_SESSION['native_counter'] ?></div>
                <p>Session ID: <code><?= htmlspecialchars(session_id()) ?></code></p>
                <p>Session Name: <code><?= htmlspecialchars(session_name()) ?></code></p>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Cookies</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td><code><?= htmlspecialchars(config('session.cookie')) ?></code></td>
                <td><code><?= $laravelCookie ? htmlspecialchars(substr($laravelCookie, 0, 20)) . '...' : '(not set)' ?></code></td>
                <td><?= $laravelCookie ? '<span class="success">✓ Present</span>' : '<span class="error">✗ Missing</span>' ?></td>
            </tr>
            <tr>
                <td><code><?= htmlspecialchars(session_name()) ?></code></td>
                <td><code><?= $phpCookie ? htmlspecialchars(substr($phpCookie, 0, 20)) . '...' : '(not set)' ?></code></td>
                <td><?= $phpCookie ? '<span class="success">✓ Present</span>' : '<span class="warning">✗ Missing</span>' ?></td>
            </tr>
            <?php foreach ($cookies as $name => $value): ?>
                <?php if ($name !== config('session.cookie') && $name !== session_name()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($name) ?></code></td>
                    <td><code><?= htmlspecialchars(substr($value, 0, 20)) ?>...</code></td>
                    <td><span class="info">Other</span></td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>Response Headers</h2>
        <table>
            <tr>
                <th>Header</th>
                <th>Value</th>
            </tr>
            <?php foreach ($responseHeaders as $name => $value): ?>
            <tr>
                <td><code><?= htmlspecialchars($name) ?></code></td>
                <td><code><?= htmlspecialchars($value) ?></code></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>Session Configuration</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Driver</td>
                <td><code><?= htmlspecialchars(config('session.driver')) ?></code></td>
            </tr>
            <tr>
                <td>Lifetime</td>
                <td><?= config('session.lifetime') ?> minutes</td>
            </tr>
            <tr>
                <td>Cookie Name</td>
                <td><code><?= htmlspecialchars(config('session.cookie')) ?></code></td>
            </tr>
            <tr>
                <td>Path</td>
                <td><code><?= htmlspecialchars(config('session.path')) ?></code></td>
            </tr>
            <tr>
                <td>Domain</td>
                <td><code><?= htmlspecialchars(config('session.domain') ?: '(not set)') ?></code></td>
            </tr>
            <tr>
                <td>Secure</td>
                <td><?= config('session.secure') ? 'Yes' : 'No' ?></td>
            </tr>
            <tr>
                <td>HTTP Only</td>
                <td><?= config('session.http_only') ? 'Yes' : 'No' ?></td>
            </tr>
            <tr>
                <td>Same Site</td>
                <td><code><?= htmlspecialchars(config('session.same_site')) ?></code></td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2>Laravel Session Data</h2>
        <pre><?= htmlspecialchars(json_encode($content['all_data'] ?? [], JSON_PRETTY_PRINT)) ?></pre>
    </div>

    <div class="button-group">
        <button onclick="location.reload()">Refresh Page</button>
        <button onclick="testAjax()">Test AJAX</button>
        <button onclick="clearSessions()">Clear All Sessions</button>
    </div>

    <script>
        function testAjax() {
            fetch('/api/test/session-check', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('AJAX Session Check:\n' + JSON.stringify(data, null, 2));
            })
            .catch(error => {
                alert('AJAX Error: ' + error.message);
            });
        }

        function clearSessions() {
            if (confirm('Clear all sessions?')) {
                fetch('/api/test/clear-session', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(() => {
                    alert('Sessions cleared. Refreshing...');
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>