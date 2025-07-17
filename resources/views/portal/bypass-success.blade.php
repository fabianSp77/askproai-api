<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bypass Login Success</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #1e293b;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        h1 {
            color: #10b981;
            margin-bottom: 30px;
        }
        .info {
            background: #0f172a;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }
        .debug {
            background: #000;
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            overflow: auto;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin: 10px 10px 10px 0;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .success { color: #10b981; }
        .warning { color: #f59e0b; }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Bypass Login Successful!</h1>
        
        <div class="info">
            <h3>Login Status:</h3>
            <p><strong>User:</strong> {{ $user->name }} ({{ $user->email }})</p>
            <p><strong>Company:</strong> {{ $user->company->name ?? 'N/A' }}</p>
            <p><strong>Authenticated:</strong> 
                @if($authenticated)
                    <span class="success">✅ YES</span>
                @else
                    <span class="error">❌ NO</span>
                @endif
            </p>
            <p><strong>Session ID:</strong> {{ substr($sessionId, 0, 20) }}...</p>
        </div>
        
        <div class="debug">
            <h3>Debug Information:</h3>
            <pre>{{ json_encode([
                'authenticated' => $authenticated,
                'user_id' => $user->id,
                'session_driver' => config('session.driver'),
                'session_cookie' => $_COOKIE[config('session.cookie')] ?? 'NOT SET',
                'auth_guard_check' => Auth::guard('portal')->check(),
                'session_data' => session()->all()
            ], JSON_PRETTY_PRINT) }}</pre>
        </div>
        
        <h3>Test Access:</h3>
        <p>Diese Links umgehen die Auth-Middleware:</p>
        <a href="{{ url('/business/bypass/dashboard') }}" class="button">
            📊 Bypass Dashboard
        </a>
        
        <p class="warning" style="margin-top: 30px;">
            ⚠️ Normale Portal-Links werden wahrscheinlich immer noch zur Login-Seite weiterleiten, 
            weil die Auth-Middleware die Session nicht erkennt.
        </p>
        
        <h3>Alternative: Inline Features</h3>
        <iframe src="/business/bypass/dashboard" style="width: 100%; height: 600px; border: 1px solid #374151; border-radius: 8px; margin-top: 20px;"></iframe>
    </div>
</body>
</html>