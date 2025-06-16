<!DOCTYPE html>
<html>
<head>
    <title>CSRF Token Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>CSRF Token Test</h1>
    
    <div>
        <h2>Current CSRF Token:</h2>
        <code>{{ csrf_token() }}</code>
    </div>
    
    <div style="margin-top: 20px;">
        <h2>Session Info:</h2>
        <ul>
            <li>Session ID: {{ session()->getId() }}</li>
            <li>Session Driver: {{ config('session.driver') }}</li>
            <li>Session Lifetime: {{ config('session.lifetime') }} minutes</li>
            <li>Session Domain: {{ config('session.domain') }}</li>
            <li>Secure Cookie: {{ config('session.secure') ? 'Yes' : 'No' }}</li>
            <li>Same Site: {{ config('session.same_site') }}</li>
        </ul>
    </div>
    
    <div style="margin-top: 20px;">
        <h2>Cookie Info:</h2>
        <ul>
            @foreach($_COOKIE as $name => $value)
                <li>{{ $name }}: {{ substr($value, 0, 50) }}...</li>
            @endforeach
        </ul>
    </div>
    
    <div style="margin-top: 20px;">
        <h2>Test CSRF Token:</h2>
        <form method="POST" action="/test-csrf">
            @csrf
            <button type="submit">Test CSRF</button>
        </form>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="/admin/login">Go to Admin Login</a>
    </div>
</body>
</html>