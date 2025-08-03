<?php
/**
 * Logout all portal sessions
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Logout from portal guard
Auth::guard('portal')->logout();

// Clear all session data
Session::flush();
Session::invalidate();
Session::regenerateToken();

// Clear cookies
setcookie('askproai_portal_session', '', time() - 3600, '/', '.askproai.de', true, true);
setcookie('askproai_session', '', time() - 3600, '/', '.askproai.de', true, true);
setcookie('XSRF-TOKEN', '', time() - 3600, '/', '.askproai.de', true, true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout Complete</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; padding: 20px; border-radius: 5px; color: #155724; }
        a { color: #3b82f6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="success">
        <h2>✅ Alle Sessions wurden beendet</h2>
        <p>Sie wurden von allen Portalen abgemeldet.</p>
        <p>Sie können sich jetzt neu anmelden:</p>
        <ul>
            <li><a href="/business/login">Business Portal Login</a></li>
            <li><a href="/admin/login">Admin Portal Login</a></li>
        </ul>
    </div>
</body>
</html>