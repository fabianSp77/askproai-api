<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin verschoben</title>
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
        .success-icon {
            font-size: 48px;
            color: #10b981;
            margin-bottom: 10px;
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .appointment-card {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            border: 1px solid #e5e7eb;
        }
        .appointment-detail {
            display: flex;
            align-items: start;
            margin: 12px 0;
            padding: 8px 0;
        }
        .detail-icon {
            width: 24px;
            margin-right: 12px;
            color: #6b7280;
        }
        .detail-content strong {
            display: block;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 2px;
        }
        .detail-content span {
            color: #4b5563;
            font-size: 16px;
        }
        .old-appointment {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            padding: 16px;
            border-radius: 6px;
            margin: 16px 0;
            text-decoration: line-through;
            color: #991b1b;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'AskPro') }}</div>
            <div class="success-icon">✓</div>
            <h1>Termin erfolgreich verschoben</h1>
        </div>

        <div class="content">
            <p>Hallo {{ $appointment->customer_name }},</p>

            <p>Ihr Termin wurde erfolgreich verschoben. Hier sind Ihre neuen Termindetails:</p>

            <div class="appointment-card">
                <div class="appointment-detail">
                    <div class="detail-icon">📅</div>
                    <div class="detail-content">
                        <strong>Neuer Termin</strong>
                        <span>{{ $appointment->appointment_time->format('l, d. F Y') }}</span>
                    </div>
                </div>

                <div class="appointment-detail">
                    <div class="detail-icon">🕐</div>
                    <div class="detail-content">
                        <strong>Uhrzeit</strong>
                        <span>{{ $appointment->appointment_time->format('H:i') }} Uhr</span>
                    </div>
                </div>

                <div class="appointment-detail">
                    <div class="detail-icon">✂️</div>
                    <div class="detail-content">
                        <strong>Service</strong>
                        <span>{{ $appointment->service->name }}</span>
                    </div>
                </div>

                @if($appointment->staff)
                <div class="appointment-detail">
                    <div class="detail-icon">👤</div>
                    <div class="detail-content">
                        <strong>Mitarbeiter</strong>
                        <span>{{ $appointment->staff->name }}</span>
                    </div>
                </div>
                @endif

                @if($appointment->branch)
                <div class="appointment-detail">
                    <div class="detail-icon">📍</div>
                    <div class="detail-content">
                        <strong>Filiale</strong>
                        <span>{{ $appointment->branch->name }}</span>
                    </div>
                </div>
                @endif

                @if($appointment->duration_minutes)
                <div class="appointment-detail">
                    <div class="detail-icon">⏱️</div>
                    <div class="detail-content">
                        <strong>Dauer</strong>
                        <span>{{ $appointment->duration_minutes }} Minuten</span>
                    </div>
                </div>
                @endif
            </div>

            @if(isset($oldAppointmentTime))
            <div class="info-box">
                <strong>Alter Termin:</strong> {{ $oldAppointmentTime->format('d.m.Y \u\m H:i') }} Uhr
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ route('customer-portal.appointments.index') }}" class="cta-button">
                    Termin im Portal anzeigen
                </a>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
                <strong>Wichtig:</strong> Bitte erscheinen Sie pünktlich zu Ihrem Termin. Falls Sie den Termin nicht wahrnehmen können, bitten wir Sie, ihn rechtzeitig über das Kundenportal abzusagen.
            </p>
        </div>

        <div class="footer">
            <p>Bei Fragen erreichen Sie uns unter:</p>
            <p><strong>{{ $appointment->company->email ?? config('mail.from.address') }}</strong></p>
            <p style="margin-top: 20px; font-size: 11px; color: #9ca3af;">
                © {{ date('Y') }} {{ $appointment->company->name }}. Alle Rechte vorbehalten.
            </p>
        </div>
    </div>
</body>
</html>
