@component('mail::message')
# Service vorübergehend pausiert

Sehr geehrte/r {{ $company_name }},

aufgrund der ausstehenden Zahlung mussten wir Ihren Service leider vorübergehend pausieren.

**Details:**
- Rechnungsnummer: {{ $invoice_number }}
- Offener Betrag: {{ $currency }} {{ number_format($amount, 2, ',', '.') }}
- Service pausiert seit: {{ $paused_date }}

**Was bedeutet das für Sie?**
- Eingehende Anrufe werden nicht mehr automatisch beantwortet
- Keine neuen Terminbuchungen möglich
- Bestehende Termine bleiben erhalten

**So aktivieren Sie Ihren Service wieder:**
Sobald die offene Rechnung beglichen ist, wird Ihr Service automatisch wieder aktiviert.

@component('mail::button', ['url' => config('app.url') . '/admin/billing'])
Rechnung jetzt begleichen
@endcomponent

Unser Support-Team steht Ihnen für Fragen oder alternative Zahlungsvereinbarungen zur Verfügung:
- E-Mail: {{ config('mail.support_email', 'support@askproai.de') }}
- Telefon: {{ config('app.support_phone', '+49 123 456789') }}

Wir möchten Ihnen gerne weiterhin unseren Service zur Verfügung stellen und unterstützen Sie bei der Lösung dieser Situation.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Diese E-Mail wurde automatisch generiert. Bei dringenden Anliegen kontaktieren Sie bitte direkt unseren Support.
@endcomponent
@endcomponent