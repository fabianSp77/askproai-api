<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ihr Zugang wurde freigeschaltet</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #4F46E5;">AskProAI</h1>
        </div>
        
        <h2>Ihr Business Portal Zugang ist jetzt aktiv!</h2>
        
        <p>Hallo {{ $user->name }},</p>
        
        <p>gute Nachrichten! Ihr Business Portal Zugang wurde erfolgreich freigeschaltet.</p>
        
        <div style="background-color: #EEF2FF; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4F46E5;">
            <h3 style="margin-top: 0; color: #4F46E5;">Ihre Zugangsdaten:</h3>
            <p style="margin: 5px 0;"><strong>E-Mail:</strong> {{ $user->email }}</p>
            <p style="margin: 5px 0;"><strong>Portal URL:</strong> <a href="{{ url('/business/login') }}" style="color: #4F46E5;">{{ url('/business/login') }}</a></p>
            <p style="margin: 5px 0;"><strong>Passwort:</strong> Das von Ihnen bei der Registrierung gewählte Passwort</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/business/login') }}" 
               style="display: inline-block; background-color: #4F46E5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Jetzt einloggen
            </a>
        </div>
        
        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0;">Was können Sie im Business Portal tun?</h4>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Anrufe und Gesprächsprotokolle einsehen</li>
                <li>Termine verwalten und planen</li>
                <li>Kundendaten pflegen</li>
                <li>Berichte und Statistiken abrufen</li>
                <li>Team-Mitglieder verwalten</li>
            </ul>
        </div>
        
        <div style="margin: 30px 0;">
            <p><strong>Benötigen Sie Hilfe?</strong></p>
            <p>Unser Support-Team steht Ihnen gerne zur Verfügung:</p>
            <ul style="list-style: none; padding: 0;">
                <li>📧 E-Mail: <a href="mailto:support@askproai.de" style="color: #4F46E5;">support@askproai.de</a></li>
                <li>📞 Telefon: +49 (0) 123 456789</li>
                <li>📚 Dokumentation: <a href="{{ url('/help') }}" style="color: #4F46E5;">{{ url('/help') }}</a></li>
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