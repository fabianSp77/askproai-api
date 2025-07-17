<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anrufzusammenfassung</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .info-item h3 {
            margin: 0 0 5px;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }
        .info-item p {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .summary-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .summary-section h2 {
            margin: 0 0 15px;
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
        }
        .summary-section h2::before {
            content: "📋";
            margin-right: 10px;
        }
        .action-items {
            margin: 20px 0;
        }
        .action-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: start;
        }
        .action-item.urgent {
            border-color: #dc3545;
            border-width: 2px;
        }
        .action-item.high {
            border-color: #ffc107;
            border-width: 2px;
        }
        .action-icon {
            margin-right: 15px;
            font-size: 24px;
        }
        .action-content h3 {
            margin: 0 0 5px;
            font-size: 16px;
            color: #333;
        }
        .action-content p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        .transcript {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .transcript h3 {
            margin: 0 0 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        .transcript h3::before {
            content: "💬";
            margin-right: 10px;
        }
        .transcript-content {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
        }
        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .cta-button:hover {
            background: #5a67d8;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .urgency-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .urgency-urgent {
            background: #dc3545;
            color: white;
        }
        .urgency-high {
            background: #ffc107;
            color: #333;
        }
        .urgency-normal {
            background: #28a745;
            color: white;
        }
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .container {
                margin: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $company->name }}</h1>
            <p>Neue Anrufzusammenfassung</p>
        </div>

        <!-- Content -->
        <div class="content">
            @if($customMessage)
            <div style="background: #e8f4fd; border-left: 4px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <p style="margin: 0; color: #1976D2;">{{ $customMessage }}</p>
            </div>
            @endif

            <!-- Call Information Grid -->
            <div class="info-grid">
                <div class="info-item">
                    <h3>Anrufer</h3>
                    <p>{{ $call->customer?->name ?? 'Unbekannt' }}</p>
                </div>
                <div class="info-item">
                    <h3>Telefonnummer</h3>
                    <p>{{ $call->from_number ?? $call->phone_number ?? 'Nicht verfügbar' }}</p>
                </div>
                <div class="info-item">
                    <h3>Datum & Uhrzeit</h3>
                    <p>{{ $call->created_at->format('d.m.Y H:i') }} Uhr</p>
                </div>
                <div class="info-item">
                    <h3>Dauer</h3>
                    <p>{{ $callDuration }}</p>
                </div>
            </div>

            <!-- Urgency Level -->
            @if($urgencyLevel)
            <div style="margin-bottom: 20px; display: flex; align-items: center;">
                <span style="font-weight: 600; margin-right: 10px;">Dringlichkeit:</span>
                @if($urgencyLevel === 'urgent')
                    <span class="urgency-badge urgency-urgent">Dringend</span>
                @elseif($urgencyLevel === 'high')
                    <span class="urgency-badge urgency-high">Hoch</span>
                @else
                    <span class="urgency-badge urgency-normal">Normal</span>
                @endif
            </div>
            @endif

            <!-- Summary Section -->
            <div class="summary-section">
                <h2>Zusammenfassung</h2>
                <p>{{ $call->summary ?? 'Keine Zusammenfassung verfügbar.' }}</p>
                
                @if($hasAppointment)
                <div style="margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 6px; color: #155724;">
                    <strong>✅ Termin wurde gebucht</strong>
                </div>
                @endif
            </div>

            <!-- Action Items -->
            @if(count($actionItems) > 0)
            <div class="action-items">
                <h2 style="font-size: 18px; margin-bottom: 15px;">🎯 Erforderliche Maßnahmen</h2>
                @foreach($actionItems as $item)
                <div class="action-item {{ $item['priority'] }}">
                    <div class="action-icon">
                        @if($item['type'] === 'appointment_needed')
                            📅
                        @elseif($item['type'] === 'callback_needed')
                            📞
                        @elseif($item['type'] === 'urgent_followup')
                            ⚡
                        @else
                            ✏️
                        @endif
                    </div>
                    <div class="action-content">
                        <h3>{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Dynamic Variables -->
            @if($call->dynamic_variables && count($call->dynamic_variables) > 0)
            <div class="summary-section">
                <h2>📊 Erfasste Informationen</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    @foreach($call->dynamic_variables as $key => $value)
                    @if(!in_array($key, ['caller_id', 'to_number', 'from_number', 'direction', 'twilio_call_sid']))
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; font-weight: 600; width: 40%;">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #e0e0e0;">
                            {{ is_array($value) ? json_encode($value) : $value }}
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </table>
            </div>
            @endif

            <!-- Transcript -->
            @if($includeTranscript && $call->transcript)
            <div class="transcript">
                <h3>Gesprächsverlauf</h3>
                <div class="transcript-content">{{ $call->transcript }}</div>
            </div>
            @endif

            <!-- CTA Button -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/admin/calls/{{ $call->id }}" class="cta-button">
                    Anruf im Dashboard ansehen
                </a>
            </div>

            <!-- Customer Info -->
            @if($call->customer)
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <h3 style="margin: 0 0 10px; font-size: 16px;">Kundeninformationen</h3>
                @if($call->customer->email)
                <p style="margin: 5px 0;"><strong>E-Mail:</strong> {{ $call->customer->email }}</p>
                @endif
                @if($call->customer->address)
                <p style="margin: 5px 0;"><strong>Adresse:</strong> {{ $call->customer->address }}</p>
                @endif
                @if($call->customer->last_contact_at)
                <p style="margin: 5px 0;"><strong>Letzter Kontakt:</strong> {{ $call->customer->last_contact_at->format('d.m.Y') }}</p>
                @endif
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Diese E-Mail wurde automatisch von {{ $company->name }} generiert.</p>
            <p>{{ config('app.url') }}</p>
            @if($recipientType === 'external')
            <p style="margin-top: 10px;">
                <small>Diese E-Mail enthält vertrauliche Informationen. Wenn Sie nicht der beabsichtigte Empfänger sind, löschen Sie bitte diese E-Mail.</small>
            </p>
            @endif
        </div>
    </div>
</body>
</html>