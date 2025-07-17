<!DOCTYPE html>
<html>
<head>
    <title>Portal Login Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .success { background: #D1FAE5; color: #065F46; }
        .error { background: #FEE2E2; color: #991B1B; }
        button {
            width: 100%;
            padding: 12px;
            background: #3B82F6;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin: 5px 0;
        }
        button:hover {
            background: #2563EB;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Business Portal - Quick Login</h2>
        
        <div id="status"></div>
        
        <form method="POST" action="{{ route('business.login.post') }}">
            @csrf
            <input type="hidden" name="email" value="fabianspitzer@icloud.com">
            <input type="hidden" name="password" value="demo123">
            <button type="submit">Login mit Demo Account</button>
        </form>
        
        <hr style="margin: 20px 0;">
        
        <button onclick="window.location.href='{{ route('business.login') }}'">
            Zur normalen Login-Seite
        </button>
        
        <button onclick="checkAuth()">
            Auth Status prüfen
        </button>
        
        <pre id="result" style="margin-top: 20px; padding: 10px; background: #f5f5f5; display: none;"></pre>
    </div>
    
    <script>
    function checkAuth() {
        fetch('{{ route('business.test.session') }}')
            .then(r => r.json())
            .then(data => {
                document.getElementById('result').style.display = 'block';
                document.getElementById('result').textContent = JSON.stringify(data, null, 2);
                
                if (data.auth_check) {
                    document.getElementById('status').className = 'status success';
                    document.getElementById('status').textContent = '✅ Eingeloggt als: ' + data.auth_user.email;
                } else {
                    document.getElementById('status').className = 'status error';
                    document.getElementById('status').textContent = '❌ Nicht eingeloggt';
                }
            });
    }
    
    // Check auth on load
    checkAuth();
    </script>
</body>
</html>