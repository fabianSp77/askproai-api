<!DOCTYPE html>
<html>
<head>
    <title>Force Logout & Fresh Login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        button {
            padding: 12px 24px;
            background: #3B82F6;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #2563EB;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info { background: #DBEAFE; color: #1E40AF; }
        .success { background: #D1FAE5; color: #065F46; }
        .error { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Business Portal - Clean Login</h1>
        
        <div class="status info">
            <strong>Problem:</strong> Du bist als Admin-User eingeloggt, nicht als Portal-User!
        </div>
        
        <h3>Schritt 1: Alle Sessions beenden</h3>
        <form method="POST" action="/logout" style="display: inline;">
            @csrf
            <button type="submit">Admin Logout</button>
        </form>
        
        <form method="POST" action="{{ route('business.logout') }}" style="display: inline;">
            @csrf
            <button type="submit">Portal Logout</button>
        </form>
        
        <button onclick="clearAllCookies()">Alle Cookies löschen</button>
        
        <h3>Schritt 2: Neu einloggen</h3>
        <button onclick="window.location.href='{{ route('business.login') }}'">
            Zum Portal Login
        </button>
        
        <div class="status success" style="margin-top: 30px;">
            <strong>Zugangsdaten:</strong><br>
            Email: fabianspitzer@icloud.com<br>
            Passwort: demo123
        </div>
    </div>
    
    <script>
    function clearAllCookies() {
        // Clear all cookies
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/;domain=.askproai.de"); 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/;domain=askproai.de"); 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });
        
        // Clear localStorage and sessionStorage
        localStorage.clear();
        sessionStorage.clear();
        
        alert('Alle Cookies und Storage gelöscht! Bitte neu einloggen.');
        window.location.href = '{{ route('business.login') }}';
    }
    </script>
</body>
</html>