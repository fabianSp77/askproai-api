# Phone Number Performance Metrics (October 2025)

## Highlights

- `PhoneNumber::withPerformanceMetrics()` aggregiert Anrufkennzahlen (gesamt, inbound, outbound, verpasste Anrufe, Ø- und Gesamtdauer, letzter Anruf) direkt aus der `calls`-Tabelle.
- Nummern werden beim Speichern automatisch nach E.164 normalisiert (`number_normalized`, `country_code`, `friendly_name`).
- Filament-Ressource nutzt die Live-Metriken in Listen-, Detail- und Statistiksektionen.
- Retell-Agenten können per Select angebunden werden; Status/Version werden auf der Detailseite angezeigt.

## Verwendung

```php
$numbers = PhoneNumber::withPerformanceMetrics()->get();

$number = PhoneNumber::withPerformanceMetrics(now()->subWeek(), now())->first();
echo $number->avg_call_duration_formatted; // z.B. 03:45
```

## Tests

- `tests/Unit/Models/PhoneNumberPerformanceScopeTest.php` prüft Aggregation und Normalisierung.

## ToDo / Nice-to-have

- Messaging (SMS/WhatsApp) Aggregation ergänzen, sobald Quelltabelle angebunden ist.
- Optionales Caching / Materialisierung für große Datenmengen.
- Dashboard-Widgets (Sparkline) für Zeitverläufe.
