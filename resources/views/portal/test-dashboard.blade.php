<!DOCTYPE html>
<html>
<head>
    <title>Test Dashboard</title>
</head>
<body>
    <h1>Test Dashboard - Business Portal</h1>
    
    <h2>Authentication Status:</h2>
    <pre>
Portal Auth Check: {{ Auth::guard('portal')->check() ? 'YES' : 'NO' }}
@if(Auth::guard('portal')->check())
User ID: {{ Auth::guard('portal')->user()->id }}
Email: {{ Auth::guard('portal')->user()->email }}
Company ID: {{ Auth::guard('portal')->user()->company_id }}
@endif
    </pre>
    
    <h2>Session Info:</h2>
    <pre>
Session ID: {{ session()->getId() }}
Session Keys: {{ json_encode(array_keys(session()->all()), JSON_PRETTY_PRINT) }}
Portal Session Key: {{ Auth::guard('portal')->getName() }}
Has Portal Key: {{ session()->has(Auth::guard('portal')->getName()) ? 'YES' : 'NO' }}
    </pre>
    
    <h2>Request Info:</h2>
    <pre>
URL: {{ request()->url() }}
Method: {{ request()->method() }}
IP: {{ request()->ip() }}
    </pre>
    
    <hr>
    <a href="/business/logout">Logout</a>
</body>
</html>