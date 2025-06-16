<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a90e2; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .info-row { margin: 10px 0; }
        .label { font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Terminbestätigung</h1>
            <p>{{ $companyName }}</p>
        </div>
        
        <div class="content">
            <h2>Hallo {{ $bookingData['responses']['name'] ?? 'Kunde' }},</h2>
            
            <p>Ihr Termin wurde erfolgreich gebucht. Hier sind die Details:</p>
            
            <div class="info-row">
                <span class="label">Datum:</span>
                {{ \Carbon\Carbon::parse($bookingData['startTime'])->format('d.m.Y') }}
            </div>
            
            <div class="info-row">
                <span class="label">Uhrzeit:</span>
                {{ \Carbon\Carbon::parse($bookingData['startTime'])->format('H:i') }} - 
                {{ \Carbon\Carbon::parse($bookingData['endTime'])->format('H:i') }} Uhr
            </div>
            
            @if(isset($bookingData['title']))
            <div class="info-row">
                <span class="label">Dienstleistung:</span>
                {{ $bookingData['title'] }}
            </div>
            @endif
            
            @if(isset($bookingData['videoCallUrl']))
            <div class="info-row">
                <span class="label">Video-Link:</span>
                <a href="{{ $bookingData['videoCallUrl'] }}">Zum Video-Call</a>
            </div>
            @endif
            
            <p style="margin-top: 20px;">
                Wir freuen uns auf Ihren Besuch!<br>
                Bei Fragen oder wenn Sie den Termin ändern möchten, rufen Sie uns bitte an.
            </p>
        </div>
        
        <div class="footer">
            <p>Diese E-Mail wurde automatisch generiert.</p>
            <p>© {{ date('Y') }} {{ $companyName }}</p>
        </div>
    </div>
</body>
</html>
