<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .info { background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Login Test</h1>
        
        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif
        
        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif
        
        <div class="info">
            <strong>Current Status:</strong><br>
            Session ID: {{ session()->getId() }}<br>
            Auth Check: {{ auth()->check() ? 'YES' : 'NO' }}<br>
            Auth User: {{ auth()->user() ? auth()->user()->email : 'none' }}<br>
            Session Driver: {{ config('session.driver') }}<br>
            HTTPS: {{ request()->secure() ? 'YES' : 'NO' }}
        </div>
        
        <form method="POST" action="/debug-login/attempt">
            @csrf
            <h3>Test Login with fabian@askproai.de</h3>
            <button type="submit">Attempt Login</button>
        </form>
        
        <hr style="margin: 20px 0;">
        
        <a href="/auth-debug">View Debug Dashboard</a> | 
        <a href="/admin/login">Go to Admin Login</a> |
        <a href="/storage/logs/laravel-{{ date('Y-m-d') }}.log" target="_blank">View Log File</a>
    </div>
</body>
</html>