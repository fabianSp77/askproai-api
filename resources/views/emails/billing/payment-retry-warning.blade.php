@component('mail::message')
# Erinnerung: Offene Rechnung {{ $invoice_number }}

Sehr geehrte/r {{ $company_name }},

trotz {{ $retry_count }} Abbuchungsversuche(n) konnte die offene Rechnung noch nicht beglichen werden.

**Rechnungsdetails:**
- Rechnungsnummer: {{ $invoice_number }}
- Offener Betrag: {{ $currency }} {{ number_format($amount, 2, ',', '.') }}
- Abbuchungsversuche: {{ $retry_count }} von {{ $max_retries }}

@if($next_retry_date)
**Nächster Abbuchungsversuch:** {{ \Carbon\Carbon::parse($next_retry_date)->format('d.m.Y') }}
@endif

@if($retry_count >= $max_retries - 1)
⚠️ **Wichtig:** Dies ist der letzte automatische Abbuchungsversuch. Bei erneutem Fehlschlag können Serviceeinschränkungen folgen.
@endif

Um Serviceunterbrechungen zu vermeiden, empfehlen wir Ihnen dringend:
- Überprüfung Ihrer Zahlungsmethode
- Sicherstellung ausreichender Deckung
- Kontaktaufnahme mit unserem Support bei Problemen

@component('mail::button', ['url' => config('app.url') . '/admin/billing'])
Jetzt Zahlung aktualisieren
@endcomponent

Bei Fragen stehen wir Ihnen gerne zur Verfügung unter {{ config('mail.support_email', 'support@askproai.de') }}.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.
@endcomponent
@endcomponent