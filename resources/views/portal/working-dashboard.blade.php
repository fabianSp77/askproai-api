<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logout-form {
            display: inline;
        }
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .logout-btn:hover {
            background: #dc2626;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .welcome-box {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .welcome-box h2 {
            font-size: 1.875rem;
            margin-bottom: 0.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
        }
        .nav-links {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        .nav-links h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        .nav-links ul {
            list-style: none;
        }
        .nav-links li {
            margin-bottom: 0.5rem;
        }
        .nav-links a {
            color: #3b82f6;
            text-decoration: none;
            display: block;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: background 0.2s;
        }
        .nav-links a:hover {
            background: #eff6ff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Business Portal</h1>
        <div class="user-info">
            <span>{{ $user->email }}</span>
            <form method="POST" action="{{ route('business.logout') }}" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">Abmelden</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-box">
            <h2>Willkommen zurück!</h2>
            <p>Sie sind erfolgreich angemeldet als {{ $user->email }}</p>
            <p style="margin-top: 0.5rem; color: #6b7280;">Company ID: {{ $company_id }}</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Status</h3>
                <div class="value" style="color: #10b981;">✅ Aktiv</div>
            </div>
            <div class="stat-card">
                <h3>Benutzer ID</h3>
                <div class="value">{{ $user->id }}</div>
            </div>
            <div class="stat-card">
                <h3>Rolle</h3>
                <div class="value">{{ $user->role ?? 'User' }}</div>
            </div>
        </div>
        
        <div class="nav-links">
            <h3>Navigation</h3>
            <ul>
                <li><a href="{{ route('business.calls') }}">📞 Anrufe</a></li>
                <li><a href="{{ route('business.appointments') }}">📅 Termine</a></li>
                <li><a href="{{ route('business.customers') }}">👥 Kunden</a></li>
                <li><a href="{{ route('business.analytics') }}">📊 Analysen</a></li>
                <li><a href="{{ route('business.settings') }}">⚙️ Einstellungen</a></li>
                <li><a href="{{ route('business.billing') }}">💳 Abrechnung</a></li>
                <li><a href="{{ route('business.team') }}">👥 Team</a></li>
            </ul>
        </div>
    </div>
</body>
</html>