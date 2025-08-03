<?php
/**
 * Fix Login Session Issues
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
use Illuminate\Support\Facades\Log;

// Clear all portal sessions
Auth::guard('portal')->logout();
Session::flush();
Session::regenerate();

// Clear cookies with JavaScript as well
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fixing Login Session</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success {
            color: #10b981;
            font-size: 48px;
            margin-bottom: 20px;
        }
        a {
            display: inline-block;
            margin: 10px;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        a:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="success">✅</div>
        <h1>Session wurde zurückgesetzt</h1>
        <p>Alle Portal-Sessions wurden beendet und Cookies gelöscht.</p>
        <p>Sie können sich jetzt neu anmelden:</p>
        
        <div>
            <a href="/business/login">Business Portal Login</a>
            <a href="/business-login-fixed.php">Alternative Login</a>
        </div>
    </div>
    
    <script>
        // Clear all cookies for this domain
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/;domain=.askproai.de"); 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });
        
        // Clear localStorage and sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
        } catch(e) {}
        
        console.log('All sessions and storage cleared');
    </script>
</body>
</html>