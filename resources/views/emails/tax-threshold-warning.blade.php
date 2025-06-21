@component('mail::message')
# Warnung: Annäherung an Kleinunternehmergrenze

Sehr geehrte Damen und Herren,

Ihr Umsatz nähert sich der Kleinunternehmergrenze von **22.000 €** im laufenden Jahr.

## Aktueller Stand:
- **Bisheriger Umsatz**: {{ number_format($current_revenue, 2, ',', '.') }} €
- **Grenze**: 22.000 €
- **Erreicht**: {{ number_format($percentage, 1, ',', '.') }}%
- **Verbleibend**: {{ number_format($remaining, 2, ',', '.') }} €

@if($percentage >= 90)
@component('mail::panel')
**⚠️ ACHTUNG: Sie haben bereits über 90% der Grenze erreicht!**

Bei Überschreitung der 22.000 € Grenze entfällt die Kleinunternehmerregelung im **nächsten Jahr**.
@endcomponent
@else
## Was passiert bei Überschreitung?

Wenn Sie im laufenden Jahr mehr als 22.000 € Umsatz erzielen:
- Die Kleinunternehmerregelung gilt noch bis zum Jahresende
- Ab dem nächsten Jahr müssen Sie Umsatzsteuer ausweisen
- Sie werden umsatzsteuerpflichtig (sofern der Vorjahresumsatz nicht über 50.000 € lag)
@endif

## Empfohlene Maßnahmen:

1. **Überwachen Sie Ihre Umsätze genau**
2. **Planen Sie ggf. Rechnungsstellung ins nächste Jahr**
3. **Bereiten Sie sich auf mögliche Umstellung vor**
4. **Konsultieren Sie Ihren Steuerberater**

@component('mail::button', ['url' => config('app.url') . '/admin/tax-configuration'])
Umsatzübersicht anzeigen
@endcomponent

## Prognose Jahresende:
Basierend auf Ihrem bisherigen Umsatzverlauf prognostizieren wir einen Jahresumsatz von ca. **{{ number_format(($current_revenue / date('z')) * (date('L') ? 366 : 365), 2, ',', '.') }} €**.

Mit freundlichen Grüßen,  
Ihr {{ config('app.name') }} Team

@component('mail::subcopy')
Diese Benachrichtigung dient nur zur Information. Die tatsächliche steuerliche Behandlung sollte mit Ihrem Steuerberater besprochen werden.
@endcomponent
@endcomponent