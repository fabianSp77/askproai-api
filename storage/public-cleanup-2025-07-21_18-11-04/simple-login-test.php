<?php
/**
 * Simple Login Test - Direct and Simple
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$action = $_GET['action'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .box { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .button { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Simple Login Test</h1>
    
    <?php if ($action === 'login'): ?>
        <?php
        $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
        if ($user) {
            Auth::login($user, true);
            session()->save();
            echo '<div class="box success">Login successful! Redirecting...</div>';
            echo '<meta http-equiv="refresh" content="1;url=?action=check">';
        }
        ?>
    <?php elseif ($action === 'check'): ?>
        <div class="box">
            <h2>After Redirect:</h2>
            <p>Auth::check() = <span class="<?= Auth::check() ? 'success' : 'error' ?>"><?= Auth::check() ? 'TRUE ✅' : 'FALSE ❌' ?></span></p>
            <?php if (Auth::check()): ?>
                <p>User: <?= Auth::user()->email ?></p>
                <p class="success">🎉 Session persisted successfully!</p>
            <?php else: ?>
                <p class="error">Session was lost after redirect</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="box">
            <p>Current status: <?= Auth::check() ? '<span class="success">Logged in as ' . Auth::user()->email . '</span>' : '<span class="error">Not logged in</span>' ?></p>
        </div>
    <?php endif; ?>
    
    <div class="box">
        <a href="?action=login" class="button">Test Login</a>
        <a href="?" class="button">Reset</a>
        <?php if (Auth::check()): ?>
            <a href="/admin" class="button">Go to Admin</a>
        <?php endif; ?>
    </div>
    
    <div class="box">
        <h3>Debug Info:</h3>
        <p>Session ID: <?= session()->getId() ?></p>
        <p>Cookie: <?= isset($_COOKIE[config('session.cookie')]) ? 'Present' : 'Missing' ?></p>
    </div>
</body>
</html>