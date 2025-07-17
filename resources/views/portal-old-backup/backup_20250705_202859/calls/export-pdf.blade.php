<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anrufe Export - {{ now()->format('d.m.Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        .header .info {
            color: #6b7280;
            font-size: 14px;
        }
        .summary {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .summary-item .label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #f3f4f6;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: #1f2937;
        }
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 8px;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .urgency-high {
            color: #dc2626;
            font-weight: bold;
        }
        .urgency-medium {
            color: #f59e0b;
            font-weight: bold;
        }
        .urgency-low {
            color: #6b7280;
        }
        .status-new {
            color: #3b82f6;
        }
        .status-in_progress {
            color: #f59e0b;
        }
        .status-requires_action {
            color: #dc2626;
            font-weight: bold;
        }
        .status-completed {
            color: #10b981;
        }
        .cost {
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Anrufe Export</h1>
        <div class="info">
            Exportiert am {{ now()->format('d.m.Y \u\m H:i') }} Uhr<br>
            {{ $calls->count() }} Anrufe ausgewählt
        </div>
    </div>

    @if($showSummary)
    <div class="summary">
        <h2 style="margin: 0 0 15px 0; font-size: 16px;">Zusammenfassung</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="value">{{ $calls->count() }}</div>
                <div class="label">Anrufe gesamt</div>
            </div>
            <div class="summary-item">
                <div class="value">{{ gmdate('H:i:s', $calls->sum('duration_sec')) }}</div>
                <div class="label">Gesamtdauer</div>
            </div>
            @if($showCosts)
            <div class="summary-item">
                <div class="value">{{ number_format($totalCost, 2, ',', '.') }} €</div>
                <div class="label">Gesamtkosten</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Datum/Zeit</th>
                <th>Anrufer</th>
                <th>Kunde</th>
                <th>Anliegen</th>
                <th>Dringlichkeit</th>
                <th>Status</th>
                <th>Dauer</th>
                @if($showCosts)
                <th>Kosten</th>
                @endif
                <th>Zugewiesen</th>
                <th>Filiale</th>
            </tr>
        </thead>
        <tbody>
            @foreach($calls as $call)
            <tr>
                <td>
                    {{ $call->created_at->format('d.m.Y') }}<br>
                    <small style="color: #6b7280;">{{ $call->created_at->format('H:i') }}</small>
                </td>
                <td>{{ $call->phone_number }}</td>
                <td>
                    <strong>{{ $call->extracted_name ?? $call->customer->name ?? 'Unbekannt' }}</strong>
                    @if($call->metadata['customer_data']['company'] ?? $call->customer->company_name ?? null)
                        <br><small style="color: #6b7280;">{{ $call->metadata['customer_data']['company'] ?? $call->customer->company_name }}</small>
                    @endif
                </td>
                <td>{{ Str::limit($call->reason_for_visit ?? '-', 50) }}</td>
                <td>
                    @php
                        $urgency = $call->urgency_level ?? $call->metadata['customer_data']['urgency'] ?? 'low';
                        $urgencyClass = match(strtolower($urgency)) {
                            'high', 'hoch' => 'urgency-high',
                            'medium', 'mittel' => 'urgency-medium',
                            default => 'urgency-low'
                        };
                        $urgencyDisplay = match(strtolower($urgency)) {
                            'high' => 'Hoch',
                            'medium' => 'Mittel',
                            'low' => 'Niedrig',
                            default => ucfirst($urgency)
                        };
                    @endphp
                    <span class="{{ $urgencyClass }}">{{ $urgencyDisplay }}</span>
                </td>
                <td>
                    @php
                        $status = $call->callPortalData->status ?? 'new';
                        $statusClass = 'status-' . $status;
                        $statusDisplay = match($status) {
                            'new' => 'Neu',
                            'in_progress' => 'In Bearbeitung',
                            'requires_action' => 'Aktion erforderlich',
                            'completed' => 'Abgeschlossen',
                            'callback_scheduled' => 'Rückruf geplant',
                            default => ucfirst($status)
                        };
                    @endphp
                    <span class="{{ $statusClass }}">{{ $statusDisplay }}</span>
                </td>
                <td>{{ gmdate('H:i:s', $call->duration_sec ?? 0) }}</td>
                @if($showCosts)
                <td class="cost">
                    @if($call->cost)
                        {{ number_format($call->cost, 2, ',', '.') }} €
                    @else
                        -
                    @endif
                </td>
                @endif
                <td>{{ $call->callPortalData->assignedTo->name ?? 'Nicht zugewiesen' }}</td>
                <td>{{ $call->branch->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>
            Dieser Export wurde automatisch generiert von AskProAI<br>
            © {{ date('Y') }} AskProAI - Alle Rechte vorbehalten
        </p>
    </div>
</body>
</html>