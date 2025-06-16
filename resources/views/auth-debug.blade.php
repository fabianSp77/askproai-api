<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth Debug Dashboard</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #569cd6;
            border-bottom: 2px solid #569cd6;
            padding-bottom: 10px;
        }
        .section {
            background: #252526;
            border: 1px solid #3e3e3e;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .section h2 {
            color: #4ec9b0;
            margin-top: 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
        }
        .label {
            color: #9cdcfe;
            font-weight: bold;
        }
        .value {
            color: #ce9178;
            word-break: break-all;
        }
        .true { color: #4ec9b0; }
        .false { color: #f44747; }
        .null { color: #808080; }
        .log-entry {
            background: #1e1e1e;
            border: 1px solid #3e3e3e;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 12px;
            overflow-x: auto;
        }
        .log-time {
            color: #808080;
        }
        .log-level-info { color: #3794ff; }
        .log-level-warning { color: #ffcc00; }
        .log-level-error { color: #f44747; }
        button {
            background: #0e639c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #1177bb;
        }
        .actions {
            margin-bottom: 20px;
        }
        pre {
            background: #1e1e1e;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Auth Debug Dashboard</h1>
        
        <div class="actions">
            <button onclick="location.reload()">🔄 Refresh</button>
            <button onclick="window.location.href='/admin/login'">🔐 Go to Login</button>
            <button onclick="clearLogs()">🗑️ Clear Logs</button>
        </div>

        <div class="section">
            <h2>Current Session & Auth Status</h2>
            <div class="info-grid">
                <div class="label">Session ID:</div>
                <div class="value">{{ session()->getId() }}</div>
                
                <div class="label">Session Driver:</div>
                <div class="value">{{ config('session.driver') }}</div>
                
                <div class="label">Session Domain:</div>
                <div class="value">{{ config('session.domain') ?: 'not set' }}</div>
                
                <div class="label">Session Secure:</div>
                <div class="value {{ config('session.secure') ? 'true' : 'false' }}">{{ config('session.secure') ? 'true' : 'false' }}</div>
                
                <div class="label">Auth Check:</div>
                <div class="value {{ auth()->check() ? 'true' : 'false' }}">{{ auth()->check() ? 'true' : 'false' }}</div>
                
                <div class="label">Auth User:</div>
                <div class="value">{{ auth()->user() ? auth()->user()->email : 'null' }}</div>
                
                <div class="label">Default Guard:</div>
                <div class="value">{{ config('auth.defaults.guard') }}</div>
                
                <div class="label">Request Secure:</div>
                <div class="value {{ request()->secure() ? 'true' : 'false' }}">{{ request()->secure() ? 'true' : 'false' }}</div>
            </div>
        </div>

        <div class="section">
            <h2>Session Data</h2>
            <pre>{{ json_encode(session()->all(), JSON_PRETTY_PRINT) }}</pre>
        </div>

        <div class="section">
            <h2>Recent Auth Logs</h2>
            <div id="logs">
                @php
                    $logFile = storage_path('logs/laravel.log');
                    if (file_exists($logFile)) {
                        $lines = array_slice(file($logFile), -100);
                        $authLogs = [];
                        foreach ($lines as $line) {
                            if (strpos($line, '=== AUTH') !== false) {
                                $authLogs[] = $line;
                            }
                        }
                        $authLogs = array_slice($authLogs, -20);
                    }
                @endphp
                
                @if (!empty($authLogs))
                    @foreach (array_reverse($authLogs) as $log)
                        <div class="log-entry">
                            @php
                                $parts = explode('] ', $log, 3);
                                $time = isset($parts[0]) ? str_replace('[', '', $parts[0]) : '';
                                $level = isset($parts[1]) ? $parts[1] : '';
                                $message = isset($parts[2]) ? $parts[2] : '';
                                
                                $levelClass = 'log-level-info';
                                if (strpos($level, 'WARNING') !== false) $levelClass = 'log-level-warning';
                                if (strpos($level, 'ERROR') !== false) $levelClass = 'log-level-error';
                            @endphp
                            <span class="log-time">{{ $time }}</span>
                            <span class="{{ $levelClass }}">{{ $level }}</span>
                            <pre>{{ $message }}</pre>
                        </div>
                    @endforeach
                @else
                    <p>No auth logs found yet. Try logging in to see debug information.</p>
                @endif
            </div>
        </div>

        <div class="section">
            <h2>Test Login</h2>
            <form method="POST" action="/debug/login">
                @csrf
                <button type="submit">Test Login with fabian@askproai.de</button>
            </form>
        </div>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Clear all debug logs?')) {
                fetch('/debug/clear-logs', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                    .then(() => location.reload());
            }
        }
        
        // Auto-refresh every 5 seconds
        setTimeout(() => location.reload(), 5000);
    </script>
</body>
</html>