<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Neue Kundenanfrage - {{ $customer_name }}</title>
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
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
        }
        .info-row {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        .label {
            font-weight: bold;
            color: #495057;
            display: inline-block;
            width: 150px;
        }
        .value {
            color: #212529;
        }
        .request-box {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .urgent {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🔔 Neue Kundenanfrage eingegangen</h2>
        <p>Krückeberg Servicegruppe hat einen Anruf für Sie entgegengenommen</p>
    </div>

    <div class="content">
        <h3>📞 Anruferdetails</h3>
        
        <div class="info-row">
            <span class="label">Name:</span>
            <span class="value">{{ $customer_name }}</span>
        </div>

        <div class="info-row">
            <span class="label">Firma:</span>
            <span class="value">{{ $company }}</span>
        </div>

        <div class="info-row">
            <span class="label">Telefon:</span>
            <span class="value">{{ $phone }}</span>
        </div>

        <div class="info-row">
            <span class="label">E-Mail:</span>
            <span class="value">{{ $email }}</span>
        </div>

        <div class="info-row">
            <span class="label">Anrufzeit:</span>
            <span class="value">{{ $call_time }} Uhr</span>
        </div>

        <div class="info-row">
            <span class="label">Gesprächsdauer:</span>
            <span class="value">{{ $call_duration }}</span>
        </div>

        <h3>📝 Kundenanliegen</h3>
        <div class="request-box">
            <strong>Anliegen:</strong><br>
            {{ $request }}
        </div>

        @if($notes != 'Keine')
        <div class="request-box">
            <strong>Weitere Notizen:</strong><br>
            {{ $notes }}
        </div>
        @endif

        <p class="urgent">⚡ Bitte kontaktieren Sie den Kunden schnellstmöglich zurück!</p>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch von der Krückeberg Servicegruppe generiert.</p>
        <p>© {{ date('Y') }} AskProAI - Powered by AI</p>
    </div>
</body>
</html>