<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Business Portal Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e1e8ed;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome h1 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #e1e8ed;
            padding: 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            color: #2c3e50;
            text-align: center;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            border-color: #3498db;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
        }
        
        .logout {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    @php
        $user = Auth::guard('portal')->user();
    @endphp
    
    <div class="header">
        <div class="logo">AskProAI Business Portal</div>
        <div class="user-info">
            <span>{{ $user->name ?? 'Demo User' }}</span>
            <a href="/business/logout" class="logout">Abmelden</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome">
            <h1>Willkommen zurück, {{ $user->name ?? 'Demo User' }}!</h1>
            <p>Sie sind erfolgreich im Business Portal angemeldet.</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Heutige Anrufe</h3>
                <div class="value">0</div>
            </div>
            <div class="stat-card">
                <h3>Offene Termine</h3>
                <div class="value">0</div>
            </div>
            <div class="stat-card">
                <h3>Neue Kunden</h3>
                <div class="value">0</div>
            </div>
            <div class="stat-card">
                <h3>Umsatz heute</h3>
                <div class="value">€0</div>
            </div>
        </div>
        
        <h2 style="margin-bottom: 1rem;">Schnellzugriff</h2>
        <div class="actions">
            <a href="/business/calls" class="action-btn">
                <h3>📞 Anrufe</h3>
                <p>Alle Anrufe anzeigen</p>
            </a>
            <a href="/business/appointments" class="action-btn">
                <h3>📅 Termine</h3>
                <p>Terminkalender öffnen</p>
            </a>
            <a href="/business/customers" class="action-btn">
                <h3>👥 Kunden</h3>
                <p>Kundenverwaltung</p>
            </a>
            <a href="/business/settings" class="action-btn">
                <h3>⚙️ Einstellungen</h3>
                <p>Portal konfigurieren</p>
            </a>
        </div>
        
        <div style="margin-top: 3rem; padding: 1rem; background: #fff3cd; border-radius: 8px;">
            <strong>Hinweis:</strong> Dies ist eine vereinfachte Version des Dashboards. 
            Das React-Dashboard wird möglicherweise aufgrund von Build-Problemen nicht korrekt geladen.
        </div>
    </div>
</body>
</html>