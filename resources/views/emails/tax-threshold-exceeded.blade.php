@component('mail::message')
# Wichtig: Kleinunternehmerregelung entfällt

Sehr geehrte Damen und Herren,

wir möchten Sie darauf hinweisen, dass Ihr **Vorjahresumsatz** die Grenze von **50.000 €** überschritten hat.

## Ihre Umsätze:
- **Vorjahresumsatz**: {{ number_format($previous_revenue, 2, ',', '.') }} €
- **Aktueller Jahresumsatz**: {{ number_format($current_revenue, 2, ',', '.') }} €

## Was bedeutet das für Sie?

Ab sofort entfällt die Kleinunternehmerregelung nach § 19 UStG. Das bedeutet:

1. **Sie müssen ab sofort Umsatzsteuer ausweisen** (19% bzw. 7% ermäßigt)
2. **Ihre Rechnungen müssen angepasst werden**
3. **Sie sind zur Abgabe von Umsatzsteuer-Voranmeldungen verpflichtet**

## Was müssen Sie jetzt tun?

@component('mail::panel')
**Dringend erforderliche Schritte:**
- Kontaktieren Sie umgehend Ihren Steuerberater
- Passen Sie Ihre Preise an (ggf. Bruttopreise beibehalten)
- Melden Sie sich beim Finanzamt für die Umsatzsteuer-Voranmeldung an
- Aktualisieren Sie Ihre Rechnungsvorlagen
@endcomponent

Die Umstellung in AskProAI wurde bereits automatisch vorgenommen. Neue Rechnungen werden ab sofort mit Umsatzsteuer erstellt.

@component('mail::button', ['url' => config('app.url') . '/admin/tax-configuration'])
Steuereinstellungen prüfen
@endcomponent

Bei Fragen stehen wir Ihnen gerne zur Verfügung.

Mit freundlichen Grüßen,  
Ihr {{ config('app.name') }} Team

@component('mail::subcopy')
Diese E-Mail wurde automatisch generiert. Bitte wenden Sie sich bei Rückfragen an Ihren Steuerberater oder unser Support-Team.
@endcomponent
@endcomponent