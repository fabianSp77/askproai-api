<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Kontoauszug {{ $statement_number }}</title>
    <style>
        @page {
            margin: 100px 50px 80px 50px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #333;
        }
        
        .header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 1px solid #6366f1;
        }
        
        .footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 40px;
            font-size: 8pt;
            color: #666;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .page-number:after {
            content: counter(page);
        }
        
        .statement-header {
            background-color: #f3f4f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .statement-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin: 0 0 10px 0;
        }
        
        .statement-period {
            font-size: 11pt;
            color: #6b7280;
        }
        
        .customer-info {
            margin-bottom: 30px;
            padding: 10px;
            background-color: #f9fafb;
            border-left: 3px solid #6366f1;
        }
        
        .summary-box {
            background-color: #eff6ff;
            padding: 15px;
            margin-bottom: 30px;
            border: 1px solid #bfdbfe;
            border-radius: 5px;
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
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
        }
        
        .summary-label {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .transactions-table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
            border-bottom: 2px solid #6366f1;
        }
        
        .transactions-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8pt;
        }
        
        .transactions-table .date-group-header {
            background-color: #f9fafb;
            font-weight: bold;
            border-bottom: 1px solid #d1d5db;
        }
        
        .amount-positive {
            color: #10b981;
            font-weight: bold;
        }
        
        .amount-negative {
            color: #ef4444;
        }
        
        .text-right {
            text-align: right;
        }
        
        .balance-summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 5px;
        }
        
        .balance-row {
            display: table;
            width: 100%;
            margin: 5px 0;
        }
        
        .balance-label {
            display: table-cell;
            width: 70%;
            font-weight: bold;
        }
        
        .balance-amount {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: bold;
        }
        
        .balance-final {
            font-size: 12pt;
            padding-top: 10px;
            border-top: 2px solid #86efac;
            margin-top: 10px;
        }
        
        .statistics {
            margin-top: 30px;
            padding: 15px;
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 5px;
        }
        
        .stat-grid {
            display: table;
            width: 100%;
        }
        
        .stat-item {
            display: table-cell;
            width: 33%;
            padding: 5px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-data {
            text-align: center;
            color: #9ca3af;
            padding: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td width="50%">
                    <strong>{{ $company['name'] }}</strong><br>
                    Kontoauszug
                </td>
                <td width="50%" style="text-align: right;">
                    {{ $statement_number }}<br>
                    {{ $statement_date->format('d.m.Y') }}
                </td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <table width="100%">
            <tr>
                <td width="33%">{{ $company['name'] }}</td>
                <td width="33%" style="text-align: center;">Seite <span class="page-number"></span></td>
                <td width="33%" style="text-align: right;">Vertraulich</td>
            </tr>
        </table>
    </div>
    
    <div class="content">
        <div class="statement-header">
            <h1 class="statement-title">Monatsabrechnung</h1>
            <div class="statement-period">
                Abrechnungszeitraum: {{ $statement_period }}
            </div>
        </div>
        
        <div class="customer-info">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <strong>Kunde:</strong><br>
                        {{ $customer['name'] }}<br>
                        @if($customer['address'])
                            {{ $customer['address'] }}<br>
                        @endif
                        @if($customer['city'])
                            {{ $customer['postal_code'] }} {{ $customer['city'] }}
                        @endif
                    </td>
                    <td width="50%" style="text-align: right;">
                        <strong>Kundennummer:</strong> {{ $customer['customer_id'] }}<br>
                        <strong>Abrechnungsmonat:</strong> {{ \Carbon\Carbon::parse($statement_period)->locale('de')->isoFormat('MMMM YYYY') }}<br>
                        <strong>Erstellt am:</strong> {{ $statement_date->format('d.m.Y H:i') }}
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="summary-box">
            <h3 style="margin-top: 0; color: #1f2937;">Zusammenfassung</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value" style="color: #6b7280;">{{ number_format($opening_balance / 100, 2, ',', '.') }} €</div>
                    <div class="summary-label">Anfangssaldo</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value amount-positive">+{{ number_format($total_topups / 100, 2, ',', '.') }} €</div>
                    <div class="summary-label">Aufladungen</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value amount-negative">-{{ number_format($total_usage / 100, 2, ',', '.') }} €</div>
                    <div class="summary-label">Verbrauch</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" style="color: #1f2937;">{{ number_format($closing_balance / 100, 2, ',', '.') }} €</div>
                    <div class="summary-label">Endsaldo</div>
                </div>
            </div>
        </div>
        
        <h3>Transaktionsübersicht</h3>
        
        @if($transactions->count() > 0)
        <table class="transactions-table">
            <thead>
                <tr>
                    <th width="15%">Datum</th>
                    <th width="10%">Zeit</th>
                    <th width="15%">Typ</th>
                    <th width="35%">Beschreibung</th>
                    <th width="12%" class="text-right">Betrag</th>
                    <th width="13%" class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @php $currentDate = null; @endphp
                @foreach($transactions as $transaction)
                    @php
                        $transactionDate = $transaction->created_at->format('Y-m-d');
                        $showDateHeader = $currentDate !== $transactionDate;
                        $currentDate = $transactionDate;
                    @endphp
                    
                    @if($showDateHeader && isset($daily_groups[$transactionDate]))
                    <tr class="date-group-header">
                        <td colspan="6">{{ \Carbon\Carbon::parse($transactionDate)->locale('de')->isoFormat('dddd, D. MMMM YYYY') }}</td>
                    </tr>
                    @endif
                    
                    <tr>
                        <td>{{ $transaction->created_at->format('d.m.Y') }}</td>
                        <td>{{ $transaction->created_at->format('H:i') }}</td>
                        <td>
                            @switch($transaction->type)
                                @case('topup')
                                    <span style="color: #10b981;">Aufladung</span>
                                    @break
                                @case('usage')
                                    <span style="color: #6b7280;">Verbrauch</span>
                                    @break
                                @case('refund')
                                    <span style="color: #3b82f6;">Rückerstattung</span>
                                    @break
                                @case('adjustment')
                                    <span style="color: #f59e0b;">Anpassung</span>
                                    @break
                                @default
                                    {{ ucfirst($transaction->type) }}
                            @endswitch
                        </td>
                        <td>{{ $transaction->description }}</td>
                        <td class="text-right {{ $transaction->amount_cents > 0 ? 'amount-positive' : 'amount-negative' }}">
                            {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2, ',', '.') }} €
                        </td>
                        <td class="text-right">
                            {{ number_format($transaction->balance_after_cents / 100, 2, ',', '.') }} €
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">
            Keine Transaktionen in diesem Zeitraum
        </div>
        @endif
        
        <div class="balance-summary">
            <h3 style="margin-top: 0; color: #065f46;">Saldenverlauf</h3>
            <div class="balance-row">
                <div class="balance-label">Anfangssaldo {{ \Carbon\Carbon::parse($statement_period)->startOfMonth()->format('d.m.Y') }}:</div>
                <div class="balance-amount">{{ number_format($opening_balance / 100, 2, ',', '.') }} €</div>
            </div>
            <div class="balance-row">
                <div class="balance-label">Summe Aufladungen:</div>
                <div class="balance-amount amount-positive">+{{ number_format($total_topups / 100, 2, ',', '.') }} €</div>
            </div>
            <div class="balance-row">
                <div class="balance-label">Summe Verbrauch:</div>
                <div class="balance-amount amount-negative">-{{ number_format($total_usage / 100, 2, ',', '.') }} €</div>
            </div>
            @if($total_refunds > 0)
            <div class="balance-row">
                <div class="balance-label">Summe Rückerstattungen:</div>
                <div class="balance-amount amount-positive">+{{ number_format($total_refunds / 100, 2, ',', '.') }} €</div>
            </div>
            @endif
            <div class="balance-row balance-final">
                <div class="balance-label">Endsaldo {{ \Carbon\Carbon::parse($statement_period)->endOfMonth()->format('d.m.Y') }}:</div>
                <div class="balance-amount" style="font-size: 14pt; color: #065f46;">
                    {{ number_format($closing_balance / 100, 2, ',', '.') }} €
                </div>
            </div>
        </div>
        
        @if(isset($summary))
        <div class="statistics">
            <h3 style="margin-top: 0; color: #92400e;">Nutzungsstatistik</h3>
            <div class="stat-grid">
                <div class="stat-item">
                    <strong>Anzahl Anrufe:</strong><br>
                    {{ $summary['call_count'] }}
                </div>
                <div class="stat-item">
                    <strong>Gesamtminuten:</strong><br>
                    {{ $summary['total_minutes'] }} Min.
                </div>
                <div class="stat-item">
                    <strong>Ø Kosten pro Anruf:</strong><br>
                    {{ number_format($summary['average_cost'], 2, ',', '.') }} €
                </div>
            </div>
        </div>
        @endif
        
        <div style="margin-top: 40px; padding: 10px; background-color: #f9fafb; border-left: 3px solid #6366f1;">
            <p style="margin: 0; font-size: 8pt; color: #4b5563;">
                <strong>Hinweis:</strong> Dieser Kontoauszug dient nur zu Informationszwecken. 
                Für steuerliche Zwecke verwenden Sie bitte die separaten Rechnungen für Ihre Aufladungen.
                Bei Fragen wenden Sie sich bitte an unseren Support unter {{ $company['email'] }}.
            </p>
        </div>
    </div>
</body>
</html>