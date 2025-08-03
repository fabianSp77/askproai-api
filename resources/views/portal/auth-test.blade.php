<!DOCTYPE html>
<html>
<head>
    <title>Auth Test</title>
</head>
<body>
    <h1>Auth Test Results</h1>
    
    <h2>Direct Auth Check:</h2>
    <pre>Auth::guard('portal')->check(): {{ Auth::guard('portal')->check() ? 'true' : 'false' }}</pre>
    
    <h2>Blade Directives:</h2>
    
    <h3>@auth('portal') directive:</h3>
    @auth('portal')
        <p>✅ @auth('portal') - User is authenticated</p>
    @else
        <p>❌ @auth('portal') - User is NOT authenticated</p>
    @endauth
    
    <h3>@guest('portal') directive:</h3>
    @guest('portal')
        <p>❌ @guest('portal') - User is a guest (not authenticated)</p>
    @else
        <p>✅ @guest('portal') - User is NOT a guest (authenticated)</p>
    @endguest
    
    <h3>Auth Details:</h3>
    @if(Auth::guard('portal')->check())
        <pre>
User ID: {{ Auth::guard('portal')->user()->id }}
Email: {{ Auth::guard('portal')->user()->email }}
        </pre>
    @else
        <pre>No user authenticated</pre>
    @endif
    
    <h3>Session Info:</h3>
    <pre>
Session ID: {{ session()->getId() }}
Session Keys: {{ implode(', ', array_keys(session()->all())) }}
    </pre>
</body>
</html>