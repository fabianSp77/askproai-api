<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Access - Success</title>
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
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .info-box {
            background: #e0f2fe;
            border-left: 4px solid #0284c7;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: left;
        }
        .features {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .features h3 {
            margin-top: 0;
            color: #475569;
        }
        .features ul {
            list-style: none;
            padding: 0;
        }
        .features li {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
        }
        .features li:before {
            content: "✅";
            position: absolute;
            left: 0;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 8px;
            transition: all 0.2s;
        }
        .button:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .button.secondary {
            background: #8b5cf6;
        }
        .button.secondary:hover {
            background: #7c3aed;
        }
        .button.success {
            background: #10b981;
        }
        .button.success:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">🚀</div>
        <h1>Portal-Zugang aktiviert!</h1>
        
        <div class="info-box">
            <strong>✅ Sie sind jetzt eingeloggt als:</strong><br>
            {{ $user->name }} ({{ $user->email }})<br>
            Firma: {{ $user->company->name ?? 'AskProAI Demo' }}
        </div>

        <div class="features">
            <h3>🎯 Neue Features zum Testen:</h3>
            <ul>
                <li>Audio-Player für Anrufaufnahmen</li>
                <li>Transkript Ein-/Ausklappen</li>
                <li>Übersetzungsfunktion (DeepL/Google)</li>
                <li>Detaillierte Call-Ansicht</li>
                <li>Stripe Zahlungsintegration</li>
                <li>Kosten-Breakdown pro Anruf</li>
            </ul>
        </div>

        <div style="margin-top: 30px;">
            <a href="{{ route('business.dashboard') }}" class="button">
                📊 Dashboard
            </a>
            <a href="{{ route('business.calls.index') }}" class="button secondary">
                📞 Anrufliste
            </a>
            <a href="{{ route('business.billing.index') }}" class="button success">
                💳 Billing & Stripe
            </a>
        </div>

        <p style="color: #64748b; margin-top: 20px; font-size: 14px;">
            Session ist für 24 Stunden aktiv
        </p>
    </div>

    <script>
        // Auto-redirect after 5 seconds
        setTimeout(() => {
            window.location.href = '{{ route('business.calls.index') }}';
        }, 5000);
    </script>
</body>
</html>