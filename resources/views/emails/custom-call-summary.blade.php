<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $call->company->name ?? 'AskProAI' }} - Anrufzusammenfassung</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #1e40af;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 18px;
        }
        .info-box p {
            margin: 8px 0;
        }
        .appointment-box {
            background-color: #fef3c7;
            border-left-color: #f59e0b;
        }
        .action-box {
            background-color: #fee2e2;
            border-left-color: #ef4444;
        }
        .custom-content {
            margin: 30px 0;
            padding: 20px 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            font-weight: 600;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ $call->company->name ?? 'AskProAI' }}</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Anrufzusammenfassung</p>
        </div>

        <div class="content">
            @if($customerInfo)
            <div class="info-box">
                <h3>📞 Anruferinformationen</h3>
                <table>
                    <tr>
                        <th>Name:</th>
                        <td>{{ $customerInfo['name'] }}</td>
                    </tr>
                    <tr>
                        <th>Telefon:</th>
                        <td>{{ $customerInfo['phone'] }}</td>
                    </tr>
                    @if($customerInfo['email'])
                    <tr>
                        <th>E-Mail:</th>
                        <td>{{ $customerInfo['email'] }}</td>
                    </tr>
                    @endif
                    @if($customerInfo['company'])
                    <tr>
                        <th>Firma:</th>
                        <td>{{ $customerInfo['company'] }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            @endif

            @if($summary)
            <div class="info-box">
                <h3>📋 Zusammenfassung</h3>
                <p>{{ $summary }}</p>
            </div>
            @endif

            @if($appointmentInfo)
            <div class="info-box appointment-box">
                <h3>📅 Terminanfrage</h3>
                <table>
                    <tr>
                        <th>Datum:</th>
                        <td>{{ $appointmentInfo['date'] ?? 'Nicht angegeben' }}</td>
                    </tr>
                    <tr>
                        <th>Uhrzeit:</th>
                        <td>{{ $appointmentInfo['time'] ?? 'Nicht angegeben' }}</td>
                    </tr>
                    <tr>
                        <th>Dienstleistung:</th>
                        <td>{{ $appointmentInfo['service'] ?? 'Nicht angegeben' }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>{{ $appointmentInfo['made'] ? '✅ Termin vereinbart' : '⏳ Noch offen' }}</td>
                    </tr>
                </table>
            </div>
            @endif

            @if($actionItems && count($actionItems) > 0)
            <div class="info-box action-box">
                <h3>⚡ Handlungsempfehlungen</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($actionItems as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($includeOptions['transcript'] ?? false)
            <div class="info-box">
                <h3>💬 Transkript</h3>
                <div style="max-height: 400px; overflow-y: auto; padding: 10px; background: white; border-radius: 4px;">
                    {!! nl2br(e($call->transcript)) !!}
                </div>
            </div>
            @endif

            <div class="custom-content">
                {!! $customContent !!}
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="https://api.askproai.de/business/calls/{{ $call->id }}/v2" class="button">
                    Anruf im Portal anzeigen
                </a>
            </div>
        </div>

        <div class="footer">
            <p>
                Diese E-Mail wurde automatisch von AskProAI generiert.<br>
                Bei Fragen wenden Sie sich bitte an 
                <a href="mailto:fabian@askproai.de">fabian@askproai.de</a>
            </p>
            <p style="margin-top: 15px;">
                <a href="https://askproai.de">askproai.de</a> | 
                <a href="https://askproai.de/datenschutz">Datenschutz</a> | 
                <a href="https://askproai.de/impressum">Impressum</a>
            </p>
        </div>
    </div>
</body>
</html>