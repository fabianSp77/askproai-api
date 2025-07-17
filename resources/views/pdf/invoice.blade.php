<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $invoice->number }}</title>
    <style>
        @page {
            margin: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 40px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #3B82F6;
            padding-bottom: 20px;
        }
        
        .logo-section {
            flex: 1;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #3B82F6;
            margin-bottom: 10px;
        }
        
        .company-info {
            font-size: 12px;
            color: #666;
        }
        
        .invoice-info {
            text-align: right;
            flex: 1;
        }
        
        .invoice-number {
            font-size: 24px;
            font-weight: bold;
            color: #3B82F6;
            margin-bottom: 5px;
        }
        
        .invoice-date {
            font-size: 14px;
            color: #666;
        }
        
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        
        .address-box {
            flex: 1;
        }
        
        .address-box h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 10px;
        }
        
        .address-box p {
            margin: 0;
            line-height: 1.4;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        
        .invoice-table th {
            background-color: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .invoice-table .text-right {
            text-align: right;
        }
        
        .invoice-table .description {
            font-size: 13px;
            color: #666;
        }
        
        .totals {
            margin-left: auto;
            width: 300px;
            margin-bottom: 40px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .totals-row.total {
            font-weight: bold;
            font-size: 18px;
            border-bottom: 2px solid #3B82F6;
            border-top: 2px solid #3B82F6;
            margin-top: 10px;
            padding: 12px 0;
        }
        
        .payment-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 40px;
        }
        
        .payment-info h3 {
            margin-top: 0;
            color: #3B82F6;
        }
        
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #666;
        }
        
        .footer-columns {
            display: flex;
            justify-content: space-between;
        }
        
        .footer-column {
            flex: 1;
        }
        
        .tax-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <div class="logo">AskProAI</div>
            <div class="company-info">
                Ihre KI-gestützte Telefonassistenz<br>
                Intelligente Anrufannahme & Terminbuchung
            </div>
        </div>
        <div class="invoice-info">
            <div class="invoice-number">{{ $invoice->number }}</div>
            <div class="invoice-date">
                Rechnungsdatum: {{ $invoice->invoice_date->format('d.m.Y') }}<br>
                Fällig am: {{ $invoice->due_date->format('d.m.Y') }}
            </div>
        </div>
    </div>
    
    <!-- Addresses -->
    <div class="addresses">
        <div class="address-box">
            <h3>Rechnungssteller</h3>
            <p>
                <strong>AskProAI GmbH</strong><br>
                Musterstraße 123<br>
                12345 Berlin<br>
                Deutschland<br>
                <br>
                USt-IdNr.: DE123456789<br>
                Steuernummer: 27/123/45678
            </p>
        </div>
        <div class="address-box">
            <h3>Rechnungsempfänger</h3>
            <p>
                <strong>{{ $invoice->company->name }}</strong><br>
                {{ $invoice->company->address }}<br>
                {{ $invoice->company->postal_code }} {{ $invoice->company->city }}<br>
                {{ $invoice->company->country ?? 'Deutschland' }}<br>
                @if($invoice->company->vat_id)
                <br>
                USt-IdNr.: {{ $invoice->company->vat_id }}
                @endif
                @if($invoice->company->tax_number)
                <br>
                Steuernummer: {{ $invoice->company->tax_number }}
                @endif
            </p>
        </div>
    </div>
    
    <!-- Tax Note for Small Business -->
    @if($invoice->company->is_small_business)
    <div class="tax-note">
        Hinweis: Als Kleinunternehmer im Sinne von § 19 Abs. 1 UStG wird keine Umsatzsteuer berechnet.
    </div>
    @endif
    
    <!-- Invoice Items -->
    <table class="invoice-table">
        <thead>
            <tr>
                <th style="width: 50%">Beschreibung</th>
                <th class="text-right" style="width: 15%">Menge</th>
                <th class="text-right" style="width: 15%">Einzelpreis</th>
                @if(!$invoice->company->is_small_business)
                <th class="text-right" style="width: 10%">USt.</th>
                @endif
                <th class="text-right" style="width: 10%">Betrag</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->description }}</strong>
                    @if($item->period_start && $item->period_end)
                    <div class="description">
                        Leistungszeitraum: {{ $item->period_start->format('d.m.Y') }} - {{ $item->period_end->format('d.m.Y') }}
                    </div>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->quantity, 2, ',', '.') }} {{ $item->unit }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                @if(!$invoice->company->is_small_business)
                <td class="text-right">{{ number_format($item->tax_rate, 0) }}%</td>
                @endif
                <td class="text-right">{{ number_format($item->amount, 2, ',', '.') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <!-- Totals -->
    <div class="totals">
        <div class="totals-row">
            <span>Zwischensumme</span>
            <span>{{ number_format($invoice->subtotal, 2, ',', '.') }} €</span>
        </div>
        @if(!$invoice->company->is_small_business && $invoice->tax_amount > 0)
        <div class="totals-row">
            <span>USt. ({{ number_format($invoice->items->first()->tax_rate ?? 19, 0) }}%)</span>
            <span>{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</span>
        </div>
        @endif
        <div class="totals-row total">
            <span>Gesamtbetrag</span>
            <span>{{ number_format($invoice->total, 2, ',', '.') }} €</span>
        </div>
    </div>
    
    <!-- Payment Information -->
    <div class="payment-info">
        <h3>Zahlungsinformationen</h3>
        <p>
            @if($invoice->status === 'paid')
            <strong>Diese Rechnung wurde bereits bezahlt.</strong><br>
            Bezahlt am: {{ $invoice->paid_at->format('d.m.Y') }}
            @else
            Bitte überweisen Sie den Gesamtbetrag bis zum {{ $invoice->due_date->format('d.m.Y') }} auf folgendes Konto:<br><br>
            <strong>Kontoinhaber:</strong> AskProAI GmbH<br>
            <strong>IBAN:</strong> DE89 3704 0044 0532 0130 00<br>
            <strong>BIC:</strong> COBADEFFXXX<br>
            <strong>Verwendungszweck:</strong> {{ $invoice->invoice_number }}
            @endif
        </p>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="footer-columns">
            <div class="footer-column">
                <strong>AskProAI GmbH</strong><br>
                Geschäftsführer: Max Mustermann<br>
                Registergericht: Amtsgericht Berlin<br>
                Registernummer: HRB 123456 B
            </div>
            <div class="footer-column">
                <strong>Kontakt</strong><br>
                Tel: +49 30 123456789<br>
                E-Mail: rechnung@askproai.de<br>
                Web: www.askproai.de
            </div>
            <div class="footer-column">
                <strong>Bankverbindung</strong><br>
                Commerzbank AG<br>
                IBAN: DE89 3704 0044 0532 0130 00<br>
                BIC: COBADEFFXXX
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #999;">
            Seite 1 von 1 | Erstellt am {{ now()->format('d.m.Y H:i') }} Uhr
        </div>
    </div>
</body>
</html>