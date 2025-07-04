# Billing Rate Fix Summary - 2025-07-04

## Problem
Die Kostenberechnung hat den falschen Firmen-Tarif verwendet (0,10€ statt 0,42€ pro Minute).

## Ursache
- Der Code versuchte auf `$company->call_rate` zuzugreifen, was nicht existiert
- Die tatsächliche Rate ist in der `billing_rates` Tabelle gespeichert
- Zugriff erfolgt über die Relationship `$company->billingRate`

## Lösung

### 1. Model-Import hinzugefügt
```php
use App\Models\BillingRate;
```

### 2. Eager Loading der billingRate Relationship
```php
->with(['customer', 'appointment', 'branch', 'company.billingRate', 'mlPrediction'])
```

### 3. Korrekte Berechnung implementiert
```php
// Get company billing rate through relationship
$billingRate = $record->company?->billingRate;
$companyRate = $billingRate ? $billingRate->rate_per_minute : BillingRate::getDefaultRate(); // EUR per minute (default: 0.42)

// Calculate revenue based on actual billing increment (usually per second)
if ($billingRate) {
    // Use the billing rate's calculation method which handles billing increments
    $revenue = $billingRate->calculateCharge($record->duration_sec);
} else {
    // Fallback: simple calculation (seconds to minutes * rate)
    $revenue = ($record->duration_sec / 60) * $companyRate;
}
```

### 4. Tooltip erweitert
- Zeigt jetzt Sekunden und Minuten
- Zeigt Abrechnungstakt (z.B. "Sekundengenau")
- Korrekte Rate von 0,42€/Min

## Verifizierung für Call 258
- **Dauer**: 112 Sekunden (1.87 Minuten)
- **Rate**: 0,42€/Min (sekundengenau)
- **Umsatz**: 0,78€
- **Kosten**: 0,14€ (Retell)
- **Gewinn**: 0,64€
- **Marge**: 82.1%

## Technische Details
- Standard-Rate: 0,42€/Min (definiert in `BillingRate::getDefaultRate()`)
- Abrechnungstakt: Sekundengenau (billing_increment = 1)
- Berechnung: `(Sekunden / 60) × Rate pro Minute`

## Cache geleert
```bash
php artisan optimize:clear
```