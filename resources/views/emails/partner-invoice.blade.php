<x-mail::message>
# Rechnung {{ $invoice->invoice_number }}

Sehr geehrte Damen und Herren,

anbei erhalten Sie Ihre Rechnung für den Abrechnungszeitraum **{{ $billingPeriod }}**.

## Rechnungsdetails

| Beschreibung | Wert |
|:-------------|-----:|
| Rechnungsnummer | {{ $invoice->invoice_number }} |
| Abrechnungszeitraum | {{ $billingPeriod }} |
| Positionen | {{ $itemCount }} |
| **Gesamtbetrag** | **{{ $formattedTotal }}** |
@if($dueDate)
| Fällig am | {{ $dueDate }} |
@endif

<x-mail::button :url="$paymentUrl" color="success">
Rechnung ansehen und bezahlen
</x-mail::button>

Über den obigen Link können Sie Ihre Rechnung einsehen und direkt bezahlen.

@if($invoice->notes)
## Anmerkungen
{{ $invoice->notes }}
@endif

Bei Fragen zu dieser Rechnung wenden Sie sich bitte an unser Billing-Team.

Mit freundlichen Grüßen,<br>
Ihr {{ config('app.name') }} Team

---
<small>
Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese Nachricht.
</small>
</x-mail::message>
