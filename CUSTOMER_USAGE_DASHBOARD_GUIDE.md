# Customer Usage Dashboard Guide

Dieses Dokument beschreibt das Customer Usage Dashboard für transparente Verbrauchsanzeige und Self-Service Optionen.

## Übersicht

Das Customer Usage Dashboard bietet Kunden eine transparente Einsicht in:
- Aktuelle Nutzungsdaten (Anrufe, Minuten, Termine)
- Kostenübersicht und Prognosen
- Rechnungshistorie
- Zahlungsmethoden-Verwaltung
- Usage-Trends und Analysen

## Komponenten

### 1. Filament Dashboard Page

**CustomerBillingDashboard** (`app/Filament/Admin/Pages/CustomerBillingDashboard.php`)
- Zentrale Dashboard-Seite im Admin-Panel
- Echtzeit-Nutzungsdaten mit 5-Minuten-Cache
- Interaktive Charts für Usage-Trends
- Download-Optionen für Reports

### 2. Usage Calculation Service

**UsageCalculationService** (`app/Services/Billing/UsageCalculationService.php`)
- Berechnung von Verbrauchsdaten
- Kostenkalkulationen basierend auf Preismodellen
- Projektionen für Monatsende
- Detaillierte Usage-Breakdowns

### 3. API Endpoints

**BillingUsageController** (`app/Http/Controllers/Api/BillingUsageController.php`)

Verfügbare Endpoints:
- `GET /api/billing/usage/current` - Aktuelle Periode
- `GET /api/billing/usage/projection` - Monatsend-Projektion
- `GET /api/billing/history` - Rechnungshistorie
- `GET /api/billing/period/{id}` - Periodendetails
- `GET /api/billing/period/{id}/download` - CSV-Export

## Dashboard Features

### Current Period Overview
- **Echtzeitdaten**: Anrufe, Minuten, Termine
- **Kostenübersicht**: Aktuelle Gebühren
- **Usage-Progress**: Visueller Fortschrittsbalken
- **Overage-Warnung**: Bei Überschreitung inkludierter Minuten

### Usage Trends Chart
- 6-Monats-Verlauf
- Dual-Axis Chart (Minuten & Anrufe)
- Interaktive Tooltips
- Responsive Design

### Upcoming Charges
- Projektion der Monatsendkosten
- Subscription-Erneuerung
- Overage-Gebühren

### Payment Methods
- Übersicht gespeicherter Zahlungsmethoden
- Direkter Link zum Stripe Customer Portal
- Default-Zahlungsmethode markiert

### Billing History
- Tabellarische Übersicht
- Status-Badges (Paid, Pending, Failed)
- PDF-Download Links
- Sortierung nach Datum

## Preismodelle

### Unterstützte Modelle

1. **Per-Minute Pricing**
   ```php
   'pricing_model' => 'per_minute',
   'base_fee' => 49.00,
   'included_minutes' => 500,
   'overage_per_minute' => 0.15,
   'per_appointment_rate' => 2.00
   ```

2. **Per-Appointment Pricing**
   ```php
   'pricing_model' => 'per_appointment',
   'base_fee' => 0,
   'per_appointment_rate' => 5.00
   // Alle Minuten inklusive
   ```

3. **Package Pricing**
   ```php
   'pricing_model' => 'package',
   'base_fee' => 99.00,
   'package_minutes' => 1000,
   'package_appointments' => 50,
   'overage_per_minute' => 0.12,
   'overage_per_appointment' => 3.00
   ```

4. **Combined Pricing**
   ```php
   'pricing_model' => 'combined',
   'per_minute_rate' => 0.10,
   'per_appointment_rate' => 2.00
   // Keine Basis oder inkludierte Leistungen
   ```

## API Usage

### Authentication
Alle API-Endpoints erfordern Sanctum-Authentication:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.askproai.de/api/billing/usage/current
```

### Current Usage Response
```json
{
  "success": true,
  "data": {
    "period": {
      "start": "2025-06-01",
      "end": "2025-06-30",
      "days_remaining": 15
    },
    "calls": {
      "total": 245,
      "completed": 230,
      "total_minutes": 687.5,
      "avg_duration": 2.8,
      "cost": 68.75
    },
    "appointments": {
      "total": 87,
      "cost": 174.00
    },
    "total_cost": 242.75,
    "included_minutes": 500,
    "overage_minutes": 187.5,
    "usage_percentage": 137.5
  }
}
```

### Projection Response
```json
{
  "success": true,
  "data": {
    "current": { /* current usage */ },
    "projected": {
      "minutes": 1375.0,
      "appointments": 174,
      "costs": {
        "base_fee": 49.00,
        "minutes_cost": 131.25,
        "appointments_cost": 348.00,
        "total_cost": 528.25
      },
      "days_remaining": 15,
      "avg_per_day": {
        "minutes": 45.83,
        "appointments": 5.8
      }
    }
  }
}
```

## Frontend Integration

### JavaScript Chart Integration
```javascript
// Chart.js Integration
const ctx = document.getElementById('usageTrendsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: usageTrends.map(t => t.month),
        datasets: [{
            label: 'Minutes',
            data: usageTrends.map(t => t.minutes),
            borderColor: 'rgb(59, 130, 246)',
            yAxisID: 'y',
        }, {
            label: 'Calls',
            data: usageTrends.map(t => t.calls),
            borderColor: 'rgb(16, 185, 129)',
            yAxisID: 'y1',
        }]
    }
});
```

### Livewire Actions
```php
// Refresh data
wire:click="refreshData"

// Open payment portal
wire:click="openPaymentMethodsModal"

// Export usage data
wire:click="exportUsageData"
```

## Performance Optimization

### Caching Strategy
- Current usage: 5-Minuten-Cache
- Historical data: 1-Stunden-Cache
- Cache-Key: `billing_usage_{company_id}_{period}`

### Query Optimization
- Eager Loading für Relationships
- Aggregierte Queries für Statistiken
- Indexed Columns: company_id, created_at, status

## Security Considerations

1. **Access Control**
   - Nur authentifizierte User
   - Company-Scope automatisch angewendet
   - Keine Cross-Company Datenlecks

2. **API Rate Limiting**
   - 60 Requests pro Minute
   - Throttling per User

3. **Data Privacy**
   - Keine sensiblen Zahlungsdaten im Dashboard
   - Stripe Portal für Zahlungsmethoden
   - Audit-Log für alle Zugriffe

## Deployment Checklist

1. **Migrationen ausführen**
   ```bash
   php artisan migrate --force
   ```

2. **Cache leeren**
   ```bash
   php artisan optimize:clear
   ```

3. **Assets kompilieren**
   ```bash
   npm run build
   ```

4. **Permissions prüfen**
   - User benötigt `view_billing` Permission
   - Company muss aktiv sein

## Troubleshooting

### Problem: Keine Daten im Dashboard

1. Prüfe ob BillingPeriod existiert:
   ```sql
   SELECT * FROM billing_periods 
   WHERE company_id = ? 
   AND start_date <= NOW() 
   AND end_date >= NOW();
   ```

2. Prüfe ob Calls vorhanden:
   ```sql
   SELECT COUNT(*) FROM calls 
   WHERE company_id = ? 
   AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
   ```

### Problem: Falsche Kostenberechnung

1. Prüfe aktive Preisgestaltung:
   ```php
   $pricing = CompanyPricing::getActiveForCompany($company);
   dd($pricing->toArray());
   ```

2. Manuell berechnen:
   ```php
   $service = app(UsageCalculationService::class);
   $usage = $service->getCurrentPeriodUsage($company);
   dd($usage);
   ```

### Problem: Chart wird nicht angezeigt

1. Prüfe Console auf JS-Fehler
2. Stelle sicher dass Chart.js geladen ist
3. Verifiziere Datenformat in Browser DevTools

## Future Enhancements

1. **Real-time Updates**
   - WebSocket Integration für Live-Updates
   - Push-Notifications bei Limits

2. **Advanced Analytics**
   - Conversion Rate Tracking
   - Peak Hour Analysis
   - Cost per Lead Calculations

3. **Mobile App**
   - Native iOS/Android Apps
   - Push Notifications
   - Offline Usage Tracking

4. **Budgeting Tools**
   - Budget Alerts
   - Spending Forecasts
   - Cost Optimization Suggestions