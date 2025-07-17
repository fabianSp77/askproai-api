<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $frequency === 'hourly' ? 'Stündliche' : 'Tägliche' }} Anrufzusammenfassung</title>
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
            max-width: 800px;
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
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-card h3 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        .stat-card .label {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .calls-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .calls-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
            font-size: 14px;
            color: #666;
        }
        .calls-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        .calls-table tr:hover {
            background: #f8f9fa;
        }
        .urgency-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .urgency-urgent {
            background: #fee;
            color: #c33;
        }
        .urgency-high {
            background: #ffeaa7;
            color: #d63031;
        }
        .urgency-normal {
            background: #dfe6e9;
            color: #2d3436;
        }
        .appointment-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 30px 0 20px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .cta-button:hover {
            background: #5a67d8;
        }
        .time-period {
            background: #e8f4fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .time-period p {
            margin: 0;
            color: #1976D2;
        }
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
            .calls-table {
                font-size: 12px;
            }
            .calls-table th,
            .calls-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $company->name }}</h1>
            <p>{{ $frequency === 'hourly' ? 'Stündliche' : 'Tägliche' }} Anrufzusammenfassung</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Time Period -->
            <div class="time-period">
                <p><strong>Zeitraum:</strong> {{ $startTime->format('d.m.Y H:i') }} - {{ $endTime->format('d.m.Y H:i') }} Uhr</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Anrufe Gesamt</h3>
                    <p class="value">{{ $totalCalls }}</p>
                </div>
                <div class="stat-card">
                    <h3>Gesamtdauer</h3>
                    <p class="value">{{ gmdate('H:i', $totalDuration) }}</p>
                    <p class="label">Stunden:Minuten</p>
                </div>
                <div class="stat-card">
                    <h3>Termine Gebucht</h3>
                    <p class="value">{{ $appointmentsBooked }}</p>
                </div>
                <div class="stat-card">
                    <h3>Dringende Anrufe</h3>
                    <p class="value">{{ $urgentCalls }}</p>
                </div>
            </div>

            <!-- Urgent Calls Section -->
            @if($urgentCalls > 0)
            <h2 class="section-title">⚡ Dringende Anrufe</h2>
            <table class="calls-table">
                <thead>
                    <tr>
                        <th>Zeit</th>
                        <th>Anrufer</th>
                        <th>Telefon</th>
                        <th>Zusammenfassung</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calls->where('urgency_level', 'urgent') as $call)
                    <tr>
                        <td>{{ $call->created_at->format('H:i') }}</td>
                        <td>{{ $call->customer?->name ?? 'Unbekannt' }}</td>
                        <td>{{ $call->phone_number ?? '-' }}</td>
                        <td>{{ Str::limit($call->summary ?? 'Keine Zusammenfassung', 100) }}</td>
                        <td>
                            <span class="urgency-badge urgency-urgent">Dringend</span>
                            @if($call->appointment_id)
                                <span class="appointment-badge">Termin gebucht</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            <!-- All Calls Section -->
            <h2 class="section-title">📞 Alle Anrufe</h2>
            <table class="calls-table">
                <thead>
                    <tr>
                        <th>Zeit</th>
                        <th>Anrufer</th>
                        <th>Telefon</th>
                        <th>Dauer</th>
                        <th>Filiale</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calls as $call)
                    <tr>
                        <td>{{ $call->created_at->format('H:i') }}</td>
                        <td>{{ $call->customer?->name ?? 'Unbekannt' }}</td>
                        <td>{{ $call->phone_number ?? '-' }}</td>
                        <td>{{ gmdate('i:s', $call->duration_sec ?? 0) }}</td>
                        <td>{{ $call->branch?->name ?? '-' }}</td>
                        <td>
                            @if($call->urgency_level === 'urgent')
                                <span class="urgency-badge urgency-urgent">Dringend</span>
                            @elseif($call->urgency_level === 'high')
                                <span class="urgency-badge urgency-high">Hoch</span>
                            @else
                                <span class="urgency-badge urgency-normal">Normal</span>
                            @endif
                            @if($call->appointment_id)
                                <span class="appointment-badge">Termin</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Call to Action -->
            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ config('app.url') }}/admin/calls" class="cta-button">
                    Alle Anrufe im Dashboard ansehen
                </a>
            </div>

            <!-- Additional Info -->
            @if($includeCsv)
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="margin: 0 0 10px; font-size: 16px;">📎 CSV-Export im Anhang</h3>
                <p style="margin: 0; font-size: 14px; color: #666;">
                    Eine detaillierte CSV-Datei mit allen Anrufdaten ist dieser E-Mail angehängt.
                    Sie können diese Datei in Excel oder anderen Tabellenkalkulationsprogrammen öffnen.
                </p>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Diese {{ $frequency === 'hourly' ? 'stündliche' : 'tägliche' }} Zusammenfassung wurde automatisch von {{ $company->name }} generiert.</p>
            <p style="margin-top: 10px;">
                <a href="{{ config('app.url') }}/admin/settings" style="color: #667eea; text-decoration: none;">
                    Benachrichtigungseinstellungen ändern
                </a>
            </p>
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                {{ config('app.url') }} • © {{ date('Y') }} {{ $company->name }}
            </p>
        </div>
    </div>
</body>
</html>