<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Herzlichen Glückwunsch - Ihr System ist bereit!</title>
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
        .celebration {
            text-align: center;
            margin-bottom: 30px;
        }
        .celebration-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        h1 {
            color: #10B981;
            font-size: 28px;
            margin: 0;
            text-align: center;
        }
        .stats {
            background-color: #F3F4F6;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .stat {
            text-align: center;
            padding: 10px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3B82F6;
        }
        .stat-label {
            font-size: 14px;
            color: #6B7280;
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
        .button.secondary {
            background-color: #6B7280;
        }
        .checklist {
            background-color: #F0FDF4;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
        }
        .checklist-item {
            margin: 10px 0;
            color: #065F46;
        }
        .resources {
            background-color: #EFF6FF;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
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
        <div class="celebration">
            <div class="celebration-icon">🎉</div>
            <h1>Herzlichen Glückwunsch!</h1>
            <p style="font-size: 18px; color: #059669;">Ihr AskProAI-System ist vollständig eingerichtet!</p>
        </div>
        
        <p>Hallo {{ $userName }},</p>
        
        <p>fantastische Arbeit! Sie haben die Einrichtung Ihres intelligenten Terminbuchungssystems für 
        <strong>{{ $companyName }}</strong> erfolgreich abgeschlossen. Ihr System ist jetzt bereit, 
        Anrufe entgegenzunehmen und automatisch Termine zu vereinbaren.</p>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value">24/7</div>
                <div class="stat-label">Verfügbarkeit</div>
            </div>
            <div class="stat">
                <div class="stat-value">30+</div>
                <div class="stat-label">Sprachen</div>
            </div>
            <div class="stat">
                <div class="stat-value">∞</div>
                <div class="stat-label">Gleichzeitige Anrufe</div>
            </div>
            <div class="stat">
                <div class="stat-value">100%</div>
                <div class="stat-label">Eingerichtet</div>
            </div>
        </div>
        
        <div class="checklist">
            <h3>✅ Was Sie erreicht haben:</h3>
            <div class="checklist-item">✓ Unternehmensprofil vollständig eingerichtet</div>
            <div class="checklist-item">✓ Standorte und Mitarbeiter hinzugefügt</div>
            <div class="checklist-item">✓ Dienstleistungen und Arbeitszeiten definiert</div>
            <div class="checklist-item">✓ Kalendersystem verbunden</div>
            <div class="checklist-item">✓ KI-Telefonagent konfiguriert</div>
            <div class="checklist-item">✓ Testanruf erfolgreich durchgeführt</div>
        </div>
        
        <h3>🚀 Ihre nächsten Schritte:</h3>
        <ol>
            <li><strong>Telefonnummer bekanntgeben:</strong> Teilen Sie Ihren Kunden Ihre neue Service-Nummer mit</li>
            <li><strong>Team informieren:</strong> Stellen Sie sicher, dass alle Mitarbeiter über das neue System Bescheid wissen</li>
            <li><strong>Erste Termine beobachten:</strong> Überprüfen Sie die ersten automatisch gebuchten Termine</li>
            <li><strong>Feintuning:</strong> Passen Sie bei Bedarf die Einstellungen an</li>
        </ol>
        
        <div class="resources">
            <h3>📚 Hilfreiche Ressourcen:</h3>
            <ul>
                <li><a href="{{ url('/docs/best-practices') }}">Best Practices für optimale Ergebnisse</a></li>
                <li><a href="{{ url('/docs/faq') }}">Häufig gestellte Fragen</a></li>
                <li><a href="{{ url('/tutorials') }}">Video-Tutorials</a></li>
                <li><a href="{{ url('/support') }}">Support kontaktieren</a></li>
            </ul>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $dashboardUrl }}" class="button">Zum Dashboard</a>
            <a href="{{ url('/admin/appointments') }}" class="button secondary">Termine ansehen</a>
        </div>
        
        <p style="background-color: #FEF3C7; padding: 15px; border-radius: 6px; text-align: center;">
            <strong>Profi-Tipp:</strong> Aktivieren Sie E-Mail-Benachrichtigungen, um über neue Termine 
            sofort informiert zu werden.
        </p>
        
        <p>Wir freuen uns, Sie bei AskProAI zu haben und sind gespannt auf Ihren Erfolg!</p>
        
        <p>Mit besten Grüßen,<br>
        Ihr AskProAI-Team</p>
        
        <div class="footer">
            <p>Bei Fragen: support@askproai.de | +49 30 123456789</p>
            <p>Diese E-Mail wurde an {{ $user->email }} gesendet.</p>
            <p>&copy; {{ date('Y') }} AskProAI. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>