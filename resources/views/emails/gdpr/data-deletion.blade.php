<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'de' ? 'Löschanfrage bestätigen' : 'Confirm Deletion Request' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }
        .content {
            padding: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-list {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $companyName }}</h1>
    </div>
    
    <div class="content">
        @if($locale === 'de')
            <h2>Bestätigung Ihrer Löschanfrage</h2>
            
            <p>Guten Tag {{ $customerName }},</p>
            
            <p>wir haben Ihre Anfrage zur Löschung Ihrer personenbezogenen Daten gemäß Artikel 17 DSGVO erhalten.</p>
            
            <div class="warning-box">
                <strong>⚠️ Wichtiger Hinweis:</strong><br>
                Die Löschung Ihrer Daten ist unwiderruflich. Nach der Bestätigung werden folgende Daten gelöscht oder anonymisiert:
            </div>
            
            <div class="info-list">
                <strong>Betroffene Daten:</strong>
                <ul>
                    <li>Ihre persönlichen Informationen (Name, E-Mail, Telefon, Adresse)</li>
                    <li>Ihre Terminhistorie wird anonymisiert</li>
                    <li>Ihre Kommunikationshistorie wird gelöscht</li>
                    <li>Alle Marketingeinwilligungen werden widerrufen</li>
                </ul>
            </div>
            
            <p><strong>Bitte beachten Sie:</strong> Aus rechtlichen Gründen müssen wir bestimmte Daten (z.B. Rechnungsdaten) gemäß den gesetzlichen Aufbewahrungsfristen weiter speichern.</p>
            
            <p>Um die Löschung zu bestätigen, klicken Sie bitte auf den folgenden Button:</p>
            
            <div style="text-align: center;">
                <a href="{{ $confirmationLink }}" class="button">Löschung bestätigen</a>
            </div>
            
            <p><small>Dieser Link ist aus Sicherheitsgründen nur bis zum <strong>{{ $expiresAt }}</strong> gültig.</small></p>
            
            <p>Falls Sie diese Anfrage nicht gestellt haben, ignorieren Sie bitte diese E-Mail. Ihre Daten bleiben unverändert.</p>
            
        @else
            <h2>Confirm Your Deletion Request</h2>
            
            <p>Hello {{ $customerName }},</p>
            
            <p>We have received your request to delete your personal data in accordance with Article 17 GDPR.</p>
            
            <div class="warning-box">
                <strong>⚠️ Important Notice:</strong><br>
                The deletion of your data is irreversible. After confirmation, the following data will be deleted or anonymized:
            </div>
            
            <div class="info-list">
                <strong>Affected Data:</strong>
                <ul>
                    <li>Your personal information (name, email, phone, address)</li>
                    <li>Your appointment history will be anonymized</li>
                    <li>Your communication history will be deleted</li>
                    <li>All marketing consents will be revoked</li>
                </ul>
            </div>
            
            <p><strong>Please note:</strong> For legal reasons, we must continue to store certain data (e.g., invoice data) in accordance with statutory retention periods.</p>
            
            <p>To confirm the deletion, please click the following button:</p>
            
            <div style="text-align: center;">
                <a href="{{ $confirmationLink }}" class="button">Confirm Deletion</a>
            </div>
            
            <p><small>For security reasons, this link is only valid until <strong>{{ $expiresAt }}</strong>.</small></p>
            
            <p>If you did not make this request, please ignore this email. Your data will remain unchanged.</p>
        @endif
    </div>
    
    <div class="footer">
        @if($locale === 'de')
            <p>Falls Sie Fragen haben, kontaktieren Sie uns bitte bevor Sie die Löschung bestätigen.</p>
            <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.</p>
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Alle Rechte vorbehalten.</p>
        @else
            <p>If you have any questions, please contact us before confirming the deletion.</p>
            <p>This email was generated automatically. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
        @endif
    </div>
</body>
</html>