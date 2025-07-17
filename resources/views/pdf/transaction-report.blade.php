<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaktionsbericht - {{ $company->name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
        }
        
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 14pt;
            color: #6b7280;
        }
        
        .meta-info {
            margin-top: 10px;
            color: #6b7280;
            font-size: 9pt;
        }
        
        .summary-box {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
        }
        
        .summary-value {
            font-size: 16pt;
            font-weight: bold;
            color: #1f2937;
        }
        
        .summary-label {
            font-size: 9pt;
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
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .credit {
            color: #059669;
            font-weight: bold;
        }
        
        .debit {
            color: #dc2626;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 8pt;
            color: #6b7280;
            text-align: center;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @page {
            margin: 2cm 1.5cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name }}</div>
        <div class="report-title">Transaktionsbericht</div>
        <div class="meta-info">
            Zeitraum: {{ $summary['date_from'] }} bis {{ $summary['date_to'] }}<br>
            Erstellt am: {{ now()->format('d.m.Y H:i') }} Uhr
        </div>
    </div>
    
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ $summary['total_transactions'] }}</div>
                <div class="summary-label">Transaktionen</div>
            </div>
            <div class="summary-item">
                <div class="summary-value credit">+{{ number_format($summary['total_credits'], 2, ',', '.') }}€</div>
                <div class="summary-label">Aufladungen</div>
            </div>
            <div class="summary-item">
                <div class="summary-value debit">-{{ number_format($summary['total_debits'], 2, ',', '.') }}€</div>
                <div class="summary-label">Verbrauch</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">{{ number_format($summary['total_credits'] - $summary['total_debits'], 2, ',', '.') }}€</div>
                <div class="summary-label">Differenz</div>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 15%">Datum</th>
                <th style="width: 8%">Typ</th>
                <th style="width: 35%">Beschreibung</th>
                <th style="width: 12%">Referenz</th>
                <th style="width: 15%; text-align: right">Betrag</th>
                <th style="width: 15%; text-align: right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('d.m.Y H:i') }}</td>
                    <td>
                        @if($transaction->type === 'credit')
                            <span style="color: #059669;">Aufladung</span>
                        @else
                            <span style="color: #dc2626;">Verbrauch</span>
                        @endif
                    </td>
                    <td>{{ $transaction->description }}</td>
                    <td>
                        @if($transaction->reference_type && $transaction->reference_id)
                            {{ class_basename($transaction->reference_type) }} #{{ $transaction->reference_id }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: right;" class="{{ $transaction->type }}">
                        {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2, ',', '.') }}€
                    </td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ number_format($transaction->balance_after, 2, ',', '.') }}€
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    @if($transactions->count() >= 500)
        <p style="margin-top: 20px; font-size: 9pt; color: #6b7280;">
            Hinweis: Es werden maximal 500 Transaktionen angezeigt. Für eine vollständige Übersicht nutzen Sie bitte den CSV-Export.
        </p>
    @endif
    
    <div class="footer">
        <p>
            Dieser Bericht wurde automatisch erstellt von {{ config('app.name') }}<br>
            Seite 1 von 1
        </p>
    </div>
</body>
</html>