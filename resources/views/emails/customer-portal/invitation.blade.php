<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einladung zum Kundenportal</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #4b5563;
        }
        .content {
            margin-bottom: 30px;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
        }
        .cta-button:hover {
            background-color: #1d4ed8;
        }
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid #2563eb;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box strong {
            color: #1f2937;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
        .personal-message {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
            font-style: italic;
        }
        .benefits {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .benefits li {
            padding: 8px 0 8px 28px;
            position: relative;
        }
        .benefits li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'AskPro') }}</div>
            <h1>Willkommen in unserem Kundenportal!</h1>
        </div>

        <div class="content">
            <p class="greeting">Hallo,</p>

            <p>{{ $invitation->inviter->name }} von <strong>{{ $invitation->company->name }}</strong> hat Sie eingeladen, unser Kundenportal zu nutzen.</p>

            @if(isset($invitation->metadata['personal_message']) && $invitation->metadata['personal_message'])
            <div class="personal-message">
                <strong>Persönliche Nachricht:</strong><br>
                {{ $invitation->metadata['personal_message'] }}
            </div>
            @endif

            <p>Mit Ihrem persönlichen Kundenportal können Sie:</p>

            <ul class="benefits">
                <li>Ihre Termine jederzeit online einsehen</li>
                <li>Termine flexibel verschieben</li>
                <li>Termine bei Bedarf stornieren</li>
                <li>Ihre Terminhistorie verwalten</li>
                <li>Alternative Terminvorschläge erhalten</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ route('customer-portal.invitation.show', ['token' => $invitation->token]) }}" class="cta-button">
                    Jetzt registrieren
                </a>
            </div>

            <div class="info-box">
                <strong>Wichtig:</strong> Diese Einladung ist <strong>72 Stunden</strong> gültig und läuft am <strong>{{ $invitation->expires_at->format('d.m.Y \u\m H:i') }} Uhr</strong> ab.
            </div>

            <p>Falls der Button nicht funktioniert, kopieren Sie bitte diesen Link in Ihren Browser:</p>
            <p style="word-break: break-all; color: #2563eb; font-size: 12px;">
                {{ route('customer-portal.invitation.show', ['token' => $invitation->token]) }}
            </p>
        </div>

        <div class="footer">
            <p>Diese E-Mail wurde automatisch von {{ config('app.name') }} versendet.</p>
            <p>Bei Fragen kontaktieren Sie uns bitte unter: {{ $invitation->company->email ?? config('mail.from.address') }}</p>
            <p style="margin-top: 20px; font-size: 11px; color: #9ca3af;">
                © {{ date('Y') }} {{ $invitation->company->name }}. Alle Rechte vorbehalten.
            </p>
        </div>
    </div>
</body>
</html>
