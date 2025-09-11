<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Transaktionsdetails #{{ $transaction->id }}</title>
    <style>
        @page {
            margin: 2cm;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
        }
        
        .header {
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-logo {
            font-size: 24pt;
            font-weight: bold;
            color: #4F46E5;
        }
        
        .company-info {
            text-align: right;
            color: #666;
            font-size: 9pt;
        }
        
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #1F2937;
            margin: 20px 0;
        }
        
        .section {
            margin-bottom: 30px;
            background-color: #F9FAFB;
            padding: 15px;
            border-radius: 5px;
        }
        
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 5px 0;
            font-weight: bold;
            color: #6B7280;
        }
        
        .info-value {
            display: table-cell;
            width: 60%;
            padding: 5px 0;
            color: #1F2937;
        }
        
        .amount-positive {
            color: #10B981;
            font-weight: bold;
            font-size: 14pt;
        }
        
        .amount-negative {
            color: #EF4444;
            font-weight: bold;
            font-size: 14pt;
        }
        
        .balance-info {
            background-color: #FFF;
            border: 1px solid #E5E7EB;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
        }
        
        .balance-flow {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
        }
        
        .balance-item {
            text-align: center;
        }
        
        .arrow {
            color: #9CA3AF;
            font-size: 16pt;
        }
        
        .related-section {
            margin-top: 20px;
        }
        
        .related-item {
            background-color: #FFF;
            border: 1px solid #E5E7EB;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .badge-topup { background-color: #D1FAE5; color: #065F46; }
        .badge-usage { background-color: #FEE2E2; color: #991B1B; }
        .badge-refund { background-color: #FEF3C7; color: #92400E; }
        .badge-adjustment { background-color: #DBEAFE; color: #1E40AF; }
        .badge-bonus { background-color: #EDE9FE; color: #5B21B6; }
        .badge-fee { background-color: #F3F4F6; color: #374151; }
        
        .qr-code {
            width: 100px;
            height: 100px;
            float: right;
            margin-top: -50px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background-color: #F3F4F6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #E5E7EB;
        }
        
        td {
            padding: 8px;
            border-bottom: 1px solid #E5E7EB;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table width="100%">
            <tr>
                <td width="50%">
                    <div class="company-logo">{{ $companyInfo['name'] }}</div>
                </td>
                <td width="50%" class="company-info">
                    {{ $companyInfo['address'] }}<br>
                    {{ $companyInfo['city'] }}<br>
                    {{ $companyInfo['email'] }}<br>
                    {{ $companyInfo['phone'] }}
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Title -->
    <div class="title">Transaktionsdetails</div>
    
    <!-- Transaction Basic Info -->
    <div class="section">
        <div class="section-title">Transaktionsinformationen</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Transaktions-ID:</div>
                <div class="info-value">#{{ $transaction->id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Typ:</div>
                <div class="info-value">
                    @php
                        $typeClass = 'badge-' . $transaction->type;
                        $typeLabel = match($transaction->type) {
                            'topup' => 'Aufladung',
                            'usage' => 'Verbrauch',
                            'refund' => 'Erstattung',
                            'adjustment' => 'Anpassung',
                            'bonus' => 'Bonus',
                            'fee' => 'Gebühr',
                            default => ucfirst($transaction->type)
                        };
                    @endphp
                    <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Datum & Zeit:</div>
                <div class="info-value">{{ $transaction->created_at->format('d.m.Y H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Beschreibung:</div>
                <div class="info-value">{{ $transaction->description }}</div>
            </div>
            @if($transaction->tenant)
            <div class="info-row">
                <div class="info-label">Tenant:</div>
                <div class="info-value">{{ $transaction->tenant->name }}</div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Amount and Balance -->
    <div class="section">
        <div class="section-title">Betrag & Saldo</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Transaktionsbetrag:</div>
                <div class="info-value">
                    <span class="{{ $transaction->amount_cents > 0 ? 'amount-positive' : 'amount-negative' }}">
                        {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2, ',', '.') }} €
                    </span>
                </div>
            </div>
        </div>
        
        <div class="balance-info">
            <table width="100%">
                <tr>
                    <td width="35%" style="text-align: center;">
                        <div style="color: #6B7280; font-size: 9pt;">Saldo vorher</div>
                        <div style="font-size: 12pt; font-weight: bold;">
                            {{ number_format($transaction->balance_before_cents / 100, 2, ',', '.') }} €
                        </div>
                    </td>
                    <td width="30%" style="text-align: center;">
                        <span style="font-size: 20pt; color: #9CA3AF;">→</span>
                    </td>
                    <td width="35%" style="text-align: center;">
                        <div style="color: #6B7280; font-size: 9pt;">Saldo nachher</div>
                        <div style="font-size: 12pt; font-weight: bold; color: {{ $transaction->balance_after_cents < 0 ? '#EF4444' : ($transaction->balance_after_cents < 1000 ? '#F59E0B' : '#10B981') }};">
                            {{ number_format($transaction->balance_after_cents / 100, 2, ',', '.') }} €
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Related Entities -->
    @if($transaction->call_id || $transaction->appointment_id || $transaction->topup_id)
    <div class="section">
        <div class="section-title">Verknüpfungen</div>
        
        @if($transaction->call_id)
        <div class="related-item">
            <strong>Anruf:</strong> ID #{{ $transaction->call_id }}
            @if($transaction->call)
                - Dauer: {{ gmdate('i:s', $transaction->call->duration_seconds ?? 0) }} Min
            @endif
        </div>
        @endif
        
        @if($transaction->appointment_id)
        <div class="related-item">
            <strong>Termin:</strong> ID #{{ $transaction->appointment_id }}
            @if($transaction->appointment && $transaction->appointment->starts_at)
                - Datum: {{ \Carbon\Carbon::parse($transaction->appointment->starts_at)->format('d.m.Y H:i') }}
            @endif
        </div>
        @endif
        
        @if($transaction->topup_id)
        <div class="related-item">
            <strong>Aufladung:</strong> ID #{{ $transaction->topup_id }}
        </div>
        @endif
    </div>
    @endif
    
    <!-- Related Transactions -->
    @if($relatedTransactions->count() > 0)
    <div class="section">
        <div class="section-title">Letzte Transaktionen</div>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Beschreibung</th>
                    <th style="text-align: right;">Betrag</th>
                    <th style="text-align: right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatedTransactions as $related)
                <tr>
                    <td>{{ $related->created_at->format('d.m.Y') }}</td>
                    <td>{{ Str::limit($related->description, 40) }}</td>
                    <td style="text-align: right; color: {{ $related->amount_cents > 0 ? '#10B981' : '#EF4444' }};">
                        {{ $related->amount_cents > 0 ? '+' : '' }}{{ number_format($related->amount_cents / 100, 2, ',', '.') }} €
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($related->balance_after_cents / 100, 2, ',', '.') }} €
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Metadata -->
    @if($transaction->metadata && count($transaction->metadata) > 0)
    <div class="section">
        <div class="section-title">Zusätzliche Daten</div>
        <pre style="background-color: #FFF; padding: 10px; border: 1px solid #E5E7EB; border-radius: 3px; font-size: 8pt;">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
    @endif
    
    <!-- Footer -->
    <div class="footer">
        <p>
            Generiert am {{ $generatedAt->format('d.m.Y H:i:s') }} | 
            {{ $companyInfo['name'] }} | 
            USt-IdNr.: {{ $companyInfo['vat_id'] }}
        </p>
        <p style="font-size: 7pt;">
            Dieses Dokument wurde automatisch erstellt und ist ohne Unterschrift gültig.
        </p>
    </div>
</body>
</html>