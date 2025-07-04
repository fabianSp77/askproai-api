@component('mail::message')
# Zahlungsfehler - Rechnung {{ $invoice_number }}

Sehr geehrte/r {{ $company_name }},

leider konnten wir die fällige Zahlung für Ihre Rechnung nicht abbuchen.

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice_number }}
- Betrag: {{ $currency }} {{ number_format($amount, 2, ',', '.') }}
- Fehlgeschlagen am: {{ now()->format('d.m.Y') }}

**Fehlergrund:** {{ $failure_reason ?? 'Zahlung konnte nicht verarbeitet werden' }}

@if($next_retry_date)
**Nächster Abbuchungsversuch:** {{ \Carbon\Carbon::parse($next_retry_date)->format('d.m.Y') }}

Wir werden automatisch versuchen, den Betrag erneut abzubuchen. Bitte stellen Sie sicher, dass Ihre Zahlungsmethode gültig ist und ausreichend Deckung vorhanden ist.
@endif

@component('mail::button', ['url' => config('app.url') . '/admin/billing'])
Zahlungsmethode aktualisieren
@endcomponent

Falls Sie Fragen haben oder Unterstützung benötigen, kontaktieren Sie uns bitte unter {{ config('mail.support_email', 'support@askproai.de') }}.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.
@endcomponent
@endcomponent