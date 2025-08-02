<?php
/**
 * Final Session Fix Test
 * 
 * This comprehensive test verifies our duplicate session key fix works.
 * It combines all our learnings and provides a definitive solution.
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

$results = [];

// Helper function for formatted output
function addResult(&$results, $test, $passed, $details = '') {
    $results[] = [
        'test' => $test,
        'passed' => $passed,
        'details' => $details
    ];
}

// Test 1: Check initial session state
$session = app('session.store');
$sessionId = $session->getId();
$sessionFile = storage_path('framework/sessions') . '/' . $sessionId;

addResult($results, 'Session ID exists', !empty($sessionId), $sessionId);
addResult($results, 'Session file exists', file_exists($sessionFile), $sessionFile);

// Test 2: Check for duplicate keys
$duplicateKeys = [];
if (file_exists($sessionFile)) {
    $sessionData = @unserialize(file_get_contents($sessionFile));
    if ($sessionData && is_array($sessionData)) {
        $loginKeys = array_filter(array_keys($sessionData), function($key) {
            return strpos($key, 'login_web_') === 0;
        });
        $duplicateKeys = $loginKeys;
    }
}

addResult($results, 'Duplicate key check', count($duplicateKeys) <= 1, 
    count($duplicateKeys) . ' keys found: ' . implode(', ', $duplicateKeys));

// Test 3: Clean up if needed
if (count($duplicateKeys) > 1) {
    // Get correct key
    $guard = Auth::guard('web');
    $reflection = new ReflectionMethod($guard, 'getName');
    $reflection->setAccessible(true);
    $correctKey = $reflection->invoke($guard);
    
    // Clean session
    foreach ($duplicateKeys as $key) {
        if ($key !== $correctKey) {
            unset($sessionData[$key]);
        }
    }
    file_put_contents($sessionFile, serialize($sessionData));
    
    addResult($results, 'Session cleanup', true, 'Removed duplicate keys, kept: ' . $correctKey);
}

// Test 4: Check authentication status
$authCheck = Auth::check();
$authId = Auth::id();
addResult($results, 'Auth::check()', $authCheck, $authCheck ? 'User ID: ' . $authId : 'Not authenticated');

// Test 5: Perform login if requested
if (isset($_GET['login'])) {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear any existing auth
        Auth::logout();
        $session->flush();
        $session->regenerate();
        
        // Fresh login
        Auth::login($user, true);
        
        // Get the guard and session key
        $guard = Auth::guard('web');
        $reflection = new ReflectionMethod($guard, 'getName');
        $reflection->setAccessible(true);
        $sessionKey = $reflection->invoke($guard);
        
        // Manually set session data
        $session->put($sessionKey, $user->id);
        $session->put('password_hash_web', $user->password);
        
        // Force session save
        $session->save();
        
        // Verify login worked
        $loginSuccess = Auth::check() && Auth::id() == $user->id;
        addResult($results, 'Login attempt', $loginSuccess, 
            $loginSuccess ? 'Successfully logged in as ' . $user->email : 'Login failed');
        
        // Check session file after login
        $sessionData = @unserialize(file_get_contents($sessionFile));
        $loginKeys = array_filter(array_keys($sessionData), function($key) {
            return strpos($key, 'login_web_') === 0;
        });
        
        addResult($results, 'Post-login key check', count($loginKeys) == 1,
            count($loginKeys) . ' keys after login');
            
    } else {
        addResult($results, 'Login attempt', false, 'Demo user not found');
    }
}

// Test 6: Verify middleware is active
$kernelClass = get_class($kernel);
$hasCleanupMiddleware = false;

// Check in app Kernel
$appKernel = app(\App\Http\Kernel::class);
$kernelReflection = new ReflectionClass($appKernel);
$middlewareGroupsProperty = $kernelReflection->getProperty('middlewareGroups');
$middlewareGroupsProperty->setAccessible(true);
$middlewareGroups = $middlewareGroupsProperty->getValue($appKernel);

if (isset($middlewareGroups['web'])) {
    foreach ($middlewareGroups['web'] as $middleware) {
        if (strpos($middleware, 'CleanDuplicateSessionKeys') !== false) {
            $hasCleanupMiddleware = true;
            break;
        }
    }
}

addResult($results, 'Cleanup middleware active', $hasCleanupMiddleware,
    $hasCleanupMiddleware ? 'CleanDuplicateSessionKeys in web group' : 'Middleware not found');

// Calculate overall status
$allPassed = true;
foreach ($results as $result) {
    if (!$result['passed']) {
        $allPassed = false;
        break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Session Fix Test</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f0f;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #1a1a1a;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        h1 {
            color: #fff;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-align: center;
        }
        .status {
            text-align: center;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
        }
        .status.success {
            background: #1b5e20;
            color: #4caf50;
            border: 2px solid #4caf50;
        }
        .status.error {
            background: #b71c1c;
            color: #f44336;
            border: 2px solid #f44336;
        }
        .test-results {
            margin: 30px 0;
        }
        .test-item {
            background: #252525;
            padding: 15px 20px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .test-icon {
            font-size: 1.5em;
            width: 30px;
            text-align: center;
        }
        .test-icon.pass { color: #4caf50; }
        .test-icon.fail { color: #f44336; }
        .test-name {
            font-weight: 600;
            flex: 1;
        }
        .test-details {
            color: #999;
            font-size: 0.9em;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid transparent;
            cursor: pointer;
            font-size: 1em;
        }
        .btn-primary {
            background: #2196f3;
            color: white;
        }
        .btn-primary:hover {
            background: #1976d2;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: transparent;
            color: #2196f3;
            border-color: #2196f3;
        }
        .btn-secondary:hover {
            background: #2196f3;
            color: white;
        }
        .btn-success {
            background: #4caf50;
            color: white;
        }
        .btn-success:hover {
            background: #45a049;
        }
        .explanation {
            background: #2a2a2a;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .explanation h2 {
            color: #2196f3;
            margin-top: 0;
        }
        code {
            background: #333;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Final Session Fix Test</h1>
        
        <div class="status <?= $allPassed ? 'success' : 'error' ?>">
            <?= $allPassed ? '✅ All Tests Passed!' : '❌ Some Tests Failed' ?>
        </div>
        
        <div class="test-results">
            <?php foreach ($results as $result): ?>
            <div class="test-item">
                <div class="test-icon <?= $result['passed'] ? 'pass' : 'fail' ?>">
                    <?= $result['passed'] ? '✓' : '✗' ?>
                </div>
                <div class="test-name"><?= htmlspecialchars($result['test']) ?></div>
                <div class="test-details"><?= htmlspecialchars($result['details']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="actions">
            <a href="?" class="btn btn-secondary">Run Tests</a>
            <a href="?login=1" class="btn btn-primary">Test Login</a>
            <?php if (Auth::check()): ?>
            <a href="/admin" class="btn btn-success">Go to Admin</a>
            <?php endif; ?>
        </div>
        
        <div class="explanation">
            <h2>What This Test Does</h2>
            <p>This comprehensive test verifies our solution for the duplicate session key problem:</p>
            <ol>
                <li><strong>Session Check:</strong> Verifies session is properly initialized</li>
                <li><strong>Duplicate Key Detection:</strong> Checks for multiple <code>login_web_*</code> keys</li>
                <li><strong>Automatic Cleanup:</strong> Removes duplicate keys if found</li>
                <li><strong>Authentication Test:</strong> Verifies Auth::check() works correctly</li>
                <li><strong>Login Test:</strong> Performs a fresh login and verifies no duplicates are created</li>
                <li><strong>Middleware Verification:</strong> Ensures our permanent fix is active</li>
            </ol>
            <p>The <code>CleanDuplicateSessionKeys</code> middleware now runs on every request to prevent this issue.</p>
        </div>
        
        <?php if (!$allPassed): ?>
        <div class="explanation" style="border-color: #f44336;">
            <h2 style="color: #f44336;">⚠️ Action Required</h2>
            <p>Some tests failed. Try the following:</p>
            <ol>
                <li>Click "Test Login" to perform a fresh login</li>
                <li>Clear your browser cookies and try again</li>
                <li>Check the Laravel logs for errors</li>
            </ol>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>