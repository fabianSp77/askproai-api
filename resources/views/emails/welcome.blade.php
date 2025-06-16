<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen bei AskProAI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f7f7f7;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
        }
        h1 {
            color: #3B82F6;
            font-size: 28px;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background-color: #3B82F6;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 5px;
        }
        .button:hover {
            background-color: #2563EB;
        }
        .features {
            background-color: #F3F4F6;
            border-radius: 6px;
            padding: 20px;
            margin: 30px 0;
        }
        .feature {
            margin: 15px 0;
            display: flex;
            align-items: flex-start;
        }
        .feature-icon {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            color: #10B981;
        }
        .footer {
            text-align: center;
            color: #6B7280;
            font-size: 14px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Willkommen bei AskProAI</h1>
        </div>
        
        <div class="content">
            <p>Hallo {{ $userName }},</p>
            
            <p>herzlich willkommen bei AskProAI! Wir freuen uns, dass Sie sich für unser intelligentes Terminbuchungssystem entschieden haben.</p>
            
            <p>Mit AskProAI können Ihre Kunden rund um die Uhr telefonisch Termine buchen - automatisch und zuverlässig. Lassen Sie uns gemeinsam Ihr System einrichten.</p>
            
            <div class="features">
                <h3>Was Sie mit AskProAI können:</h3>
                <div class="feature">
                    <span class="feature-icon">✅</span>
                    <span>24/7 telefonische Terminannahme durch KI</span>
                </div>
                <div class="feature">
                    <span class="feature-icon">✅</span>
                    <span>Automatische Kalendersynchronisation</span>
                </div>
                <div class="feature">
                    <span class="feature-icon">✅</span>
                    <span>Mehrsprachige Kundenbetreuung</span>
                </div>
                <div class="feature">
                    <span class="feature-icon">✅</span>
                    <span>Detaillierte Anrufprotokolle und Analysen</span>
                </div>
            </div>
            
            <p><strong>Ihre nächsten Schritte:</strong></p>
            <ol>
                <li>Melden Sie sich in Ihrem Dashboard an</li>
                <li>Folgen Sie dem Einrichtungsassistenten</li>
                <li>Richten Sie Ihre Standorte und Dienstleistungen ein</li>
                <li>Verbinden Sie Ihren Kalender</li>
                <li>Führen Sie einen Testanruf durch</li>
            </ol>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $onboardingUrl }}" class="button">Einrichtung starten</a>
                <a href="{{ $loginUrl }}" class="button" style="background-color: #6B7280;">Zum Dashboard</a>
            </div>
            
            <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung:</p>
            <ul>
                <li>E-Mail: support@askproai.de</li>
                <li>Telefon: +49 30 123456789</li>
                <li>Live-Chat: Direkt im Dashboard</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Diese E-Mail wurde an {{ $user->email }} gesendet.</p>
            <p>&copy; {{ date('Y') }} AskProAI. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>