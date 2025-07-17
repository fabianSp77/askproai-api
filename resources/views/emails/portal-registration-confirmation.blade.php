<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registrierung bei AskProAI</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #4F46E5;">AskProAI</h1>
        </div>
        
        <h2>Vielen Dank für Ihre Registrierung!</h2>
        
        <p>Hallo {{ $user->name }},</p>
        
        <p>wir haben Ihre Registrierung für das AskProAI Business Portal erhalten.</p>
        
        <div style="background-color: #EEF2FF; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4F46E5;">
            <h3 style="margin-top: 0; color: #4F46E5;">Was passiert als Nächstes?</h3>
            <ol style="margin: 10px 0; padding-left: 20px;">
                <li>Unser Team überprüft Ihre Registrierung</li>
                <li>Nach erfolgreicher Prüfung erhalten Sie eine Freischaltungsbestätigung</li>
                <li>Anschließend können Sie sich mit Ihren Zugangsdaten einloggen</li>
            </ol>
            <p style="margin-bottom: 0;"><strong>Bearbeitungszeit:</strong> In der Regel 1-2 Werktage</p>
        </div>
        
        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0;">Ihre Registrierungsdaten:</h4>
            <p style="margin: 5px 0;"><strong>E-Mail:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Firma:</strong> {{ $user->company->name }}</p>
        </div>
        
        <div style="margin: 30px 0;">
            <p><strong>Haben Sie Fragen?</strong></p>
            <p>Unser Support-Team hilft Ihnen gerne weiter:</p>
            <ul style="list-style: none; padding: 0;">
                <li>📧 E-Mail: <a href="mailto:support@askproai.de" style="color: #4F46E5;">support@askproai.de</a></li>
                <li>📞 Telefon: +49 (0) 123 456789</li>
                <li>🕐 Erreichbar: Mo-Fr 9:00-18:00 Uhr</li>
            </ul>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <div style="text-align: center; font-size: 12px; color: #6b7280;">
            <p>Mit freundlichen Grüßen<br>Ihr AskProAI Team</p>
            <p style="margin-top: 20px;">
                AskProAI GmbH | Musterstraße 123 | 12345 Berlin<br>
                <a href="{{ url('/') }}" style="color: #4F46E5;">www.askproai.de</a>
            </p>
        </div>
    </div>
</body>
</html>