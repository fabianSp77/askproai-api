<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Get user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found');
}

// Login using Laravel's Auth but prevent session migration
Auth::guard('web')->login($user, true);

// Get session and ensure data is saved
$session = app('session.store');
$session->put('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d', $user->id);
$session->put('password_hash_web', $user->password);
$session->save();

// Set auth cookie
$cookie = cookie(
    'askproai_session',
    session_id(),
    120, // 2 hours
    '/',
    'api.askproai.de',
    true,
    true
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Working Login</title>
    <meta http-equiv="refresh" content="2;url=/admin">
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f0f0f0; }
        .box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .success { color: #4CAF50; font-size: 48px; margin-bottom: 20px; }
        h2 { color: #333; margin: 0 0 10px 0; }
        p { color: #666; }
        .spinner { border: 3px solid #f3f3f3; border-top: 3px solid #4CAF50; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="box">
        <div class="success">✓</div>
        <h2>Login Successful!</h2>
        <p>Logged in as: <?= htmlspecialchars($user->email) ?></p>
        <p>Session ID: <?= htmlspecialchars(substr(session_id(), 0, 20)) ?>...</p>
        <div class="spinner"></div>
        <p>Redirecting to admin panel...</p>
    </div>
    <?php
    // Output the cookie
    setcookie(
        'askproai_session',
        session_id(),
        time() + (120 * 60),
        '/',
        'api.askproai.de',
        true,
        true
    );
    ?>
</body>
</html>