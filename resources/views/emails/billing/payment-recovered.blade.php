@component('mail::message')
# Zahlung erfolgreich - Service wieder aktiv! ✅

Sehr geehrte/r {{ $company_name }},

wir freuen uns, Ihnen mitteilen zu können, dass Ihre Zahlung erfolgreich verarbeitet wurde.

**Details:**
- Rechnungsnummer: {{ $invoice_number }}
- Betrag: {{ $currency }} {{ number_format($amount, 2, ',', '.') }}
- Bezahlt am: {{ $recovered_date }}

**Ihr Service ist wieder vollständig aktiv:**
- ✅ Alle Anrufe werden wieder automatisch beantwortet
- ✅ Terminbuchungen sind wieder möglich
- ✅ Alle Funktionen stehen Ihnen uneingeschränkt zur Verfügung

Vielen Dank für Ihre Zahlung und Ihr Vertrauen in unseren Service.

@component('mail::button', ['url' => config('app.url') . '/admin'])
Zum Dashboard
@endcomponent

Falls Sie Fragen haben, stehen wir Ihnen gerne zur Verfügung unter {{ config('mail.support_email', 'support@askproai.de') }}.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.
@endcomponent
@endcomponent