<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Rückruf-Anfrage</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .field {
            margin-bottom: 15px;
        }
        .label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .value {
            font-size: 16px;
            color: #333;
        }
        .priority-high {
            color: #dc3545;
            font-weight: 600;
        }
        .priority-normal {
            color: #28a745;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
        .badge-reschedule {
            background: #fff3cd;
            color: #856404;
        }
        .badge-cancel {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-other {
            background: #d1ecf1;
            color: #0c5460;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 600;
        }
        .button:hover {
            background: #5568d3;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        @php
            $action = $callback->metadata['action_requested'] ?? 'request';
            $emoji = match($action) {
                'reschedule' => '🔄',
                'cancel' => '❌',
                default => '📞'
            };
        @endphp
        <h1>{{ $emoji }} Neue Rückruf-Anfrage</h1>
    </div>

    <div class="content">
        <div class="card">
            <div class="field">
                <div class="label">Kunde</div>
                <div class="value">{{ $callback->customer_name }}</div>
            </div>

            <div class="field">
                <div class="label">Telefonnummer</div>
                <div class="value">{{ $callback->phone_number }}</div>
            </div>

            @if($callback->customer_email)
            <div class="field">
                <div class="label">E-Mail</div>
                <div class="value">{{ $callback->customer_email }}</div>
            </div>
            @endif

            <div class="field">
                <div class="label">Gewünschte Aktion</div>
                <div class="value">
                    @php
                        $actionText = match($action) {
                            'reschedule' => 'Termin verschieben',
                            'cancel' => 'Termin stornieren',
                            default => 'Rückruf'
                        };
                        $badgeClass = match($action) {
                            'reschedule' => 'badge-reschedule',
                            'cancel' => 'badge-cancel',
                            default => 'badge-other'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $actionText }}</span>
                </div>
            </div>

            <div class="field">
                <div class="label">Priorität</div>
                <div class="value {{ $callback->priority === 'high' ? 'priority-high' : 'priority-normal' }}">
                    {{ ucfirst($callback->priority) }}
                </div>
            </div>

            @if($callback->notes)
            <div class="field">
                <div class="label">Notizen</div>
                <div class="value">{{ $callback->notes }}</div>
            </div>
            @endif

            <div class="field">
                <div class="label">Erstellt</div>
                <div class="value">{{ $callback->created_at->format('d.m.Y H:i') }} Uhr</div>
            </div>

            <div class="field">
                <div class="label">Läuft ab</div>
                <div class="value">{{ $callback->expires_at->format('d.m.Y H:i') }} Uhr</div>
            </div>
        </div>

        @if(isset($callback->metadata['call_id']))
        <div class="card">
            <div class="label">Technische Details</div>
            <div class="value" style="font-size: 12px; color: #666; margin-top: 10px;">
                Call ID: {{ $callback->metadata['call_id'] }}<br>
                @if(isset($callback->metadata['appointment_date']))
                    Datum: {{ $callback->metadata['appointment_date'] }}<br>
                @endif
                @if(isset($callback->metadata['new_time']))
                    Neue Zeit: {{ $callback->metadata['new_time'] }}<br>
                @endif
            </div>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ route('filament.admin.resources.callback-requests.view', ['record' => $callback->id]) }}" class="button">
                Details anzeigen
            </a>
        </div>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert von {{ $company->name }}</p>
        <p>AskPro AI Gateway - Appointment Management System</p>
    </div>
</body>
</html>
