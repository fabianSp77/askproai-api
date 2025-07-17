<?php
// Test current auth status

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Start session
Session::start();

$status = [
    'auth_check' => Auth::guard('portal')->check(),
    'user' => Auth::guard('portal')->user(),
    'session_id' => Session::getId(),
    'session_driver' => config('session.driver'),
    'session_all' => Session::all(),
    'cookies' => $_COOKIE,
    'request_headers' => getallheaders()
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Portal Auth Test</title>
    <style>
        body { 
            font-family: monospace; 
            background: #1a1a1a; 
            color: #e2e8f0; 
            padding: 20px; 
            margin: 0;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        pre { 
            background: #000; 
            padding: 15px; 
            border-radius: 8px; 
            overflow: auto;
        }
    </style>
</head>
<body>
    <h2>Portal Auth Status</h2>
    
    @if($status['auth_check'])
        <p class="success">✅ AUTHENTICATED - User: {{ $status['user']->name }}</p>
    @else
        <p class="error">❌ NOT AUTHENTICATED</p>
    @endif
    
    <h3>Session Info:</h3>
    <pre>{{ json_encode([
        'session_id' => $status['session_id'],
        'session_driver' => $status['session_driver'],
        'session_data' => $status['session_all']
    ], JSON_PRETTY_PRINT) }}</pre>
    
    <h3>Cookies:</h3>
    <pre>{{ json_encode($status['cookies'], JSON_PRETTY_PRINT) }}</pre>
</body>
</html>