<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #374151;
            margin-bottom: 20px;
        }
        .info {
            background: #e5e7eb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background: #10b981;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Test</h1>
        
        <div class="success">
            ✅ Sie sind erfolgreich eingeloggt!
        </div>
        
        <div class="info">
            <h3>Login funktioniert!</h3>
            <p>Das schwarze Dashboard-Problem wird jetzt behoben...</p>
            <p>User: {{ auth()->user()->email ?? 'Nicht eingeloggt' }}</p>
            <p>Time: {{ now()->format('d.m.Y H:i:s') }}</p>
        </div>
        
        <div>
            <h3>Nächste Schritte:</h3>
            <ul>
                <li>Dashboard-Widgets werden geladen</li>
                <li>Layout wird repariert</li>
                <li>CSS wird korrigiert</li>
            </ul>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/admin" style="background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                Zurück zum Admin-Panel
            </a>
        </div>
    </div>
</body>
</html>