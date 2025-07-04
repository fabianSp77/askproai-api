<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'de' ? 'Ihre Datenauskunft' : 'Your Data Request' }}</title>
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
            background-color: #007bff;
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
        .info-box {
            background-color: #e3f2fd;
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
            <h2>Ihre Datenauskunft ist bereit</h2>
            
            <p>Guten Tag {{ $customerName }},</p>
            
            <p>gemäß Ihrer Anfrage nach Artikel 15 DSGVO haben wir eine Kopie aller über Sie gespeicherten personenbezogenen Daten erstellt.</p>
            
            <div class="info-box">
                <strong>🔒 Sicherheitshinweis:</strong><br>
                Der Download-Link ist aus Sicherheitsgründen nur bis zum <strong>{{ $expiresAt }}</strong> gültig.
            </div>
            
            <p>Klicken Sie auf den folgenden Button, um Ihre Daten herunterzuladen:</p>
            
            <div style="text-align: center;">
                <a href="{{ $downloadLink }}" class="button">Daten herunterladen</a>
            </div>
            
            <p><strong>Die Datei enthält:</strong></p>
            <ul>
                <li>Ihre persönlichen Informationen</li>
                <li>Ihre Einwilligungen und Präferenzen</li>
                <li>Ihre Terminhistorie</li>
                <li>Ihre Kommunikationshistorie</li>
            </ul>
            
            <p>Die Daten werden als ZIP-Datei bereitgestellt, die eine JSON-Datei mit allen Informationen sowie eine Readme-Datei enthält.</p>
            
        @else
            <h2>Your Data Export is Ready</h2>
            
            <p>Hello {{ $customerName }},</p>
            
            <p>In accordance with your request under Article 15 GDPR, we have prepared a copy of all personal data we have stored about you.</p>
            
            <div class="info-box">
                <strong>🔒 Security Notice:</strong><br>
                For security reasons, the download link is only valid until <strong>{{ $expiresAt }}</strong>.
            </div>
            
            <p>Click the following button to download your data:</p>
            
            <div style="text-align: center;">
                <a href="{{ $downloadLink }}" class="button">Download Data</a>
            </div>
            
            <p><strong>The file contains:</strong></p>
            <ul>
                <li>Your personal information</li>
                <li>Your consents and preferences</li>
                <li>Your appointment history</li>
                <li>Your communication history</li>
            </ul>
            
            <p>The data is provided as a ZIP file containing a JSON file with all information and a readme file.</p>
        @endif
    </div>
    
    <div class="footer">
        @if($locale === 'de')
            <p>Falls Sie Fragen haben oder der Link nicht funktioniert, kontaktieren Sie uns bitte.</p>
            <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht auf diese E-Mail.</p>
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Alle Rechte vorbehalten.</p>
        @else
            <p>If you have any questions or if the link doesn't work, please contact us.</p>
            <p>This email was generated automatically. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
        @endif
    </div>
</body>
</html>