<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rechnung {{ $invoice_number }}</title>
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
            border-bottom: 2px solid #3b82f6;
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
        
        .logo {
            width: 150px;
            height: auto;
        }
        
        .invoice-header {
            margin-bottom: 30px;
        }
        
        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #3b82f6;
            margin: 0;
        }
        
        .invoice-meta {
            margin-top: 10px;
            font-size: 9pt;
        }
        
        .addresses {
            margin-bottom: 40px;
        }
        
        .address-block {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }
        
        .address-block.sender {
            font-size: 8pt;
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            padding-bottom: 2px;
        }
        
        .address-block h3 {
            font-size: 10pt;
            margin: 0 0 10px 0;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #3b82f6;
        }
        
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .totals {
            margin-top: 30px;
            margin-left: 50%;
            width: 50%;
        }
        
        .totals table td {
            padding: 5px 10px;
        }
        
        .totals .total-row {
            font-weight: bold;
            font-size: 12pt;
            border-top: 2px solid #3b82f6;
            border-bottom: 3px double #3b82f6;
        }
        
        .payment-info {
            margin-top: 40px;
            padding: 15px;
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
        }
        
        .payment-info h3 {
            margin-top: 0;
            color: #3b82f6;
        }
        
        .bank-details {
            margin-top: 20px;
        }
        
        .bank-details table {
            width: auto;
        }
        
        .bank-details td:first-child {
            font-weight: bold;
            padding-right: 20px;
        }
        
        .legal-text {
            margin-top: 30px;
            font-size: 8pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
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
                <td width="33%" style="text-align: center;">Seite <span class="page-number"></span></td>
                <td width="33%" style="text-align: right;">{{ $invoice_date->format('d.m.Y') }}</td>
            </tr>
        </table>
    </div>
    
    <div class="content">
        <div class="addresses">
            <div class="address-block">
                <div class="sender">
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
                        <td style="text-align: left; padding-right: 20px;">Rechnungsnummer:</td>
                        <td style="text-align: right; font-weight: bold;">{{ $invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Rechnungsdatum:</td>
                        <td style="text-align: right;">{{ $invoice_date->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Leistungszeitraum:</td>
                        <td style="text-align: right;">{{ $invoice_date->format('d.m.Y') }}</td>
                    </tr>
                    @if($customer['tax_id'])
                    <tr>
                        <td style="text-align: left; padding-right: 20px;">Ihre USt-IdNr.:</td>
                        <td style="text-align: right;">{{ $customer['tax_id'] }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <div class="invoice-header">
            <h1 class="invoice-title">Rechnung</h1>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th width="10%">Pos.</th>
                    <th width="40%">Beschreibung</th>
                    <th width="10%" class="text-right">Menge</th>
                    <th width="15%" class="text-right">Einzelpreis</th>
                    <th width="10%" class="text-right">MwSt.</th>
                    <th width="15%" class="text-right">Gesamt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2, ',', '.') }} €</td>
                    <td class="text-right">{{ $item['tax_rate'] }}%</td>
                    <td class="text-right">{{ number_format($item['total'], 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td>Zwischensumme:</td>
                    <td class="text-right">{{ number_format($subtotal, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td>MwSt. (19%):</td>
                    <td class="text-right">{{ number_format($tax_amount, 2, ',', '.') }} €</td>
                </tr>
                <tr class="total-row">
                    <td>Gesamtbetrag:</td>
                    <td class="text-right">{{ number_format($total, 2, ',', '.') }} €</td>
                </tr>
            </table>
        </div>
        
        <div class="payment-info">
            <h3>Zahlungsinformationen</h3>
            <p>
                <strong>Status:</strong> {{ $payment_status }}<br>
                <strong>Zahlungsart:</strong> {{ $payment_method }}<br>
                @if($stripe_reference)
                <strong>Referenz:</strong> {{ $stripe_reference }}
                @endif
            </p>
            
            @if($payment_status === 'Bezahlt')
            <p style="color: #10b981; font-weight: bold;">
                ✓ Diese Rechnung wurde bereits bezahlt. Vielen Dank!
            </p>
            @else
            <p>
                Bitte überweisen Sie den Betrag innerhalb von 14 Tagen auf folgendes Konto:
            </p>
            
            <div class="bank-details">
                <table>
                    <tr>
                        <td>Bank:</td>
                        <td>{{ $company['bank'] }}</td>
                    </tr>
                    <tr>
                        <td>IBAN:</td>
                        <td>{{ $company['iban'] }}</td>
                    </tr>
                    <tr>
                        <td>BIC:</td>
                        <td>{{ $company['bic'] }}</td>
                    </tr>
                    <tr>
                        <td>Verwendungszweck:</td>
                        <td>{{ $invoice_number }}</td>
                    </tr>
                </table>
            </div>
            @endif
        </div>
        
        <div class="legal-text">
            <p>
                {{ $company['name'] }} • Geschäftsführer: Max Mustermann<br>
                Amtsgericht Berlin • HRB 123456 • USt-IdNr.: {{ $company['tax_id'] }}<br>
                Finanzamt Berlin-Mitte • Steuernummer: 27/123/45678
            </p>
        </div>
    </div>
</body>
</html>