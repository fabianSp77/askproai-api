<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - {{ app()->getLocale() === 'de' ? 'Löschung bestätigt' : 'Deletion Confirmed' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 600px;
            margin: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        
        .icon-success {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background-color: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .icon-success svg {
            width: 40px;
            height: 40px;
            stroke: #155724;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .message {
            color: #555;
            margin-bottom: 30px;
            font-size: 18px;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        
        .info-box h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            margin-left: 20px;
            color: #666;
        }
        
        .info-box li {
            margin-bottom: 8px;
        }
        
        .timeline {
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .timeline-icon {
            width: 30px;
            height: 30px;
            background-color: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .timeline-icon svg {
            width: 16px;
            height: 16px;
            stroke: white;
        }
        
        .timeline-content {
            text-align: left;
        }
        
        .timeline-content strong {
            color: #2c3e50;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
            color: #7f8c8d;
        }
        
        .footer a {
            color: #3498db;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
        </div>
        
        @if(app()->getLocale() === 'de')
            <h1>Löschung bestätigt</h1>
            
            <p class="message">
                Ihre Anfrage zur Datenlöschung wurde erfolgreich bestätigt und wird nun bearbeitet.
            </p>
            
            <div class="info-box">
                <h3>Was passiert als Nächstes?</h3>
                <ul>
                    <li>Ihre persönlichen Daten werden innerhalb von 72 Stunden anonymisiert oder gelöscht</li>
                    <li>Sie erhalten eine Bestätigungs-E-Mail, sobald der Prozess abgeschlossen ist</li>
                    <li>Gesetzlich vorgeschriebene Daten (z.B. Rechnungen) werden gemäß den Aufbewahrungsfristen gespeichert</li>
                    <li>Nach Abschluss können Sie sich nicht mehr in Ihr Konto einloggen</li>
                </ul>
            </div>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <strong>Schritt 1:</strong> Löschanfrage bestätigt (abgeschlossen)
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <strong>Schritt 2:</strong> Datenverarbeitung läuft (0-72 Stunden)
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <strong>Schritt 3:</strong> Bestätigungs-E-Mail wird versendet
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Falls Sie diese Löschung nicht beabsichtigt haben, kontaktieren Sie uns bitte umgehend.</p>
                <p style="margin-top: 10px;">
                    <a href="/">Zurück zur Startseite</a>
                </p>
            </div>
        @else
            <h1>Deletion Confirmed</h1>
            
            <p class="message">
                Your data deletion request has been successfully confirmed and is now being processed.
            </p>
            
            <div class="info-box">
                <h3>What happens next?</h3>
                <ul>
                    <li>Your personal data will be anonymized or deleted within 72 hours</li>
                    <li>You will receive a confirmation email once the process is complete</li>
                    <li>Legally required data (e.g., invoices) will be stored according to retention periods</li>
                    <li>After completion, you will no longer be able to log into your account</li>
                </ul>
            </div>
            
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <strong>Step 1:</strong> Deletion request confirmed (completed)
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <strong>Step 2:</strong> Data processing in progress (0-72 hours)
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <strong>Step 3:</strong> Confirmation email will be sent
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>If you did not intend this deletion, please contact us immediately.</p>
                <p style="margin-top: 10px;">
                    <a href="/">Back to Homepage</a>
                </p>
            </div>
        @endif
    </div>
</body>
</html>