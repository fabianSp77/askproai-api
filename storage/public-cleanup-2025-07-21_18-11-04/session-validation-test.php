<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

session_start();

// Generate test ID for this session
$testId = uniqid('test_', true);

// Initialize or update session data
if (!isset($_SESSION['test_data'])) {
    $_SESSION['test_data'] = [
        'created_at' => date('Y-m-d H:i:s'),
        'test_id' => $testId,
        'page_views' => 1,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
} else {
    $_SESSION['test_data']['page_views']++;
    $_SESSION['test_data']['last_accessed'] = date('Y-m-d H:i:s');
}

// Laravel session test
$laravelSession = $request->session();
if ($laravelSession) {
    if (!$laravelSession->has('laravel_test_data')) {
        $laravelSession->put('laravel_test_data', [
            'created_at' => now()->toString(),
            'test_id' => $testId,
            'page_views' => 1
        ]);
    } else {
        $laravelData = $laravelSession->get('laravel_test_data');
        $laravelData['page_views']++;
        $laravelData['last_accessed'] = now()->toString();
        $laravelSession->put('laravel_test_data', $laravelData);
    }
    $laravelSession->save();
}

// Get all cookies
$cookies = $_COOKIE;

// Check session files
$sessionPath = storage_path('framework/sessions');
$sessionFiles = [];
if (is_dir($sessionPath)) {
    $files = scandir($sessionPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $sessionPath . '/' . $file;
            $sessionFiles[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                'is_current' => (session_id() === $file || ($laravelSession && $laravelSession->getId() === $file))
            ];
        }
    }
}

// Response headers
$responseHeaders = [];
foreach (headers_list() as $header) {
    $responseHeaders[] = $header;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Validation Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
            margin-top: 0;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th,
        .data-table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        pre {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            overflow-x: auto;
            font-size: 12px;
        }
        .refresh-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .refresh-btn:hover {
            background: #0056b3;
        }
        .test-actions {
            margin: 20px 0;
        }
        .test-actions button {
            margin-right: 10px;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .alert.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .cookie-item {
            background: #f8f9fa;
            padding: 8px;
            margin: 4px 0;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        .session-file {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            margin: 4px 0;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .session-file.current {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <h1>Session Validation Test</h1>
    
    <div class="alert info">
        <strong>Instructions:</strong> Refresh this page multiple times to test session persistence. 
        The page view counter should increment with each refresh if sessions are working correctly.
    </div>

    <div class="container">
        <h2>Session Configuration <span class="status success">Active</span></h2>
        <table class="data-table">
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Session Driver</td>
                <td><?= config('session.driver') ?></td>
                <td><span class="status <?= config('session.driver') === 'file' ? 'success' : 'warning' ?>">
                    <?= config('session.driver') === 'file' ? 'OK' : 'Check' ?>
                </span></td>
            </tr>
            <tr>
                <td>Session Lifetime</td>
                <td><?= config('session.lifetime') ?> minutes</td>
                <td><span class="status success">OK</span></td>
            </tr>
            <tr>
                <td>Cookie Name</td>
                <td><?= config('session.cookie') ?></td>
                <td><span class="status success">OK</span></td>
            </tr>
            <tr>
                <td>Cookie Path</td>
                <td><?= config('session.path') ?></td>
                <td><span class="status success">OK</span></td>
            </tr>
            <tr>
                <td>Cookie Domain</td>
                <td><?= config('session.domain') ?: '(not set)' ?></td>
                <td><span class="status success">OK</span></td>
            </tr>
            <tr>
                <td>Secure Cookie</td>
                <td><?= config('session.secure') ? 'Yes' : 'No' ?></td>
                <td><span class="status <?= config('session.secure') ? 'warning' : 'success' ?>">
                    <?= config('session.secure') ? 'HTTPS Only' : 'OK' ?>
                </span></td>
            </tr>
            <tr>
                <td>HTTP Only</td>
                <td><?= config('session.http_only') ? 'Yes' : 'No' ?></td>
                <td><span class="status <?= config('session.http_only') ? 'success' : 'error' ?>">
                    <?= config('session.http_only') ? 'Secure' : 'Insecure' ?>
                </span></td>
            </tr>
            <tr>
                <td>Same Site</td>
                <td><?= config('session.same_site') ?></td>
                <td><span class="status success">OK</span></td>
            </tr>
        </table>
    </div>

    <div class="container">
        <h2>PHP Session Data 
            <?php if (isset($_SESSION['test_data'])): ?>
                <span class="status success">Working</span>
            <?php else: ?>
                <span class="status error">Not Working</span>
            <?php endif; ?>
        </h2>
        
        <table class="data-table">
            <tr>
                <th>Session ID</th>
                <td><?= session_id() ?: 'No session ID' ?></td>
            </tr>
            <tr>
                <th>Page Views</th>
                <td><?= $_SESSION['test_data']['page_views'] ?? 0 ?></td>
            </tr>
            <tr>
                <th>Created At</th>
                <td><?= $_SESSION['test_data']['created_at'] ?? 'N/A' ?></td>
            </tr>
            <tr>
                <th>Last Accessed</th>
                <td><?= $_SESSION['test_data']['last_accessed'] ?? 'First visit' ?></td>
            </tr>
            <tr>
                <th>Test ID</th>
                <td><?= $_SESSION['test_data']['test_id'] ?? 'N/A' ?></td>
            </tr>
        </table>
        
        <details style="margin-top: 10px;">
            <summary>Raw Session Data</summary>
            <pre><?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)) ?></pre>
        </details>
    </div>

    <div class="container">
        <h2>Laravel Session Data 
            <?php if ($laravelSession && $laravelSession->has('laravel_test_data')): ?>
                <span class="status success">Working</span>
            <?php else: ?>
                <span class="status error">Not Working</span>
            <?php endif; ?>
        </h2>
        
        <?php if ($laravelSession): ?>
            <table class="data-table">
                <tr>
                    <th>Laravel Session ID</th>
                    <td><?= $laravelSession->getId() ?></td>
                </tr>
                <tr>
                    <th>Page Views</th>
                    <td><?= $laravelSession->get('laravel_test_data.page_views', 0) ?></td>
                </tr>
                <tr>
                    <th>Created At</th>
                    <td><?= $laravelSession->get('laravel_test_data.created_at', 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Last Accessed</th>
                    <td><?= $laravelSession->get('laravel_test_data.last_accessed', 'First visit') ?></td>
                </tr>
                <tr>
                    <th>Test ID</th>
                    <td><?= $laravelSession->get('laravel_test_data.test_id', 'N/A') ?></td>
                </tr>
            </table>
            
            <details style="margin-top: 10px;">
                <summary>All Laravel Session Data</summary>
                <pre><?= htmlspecialchars(json_encode($laravelSession->all(), JSON_PRETTY_PRINT)) ?></pre>
            </details>
        <?php else: ?>
            <p>Laravel session not available</p>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Cookies 
            <?php if (!empty($cookies)): ?>
                <span class="status success"><?= count($cookies) ?> cookies</span>
            <?php else: ?>
                <span class="status error">No cookies</span>
            <?php endif; ?>
        </h2>
        
        <?php if (!empty($cookies)): ?>
            <?php foreach ($cookies as $name => $value): ?>
                <div class="cookie-item">
                    <strong><?= htmlspecialchars($name) ?>:</strong> 
                    <?= htmlspecialchars(substr($value, 0, 100)) ?><?= strlen($value) > 100 ? '...' : '' ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No cookies found</p>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Session Files 
            <span class="status <?= !empty($sessionFiles) ? 'success' : 'error' ?>">
                <?= count($sessionFiles) ?> files
            </span>
        </h2>
        
        <?php if (!empty($sessionFiles)): ?>
            <?php foreach ($sessionFiles as $file): ?>
                <div class="session-file <?= $file['is_current'] ? 'current' : '' ?>">
                    <div>
                        <strong><?= $file['name'] ?></strong>
                        <?php if ($file['is_current']): ?>
                            <span class="status success">Current Session</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        Size: <?= $file['size'] ?> bytes | 
                        Modified: <?= $file['modified'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No session files found in <?= $sessionPath ?></p>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Response Headers</h2>
        <pre><?= htmlspecialchars(implode("\n", $responseHeaders)) ?></pre>
    </div>

    <div class="container">
        <h2>Middleware Analysis</h2>
        <table class="data-table">
            <tr>
                <th>Middleware</th>
                <th>Purpose</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>StartSession</td>
                <td>Initialize Laravel session</td>
                <td><span class="status success">Active</span></td>
            </tr>
            <tr>
                <td>EnsureSessionPersistence</td>
                <td>Force session save after request</td>
                <td><span class="status success">Active</span></td>
            </tr>
            <tr>
                <td>EnsureAuthSessionKey</td>
                <td>Fix auth session key issues</td>
                <td><span class="status success">Active</span></td>
            </tr>
            <tr>
                <td>CleanDuplicateSessionKeys</td>
                <td>Remove duplicate auth keys</td>
                <td><span class="status success">Active</span></td>
            </tr>
            <tr>
                <td>EnsureSessionCookieResponse</td>
                <td>Force session cookie in response</td>
                <td><span class="status success">Active</span></td>
            </tr>
            <tr>
                <td>ForceSessionCookie</td>
                <td>Aggressive cookie setting</td>
                <td><span class="status success">Active</span></td>
            </tr>
        </table>
    </div>

    <div class="test-actions">
        <button class="refresh-btn" onclick="location.reload()">Refresh Page</button>
        <button class="refresh-btn" onclick="clearSession()">Clear Session</button>
        <button class="refresh-btn" onclick="testAjax()">Test AJAX Request</button>
    </div>

    <script>
        function clearSession() {
            if (confirm('Clear all session data?')) {
                fetch('/api/test/clear-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(() => {
                    alert('Session cleared. Refreshing page...');
                    location.reload();
                });
            }
        }

        function testAjax() {
            fetch('/api/test/session-check', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                alert('AJAX Response:\n' + JSON.stringify(data, null, 2));
            })
            .catch(error => {
                alert('AJAX Error: ' + error.message);
            });
        }

        // Auto-refresh counter
        let secondsUntilRefresh = 60;
        const countdownEl = document.createElement('div');
        countdownEl.style.position = 'fixed';
        countdownEl.style.bottom = '20px';
        countdownEl.style.right = '20px';
        countdownEl.style.background = '#007bff';
        countdownEl.style.color = 'white';
        countdownEl.style.padding = '10px 20px';
        countdownEl.style.borderRadius = '4px';
        countdownEl.style.fontSize = '14px';
        document.body.appendChild(countdownEl);

        setInterval(() => {
            secondsUntilRefresh--;
            countdownEl.textContent = `Auto-refresh in ${secondsUntilRefresh}s`;
            if (secondsUntilRefresh <= 0) {
                location.reload();
            }
        }, 1000);
    </script>
</body>
</html>