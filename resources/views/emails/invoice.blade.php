@component('mail::message')
# Ihre Rechnung von AskProAI

Sehr geehrte/r {{ $invoice->company->name }},

vielen Dank für Ihre Zahlung! Anbei finden Sie Ihre Rechnung für die Guthaben-Aufladung.

## Rechnungsdetails

@component('mail::table')
| | |
|:--- |:--- |
| **Rechnungsnummer:** | {{ $invoice->number }} |
| **Rechnungsdatum:** | {{ $invoice->invoice_date->format('d.m.Y') }} |
| **Betrag:** | {{ number_format($invoice->total, 2, ',', '.') }} € |
| **Status:** | Bezahlt |
@endcomponent

@component('mail::button', ['url' => route('business.billing.transaction.invoice', $invoice->id)])
Rechnung herunterladen
@endcomponent

## Ihr aktuelles Guthaben

Nach dieser Aufladung beträgt Ihr Guthaben:
**{{ number_format($newBalance, 2, ',', '.') }} €**

@if($bonusAmount > 0)
**Bonus erhalten:** {{ number_format($bonusAmount, 2, ',', '.') }} €
@endif

## Fragen?

Bei Fragen zu Ihrer Rechnung oder Ihrem Guthaben stehen wir Ihnen gerne zur Verfügung:
- Email: support@askproai.de
- Telefon: +49 30 123456789

Mit freundlichen Grüßen,<br>
Ihr AskProAI Team

@component('mail::subcopy')
Diese Email wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese Email.
Die Rechnung im Anhang ist für Ihre Unterlagen bestimmt.
@endcomponent
@endcomponent