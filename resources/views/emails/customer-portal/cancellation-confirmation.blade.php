<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin storniert</title>
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
        .icon {
            font-size: 48px;
            color: #ef4444;
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
        .cancelled-appointment {
            background-color: #fef2f2;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            border: 2px solid #fca5a5;
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
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
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
        .reason-box {
            background-color: #f3f4f6;
            padding: 16px;
            border-radius: 6px;
            margin: 16px 0;
            border-left: 4px solid #6b7280;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'AskPro') }}</div>
            <div class="icon">✕</div>
            <h1>Termin storniert</h1>
        </div>

        <div class="content">
            <p>Hallo {{ $appointment->customer_name }},</p>

            <p>Ihr Termin wurde erfolgreich storniert. Hier sind die Details des stornierten Termins:</p>

            <div class="cancelled-appointment">
                <div class="appointment-detail">
                    <div class="detail-icon">📅</div>
                    <div class="detail-content">
                        <strong>Datum</strong>
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
            </div>

            @if(isset($cancellationReason) && $cancellationReason)
            <div class="reason-box">
                <strong>Stornierungsgrund:</strong><br>
                {{ $cancellationReason }}
            </div>
            @endif

            @if(isset($cancellationFee) && $cancellationFee > 0)
            <div class="info-box">
                <strong>Hinweis:</strong> Aufgrund der kurzfristigen Stornierung fällt eine Stornierungsgebühr von <strong>{{ number_format($cancellationFee, 2, ',', '.') }} €</strong> an.
            </div>
            @endif

            <p>Sie können jederzeit einen neuen Termin vereinbaren:</p>

            <div style="text-align: center;">
                <a href="{{ route('customer-portal.appointments.index') }}" class="cta-button">
                    Neuen Termin buchen
                </a>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #6b7280;">
                Wir bedauern, dass Sie Ihren Termin nicht wahrnehmen konnten. Wir freuen uns darauf, Sie bald wieder bei uns begrüßen zu dürfen!
            </p>
        </div>

        <div class="footer">
            <p>Bei Fragen erreichen Sie uns unter:</p>
            <p><strong>{{ $appointment->company->email ?? config('mail.from.address') }}</strong></p>
            <p style="margin-top: 20px;">
                Möchten Sie über verfügbare Termine informiert werden?
                <a href="{{ route('customer-portal.appointments.index') }}" style="color: #2563eb;">Benachrichtigungen aktivieren</a>
            </p>
            <p style="margin-top: 20px; font-size: 11px; color: #9ca3af;">
                © {{ date('Y') }} {{ $appointment->company->name }}. Alle Rechte vorbehalten.
            </p>
        </div>
    </div>
</body>
</html>
