<?php
/**
 * Fix Headers Already Sent Issue
 * 
 * This ensures no output is sent before session operations
 */

// Start output buffering IMMEDIATELY
ob_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// CRITICAL: Don't send response yet!
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Now we can work with session
use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';
$message = '';
$messageType = '';

if ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Clear and login
        Auth::logout();
        session()->flush();
        session()->regenerate();
        
        Auth::login($user, true);
        session()->save();
        
        // Redirect to check if session persists
        header('Location: ?action=check');
        exit;
    }
} elseif ($action === 'check') {
    if (Auth::check()) {
        $message = 'Success! Logged in as: ' . Auth::user()->email;
        $messageType = 'success';
    } else {
        $message = 'Failed - session did not persist';
        $messageType = 'error';
    }
}

// Get all output
$output = ob_get_clean();

// Now create proper response
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Headers Already Sent</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success { color: green; }
        .error { color: red; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
        }
    </style>
</head>
<body>
    <h1>Fix Headers Already Sent Issue</h1>
    
    <?php if ($message): ?>
        <div class="box">
            <p class="<?= $messageType ?>"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="box">
        <h2>Current Status</h2>
        <table>
            <tr>
                <th>Check</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Auth::check()</td>
                <td><?= Auth::check() ? '<span class="success">TRUE</span>' : '<span class="error">FALSE</span>' ?></td>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><?= session()->getId() ?></td>
            </tr>
            <tr>
                <td>Headers Sent</td>
                <td><?= headers_sent($file, $line) ? "Yes from $file:$line" : '<span class="success">No</span>' ?></td>
            </tr>
            <tr>
                <td>Output Buffering</td>
                <td><?= ob_get_level() > 0 ? 'Active' : 'Not Active' ?></td>
            </tr>
            <tr>
                <td>Session Cookie</td>
                <td><?= isset($_COOKIE[config('session.cookie')]) ? 'Present' : 'Missing' ?></td>
            </tr>
        </table>
        
        <?php if (Auth::check()): ?>
            <p><strong>User:</strong> <?= Auth::user()->email ?></p>
            <a href="/admin" class="button" style="background: green;">Go to Admin Panel</a>
        <?php else: ?>
            <a href="?action=login" class="button">Test Login</a>
        <?php endif; ?>
    </div>
    
    <?php if ($output): ?>
        <div class="box">
            <h2>Captured Output (that would have broken headers)</h2>
            <pre><?= htmlspecialchars($output) ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>
<?php
// Send the response properly
$response->setContent(ob_get_contents());
ob_end_clean();
$response->send();
$kernel->terminate($request, $response);
?>