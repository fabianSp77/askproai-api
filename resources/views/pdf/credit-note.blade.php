<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Gutschrift {{ $credit_note_number }}</title>
    <style>
        @page {
            margin: 100px 50px 80px 50px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 2px solid #ef4444;
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
        
        .watermark {
            position: fixed;
            top: 45%;
            left: 20%;
            opacity: 0.1;
            font-size: 72pt;
            color: #ef4444;
            transform: rotate(-45deg);
            font-weight: bold;
        }
        
        .logo {
            width: 150px;
            height: auto;
        }
        
        .credit-header {
            margin-bottom: 30px;
        }
        
        .credit-title {
            font-size: 24pt;
            font-weight: bold;
            color: #ef4444;
            margin: 0;
        }
        
        .addresses {
            margin-bottom: 40px;
        }
        
        .address-block {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }
        
        .info-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: #991b1b;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .details-table th {
            background-color: #fef2f2;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ef4444;
        }
        
        .details-table td {
            padding: 10px;
            border-bottom: 1px solid #fecaca;
        }
        
        .text-right {
            text-align: right;
        }
        
        .amount-box {
            margin-top: 30px;
            margin-left: 50%;
            width: 50%;
            padding: 15px;
            background-color: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 5px;
        }
        
        .amount-row {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        
        .amount-label {
            display: table-cell;
            width: 60%;
            font-weight: bold;
        }
        
        .amount-value {
            display: table-cell;
            width: 40%;
            text-align: right;
            font-weight: bold;
            font-size: 12pt;
        }
        
        .refund-reason {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9fafb;
            border-left: 4px solid #ef4444;
        }
        
        .refund-reason h3 {
            margin-top: 0;
            color: #ef4444;
        }
        
        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 5px;
        }
        
        .payment-info h3 {
            margin-top: 0;
            color: #166534;
        }
        
        .reference-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 3px;
        }
        
        .legal-text {
            margin-top: 40px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
        
        .stamp {
            position: absolute;
            right: 50px;
            bottom: 150px;
            width: 150px;
            text-align: center;
            border: 2px solid #ef4444;
            border-radius: 10px;
            padding: 10px;
            transform: rotate(-5deg);
        }
        
        .stamp-text {
            color: #ef4444;
            font-weight: bold;
            font-size: 11pt;
        }
        
        .stamp-date {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="watermark">GUTSCHRIFT</div>
    
    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
                </td>
                <td style="text-align: right;">
                    <strong>{{ $company['name'] }}</strong><br>
                    {{ $company['address'] }}<br>
                    {{ $company['city'] }}<br>
                    Tel: {{ $company['phone'] }}<br>
                    E-Mail: {{ $company['email'] }}
                </td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <table width="100%">
            <tr>
                <td width="33%">{{ $company['name'] }}</td>
                <td width="33%" style="text-align: center;">Gutschrift</td>
                <td width="33%" style="text-align: right;">{{ $credit_note_date->format('d.m.Y') }}</td>
            </tr>
        </table>
    </div>
    
    <div class="content">
        <div class="addresses">
            <div class="address-block">
                <div style="font-size: 8pt; border-bottom: 1px solid #333; margin-bottom: 5px; padding-bottom: 2px;">
                    {{ $company['name'] }} • {{ $company['address'] }} • {{ $company['city'] }}
                </div>
                <div>
                    <strong>{{ $customer['name'] }}</strong><br>
                    @if($customer['address'])
                        {{ $customer['address'] }}<br>
                    @endif
                    @if($customer['postal_code'] && $customer['city'])
                        {{ $customer['postal_code'] }} {{ $customer['city'] }}<br>
                    @endif
                    @if($customer['country'] && $customer['country'] !== 'Deutschland')
                        {{ $customer['country'] }}<br>
                    @endif
                </div>
            </div>
            
            <div class="address-block" style="float: right; text-align: right;">
                <table style="width: auto; margin-left: auto;">
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Gutschriftnummer:</td>
                        <td style="text-align: right; font-weight: bold; color: #ef4444;">{{ $credit_note_number }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Gutschriftdatum:</td>
                        <td style="text-align: right;">{{ $credit_note_date->format('d.m.Y') }}</td>
                    </tr>
                    @if($reference_invoice)
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Bezug Rechnung:</td>
                        <td style="text-align: right;">{{ $reference_invoice }}</td>
                    </tr>
                    @endif
                    @if($reference_transaction)
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Transaktions-ID:</td>
                        <td style="text-align: right; font-size: 8pt;">{{ $reference_transaction }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <div class="credit-header">
            <h1 class="credit-title">Gutschrift</h1>
        </div>
        
        <div class="info-box">
            <h3>Gutschriftinformation</h3>
            <p>
                Sehr geehrte Damen und Herren,<br><br>
                hiermit erstatten wir Ihnen den nachfolgend aufgeführten Betrag.
            </p>
        </div>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th width="60%">Beschreibung</th>
                    <th width="20%" class="text-right">Betrag (Netto)</th>
                    <th width="20%" class="text-right">MwSt. (19%)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $reason }}</strong><br>
                        @if($reference_invoice)
                        <span style="font-size: 9pt; color: #666;">Stornierung/Korrektur zu Rechnung {{ $reference_invoice }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format(($amount - $tax_amount), 2, ',', '.') }} €</td>
                    <td class="text-right">{{ number_format($tax_amount, 2, ',', '.') }} €</td>
                </tr>
            </tbody>
        </table>
        
        <div class="amount-box">
            <div class="amount-row">
                <div class="amount-label">Nettobetrag:</div>
                <div class="amount-value">{{ number_format(($amount - $tax_amount), 2, ',', '.') }} €</div>
            </div>
            <div class="amount-row">
                <div class="amount-label">MwSt. (19%):</div>
                <div class="amount-value">{{ number_format($tax_amount, 2, ',', '.') }} €</div>
            </div>
            <div class="amount-row" style="border-top: 2px solid #ef4444; padding-top: 10px; margin-top: 10px;">
                <div class="amount-label" style="font-size: 14pt;">Gutschriftbetrag:</div>
                <div class="amount-value" style="font-size: 14pt; color: #ef4444;">{{ number_format($total, 2, ',', '.') }} €</div>
            </div>
        </div>
        
        @if($reason)
        <div class="refund-reason">
            <h3>Grund der Gutschrift</h3>
            <p>{{ $reason }}</p>
            @if($reference_transaction)
            <p style="font-size: 9pt; color: #666;">
                Referenz-Transaktion: {{ $reference_transaction }}
            </p>
            @endif
        </div>
        @endif
        
        <div class="payment-info">
            <h3>Rückerstattung</h3>
            <p>
                Der Betrag von <strong>{{ number_format($total, 2, ',', '.') }} €</strong> wurde Ihrem Guthaben gutgeschrieben 
                und steht Ihnen ab sofort zur Verfügung.
            </p>
            <p style="color: #166534;">
                ✓ Die Gutschrift wurde erfolgreich verarbeitet.
            </p>
            @if($reference_transaction)
            <div class="reference-box">
                <strong>Zahlungsreferenz:</strong> {{ $reference_transaction }}<br>
                <strong>Verarbeitet am:</strong> {{ $credit_note_date->format('d.m.Y H:i:s') }}
            </div>
            @endif
        </div>
        
        <div style="margin-top: 40px; padding: 10px; background-color: #f9fafb; border-left: 3px solid #6b7280;">
            <p style="margin: 0; font-size: 8pt; color: #4b5563;">
                <strong>Hinweis:</strong> Diese Gutschrift mindert Ihre Umsatzsteuerschuld. 
                Bitte bewahren Sie dieses Dokument für Ihre Unterlagen auf.
                Bei Fragen wenden Sie sich bitte an unsere Buchhaltung unter {{ $company['email'] }}.
            </p>
        </div>
        
        <div class="stamp">
            <div class="stamp-text">ERSTATTET</div>
            <div class="stamp-date">{{ $credit_note_date->format('d.m.Y') }}</div>
        </div>
        
        <div class="legal-text">
            <p>
                {{ $company['name'] }} • Geschäftsführer: Max Mustermann<br>
                Amtsgericht Berlin • HRB 123456 • USt-IdNr.: {{ $company['tax_id'] }}<br>
                Diese Gutschrift wurde elektronisch erstellt und ist ohne Unterschrift gültig.
            </p>
        </div>
    </div>
</body>
</html>