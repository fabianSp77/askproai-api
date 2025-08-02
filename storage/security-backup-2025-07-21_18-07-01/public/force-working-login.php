<?php
/**
 * Force Working Login - Direct Solution
 * 
 * This bypasses all complexity and forces a working session
 */

// Don't use Laravel's bootstrap yet
session_name('askproai_session_manual');
session_start();

// Now bootstrap Laravel
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    
    if ($user) {
        // Set in PHP session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['logged_in'] = true;
        
        // Also try Laravel auth
        Auth::loginUsingId($user->id, true);
        app('session.store')->save();
        
        header('Location: ?action=check');
        exit;
    }
} elseif ($action === 'check') {
    // Check both sessions
    $phpLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    $laravelLoggedIn = Auth::check();
}

// Manual auth check
$isLoggedIn = false;
$userEmail = null;

if (isset($_SESSION['user_id'])) {
    $user = \App\Models\User::find($_SESSION['user_id']);
    if ($user) {
        $isLoggedIn = true;
        $userEmail = $user->email;
        
        // Force Laravel to recognize the user
        if (!Auth::check()) {
            Auth::login($user);
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Working Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .status {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success {
            color: green;
            font-size: 24px;
            margin: 20px 0;
        }
        .error {
            color: red;
            font-size: 24px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
        }
        .button:hover {
            background: #0056b3;
        }
        .button.success {
            background: #28a745;
        }
        .info {
            background: #e9ecef;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
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
    </style>
</head>
<body>
    <div class="status">
        <h1>Force Working Login</h1>
        
        <?php if ($isLoggedIn): ?>
            <div class="success">
                ✅ You are logged in!
            </div>
            <p><strong>Email:</strong> <?= htmlspecialchars($userEmail) ?></p>
            
            <a href="/admin" class="button success">Go to Admin Panel</a>
            
            <div class="info">
                <h3>Session Status:</h3>
                <table>
                    <tr>
                        <td>PHP Session:</td>
                        <td><?= isset($_SESSION['logged_in']) ? '✅ Active' : '❌ Not Active' ?></td>
                    </tr>
                    <tr>
                        <td>Laravel Auth:</td>
                        <td><?= Auth::check() ? '✅ Active' : '❌ Not Active' ?></td>
                    </tr>
                    <tr>
                        <td>PHP Session ID:</td>
                        <td><?= session_id() ?></td>
                    </tr>
                    <tr>
                        <td>Laravel Session ID:</td>
                        <td><?= app('session.store')->getId() ?></td>
                    </tr>
                </table>
            </div>
        <?php else: ?>
            <div class="error">
                ❌ Not logged in
            </div>
            
            <a href="?action=login" class="button">Login Now</a>
            
            <div class="info">
                <p>This will create a manual PHP session that persists.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'check'): ?>
            <div class="info" style="background: #d4edda; border: 1px solid #c3e6cb;">
                <h3>Login Test Result:</h3>
                <p>PHP Session: <?= $phpLoggedIn ? '✅ Persisted' : '❌ Lost' ?></p>
                <p>Laravel Session: <?= $laravelLoggedIn ? '✅ Persisted' : '❌ Lost' ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>