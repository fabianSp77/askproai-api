<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Test Login</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 30px;
            text-align: center;
        }
        .credentials {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .credentials h3 {
            margin-top: 0;
            color: #0369a1;
        }
        .credential-item {
            background: white;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            border: 1px solid #e0f2fe;
        }
        .credential-item strong {
            display: inline-block;
            width: 80px;
            color: #475569;
        }
        .credential-item code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 500;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .button {
            width: 100%;
            background: #3b82f6;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .direct-access {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }
        .direct-access a {
            color: #8b5cf6;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        .direct-access a:hover {
            color: #7c3aed;
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Portal Test Login</h1>

        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <div class="credentials">
            <h3>🧪 Test-Zugangsdaten</h3>
            <div class="credential-item">
                <strong>Email:</strong> <code>test@askproai.de</code><br>
                <strong>Passwort:</strong> <code>Test123!</code>
            </div>
            <div class="credential-item">
                <strong>Email:</strong> <code>demo@askproai.de</code><br>
                <strong>Passwort:</strong> <code>Demo123!</code>
            </div>
            <div class="credential-item">
                <strong>Email:</strong> <code>portal@askproai.de</code><br>
                <strong>Passwort:</strong> <code>Portal123!</code>
            </div>
        </div>

        <form method="POST" action="{{ route('business.login') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="test@askproai.de" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Passwort</label>
                <input type="password" id="password" name="password" value="Test123!" required>
            </div>

            <button type="submit" class="button">
                Anmelden →
            </button>
        </form>

        <div class="direct-access">
            <p style="color: #64748b; margin-bottom: 12px;">Probleme beim Login?</p>
            <a href="/portal-direct-access.php">
                <span>🚀</span> Direktzugang verwenden
            </a>
        </div>
    </div>
</body>
</html>