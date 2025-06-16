<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Fortschritt bei AskProAI</title>
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
        .progress-bar {
            background-color: #E5E7EB;
            border-radius: 10px;
            height: 20px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            background-color: #3B82F6;
            height: 100%;
            transition: width 0.3s ease;
        }
        .milestone-badge {
            display: inline-block;
            background-color: #10B981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 10px 0;
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
        .tips {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
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
        <h1 style="color: #3B82F6; text-align: center;">Großartige Arbeit, {{ $userName }}!</h1>
        
        <div style="text-align: center;">
            <span class="milestone-badge">{{ $milestone }}</span>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ str_replace('% abgeschlossen', '', $milestone) }}%;"></div>
        </div>
        
        <p>Sie machen tolle Fortschritte bei der Einrichtung Ihres AskProAI-Systems für {{ $companyName }}!</p>
        
        @if($milestone === '25% abgeschlossen')
            <p>Sie haben die ersten wichtigen Schritte gemeistert. Ihre Unternehmensdaten sind erfasst und die Grundkonfiguration steht.</p>
            
            <div class="tips">
                <strong>Tipp:</strong> Als nächstes sollten Sie Ihre Mitarbeiter und Dienstleistungen einrichten. 
                Dies bildet die Basis für Ihre automatisierte Terminvergabe.
            </div>
        @elseif($milestone === '50% abgeschlossen')
            <p>Halbzeit! Ihre Standorte, Mitarbeiter und Dienstleistungen sind eingerichtet. 
            Jetzt geht es an die Integration der externen Systeme.</p>
            
            <div class="tips">
                <strong>Tipp:</strong> Die Kalenderintegration ist der Schlüssel zu einem reibungslosen Ablauf. 
                Nehmen Sie sich Zeit für diesen wichtigen Schritt.
            </div>
        @elseif($milestone === '75% abgeschlossen')
            <p>Fast geschafft! Die meisten Einstellungen sind vorgenommen. 
            Nur noch wenige Schritte trennen Sie von Ihrem vollständig eingerichteten System.</p>
            
            <div class="tips">
                <strong>Tipp:</strong> Der Testanruf ist wichtig, um sicherzustellen, dass alles wie gewünscht funktioniert. 
                Nehmen Sie sich ein paar Minuten Zeit dafür.
            </div>
        @endif
        
        <h3>Ihre nächsten Schritte:</h3>
        <ul>
            @if(in_array($milestone, ['25% abgeschlossen']))
                <li>✅ Unternehmensdaten eingegeben</li>
                <li>✅ Standorte hinzugefügt</li>
                <li>⏳ Mitarbeiter anlegen</li>
                <li>⏳ Dienstleistungen definieren</li>
                <li>⏳ Arbeitszeiten festlegen</li>
            @elseif(in_array($milestone, ['50% abgeschlossen']))
                <li>✅ Unternehmensdaten eingegeben</li>
                <li>✅ Standorte hinzugefügt</li>
                <li>✅ Mitarbeiter angelegt</li>
                <li>✅ Dienstleistungen definiert</li>
                <li>⏳ Kalender verbinden</li>
                <li>⏳ KI-Telefon einrichten</li>
            @elseif(in_array($milestone, ['75% abgeschlossen']))
                <li>✅ Grundkonfiguration abgeschlossen</li>
                <li>✅ Team und Services eingerichtet</li>
                <li>✅ Integrationen verbunden</li>
                <li>⏳ Testanruf durchführen</li>
                <li>⏳ Finale Überprüfung</li>
            @endif
        </ul>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $onboardingUrl }}" class="button">Einrichtung fortsetzen</a>
        </div>
        
        <p style="text-align: center; color: #6B7280;">
            Sie können die Einrichtung jederzeit unterbrechen und später fortsetzen. 
            Ihr Fortschritt wird automatisch gespeichert.
        </p>
        
        <div class="footer">
            <p>Diese E-Mail wurde an {{ $user->email }} gesendet.</p>
            <p>&copy; {{ date('Y') }} AskProAI. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>