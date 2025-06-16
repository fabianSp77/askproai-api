<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailData['subject'] }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        h1 {
            color: #1F2937;
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            background-color: #F3F4F6;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #E5E7EB;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .label {
            font-weight: 600;
            color: #6B7280;
        }
        .value {
            color: #1F2937;
        }
        .transcript-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #F9FAFB;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
        }
        .transcript-header {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .transcript-content {
            white-space: pre-wrap;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            line-height: 1.8;
            color: #374151;
        }
        .custom-message {
            background-color: #EFF6FF;
            border-left: 4px solid #3B82F6;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #6B7280;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-completed {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .status-failed {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        .status-in-progress {
            background-color: #FEF3C7;
            color: #92400E;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Anrufaufzeichnung</h1>
        </div>

        @if(!empty($emailData['custom_message']))
        <div class="custom-message">
            <p>{{ $emailData['custom_message'] }}</p>
        </div>
        @endif

        <div class="info-section">
            <div class="info-row">
                <span class="label">Datum & Zeit:</span>
                <span class="value">{{ $emailData['call']->created_at->format('d.m.Y H:i') }} Uhr</span>
            </div>
            
            <div class="info-row">
                <span class="label">Dauer:</span>
                <span class="value">{{ gmdate('i:s', $emailData['call']->duration_sec ?? 0) }} Min</span>
            </div>

            @if($emailData['call']->customer)
            <div class="info-row">
                <span class="label">Kunde:</span>
                <span class="value">{{ $emailData['call']->customer->name }}</span>
            </div>
            @endif

            @if($emailData['call']->phone_number)
            <div class="info-row">
                <span class="label">Telefonnummer:</span>
                <span class="value">{{ $emailData['call']->phone_number }}</span>
            </div>
            @endif

            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value">
                    @if($emailData['call']->status === 'completed')
                        <span class="status-badge status-completed">Abgeschlossen</span>
                    @elseif($emailData['call']->status === 'failed')
                        <span class="status-badge status-failed">Fehlgeschlagen</span>
                    @else
                        <span class="status-badge status-in-progress">In Bearbeitung</span>
                    @endif
                </span>
            </div>

            @if($emailData['call']->appointment)
            <div class="info-row">
                <span class="label">Termin gebucht:</span>
                <span class="value">{{ $emailData['call']->appointment->starts_at->format('d.m.Y H:i') }} Uhr</span>
            </div>
            @endif
        </div>

        @if(!empty($emailData['call']->analysis))
        <div class="info-section">
            <h3 style="margin-top: 0; color: #1F2937;">Zusammenfassung</h3>
            <p style="margin: 0; color: #374151;">{{ $emailData['call']->analysis }}</p>
        </div>
        @endif

        @if(!empty($emailData['call']->transcript))
        <div class="transcript-section">
            <div class="transcript-header">Gesprächsprotokoll</div>
            <div class="transcript-content">{{ $emailData['call']->transcript }}</div>
        </div>
        @endif

        <div class="footer">
            <p>Diese E-Mail wurde von {{ $emailData['sender_name'] }} ({{ $emailData['sender_email'] }}) über {{ config('app.name') }} gesendet.</p>
            <p style="font-size: 12px; color: #9CA3AF;">© {{ date('Y') }} {{ config('app.name') }}. Alle Rechte vorbehalten.</p>
        </div>
    </div>
</body>
</html>