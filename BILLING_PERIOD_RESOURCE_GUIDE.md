# BillingPeriod Filament Resource Guide

Dieses Dokument beschreibt die Admin-UI für die Verwaltung von Abrechnungszeiträumen im Filament Admin Panel.

## Übersicht

Das BillingPeriod Resource bietet eine umfassende Verwaltungsoberfläche für:
- Übersicht aller Abrechnungszeiträume
- Detailansicht mit Usage-Metriken
- Manuelle Verarbeitung und Rechnungserstellung
- Profitabilitätsanalyse
- Call-Historie pro Periode

## Features

### 1. Listenansicht

**Kompakte Darstellung** mit wichtigsten Informationen:
- Company & Period
- Status Badge (Pending → Active → Processed → Invoiced → Closed)
- Usage Summary (Used/Included mit Overage-Warnung)
- Total Cost
- Revenue & Margin
- Invoice Status

**Filter-Optionen**:
- By Company
- By Status
- Current Period
- Has Overage
- Not Invoiced
- Date Range

**Bulk Actions**:
- Process Multiple Periods
- Delete (nur für Admins)

### 2. Formular (Create/Edit)

**Tab-basierte Organisation**:

#### General Information Tab
- Company & Branch Selection
- Period Dates (Auto-fills end date)
- Status Management
- Subscription Link

#### Usage & Costs Tab
- **Usage Metrics**:
  - Included/Used/Overage Minutes
  - Automatische Overage-Berechnung
  - Visuelle Warnungen bei Überschreitung
- **Pricing**:
  - Base Fee & Price per Minute
  - Automatische Cost-Berechnung
- **Profitability**:
  - Revenue Input
  - Margin-Berechnung (€ und %)
  - Farbcodierung nach Profitabilität

#### Invoice Information Tab
- Invoice Status Toggle
- Invoice Timestamps
- Stripe Invoice ID
- Proration Settings

### 3. Detailansicht (View)

**Infolist mit drei Tabs**:

#### Overview Tab
- Period Information
- Usage Summary mit Visualisierung
- Financial Summary
- Usage Percentage mit Farbcodierung

#### Profitability Tab
- Revenue & Margin Analysis
- Detaillierte Margin-Berechnung
- Profitabilitäts-Indikatoren

#### Invoice Details Tab
- Invoice Status
- Links zu generierten Rechnungen
- Stripe Integration Details

### 4. Relation Manager: Calls

**Call-Liste innerhalb der Periode**:
- Alle Anrufe im Zeitraum
- Call Details (ID, Customer, Duration)
- Booking Status
- Geschätzte Kosten pro Call
- Audio-Player für Recordings
- Filter nach Status/Booking/Duration

### 5. Actions

**Process Period**:
- Verfügbar wenn: Status = 'active' && end_date < now()
- Berechnet finale Usage
- Setzt Status auf 'processed'

**Create Invoice**:
- Verfügbar wenn: Status = 'processed' && !is_invoiced
- Erstellt Rechnung via BillingPeriodService
- Verlinkt zu generierter Rechnung

### 6. Dashboard Widget

**BillingPeriodSummaryWidget** zeigt:
- Current Period mit verbleibenden Tagen
- Anzahl Pending Invoices
- Monthly Revenue mit Chart
- Average Margin mit Health-Indikator

## Technische Details

### Resource-Konfiguration
```php
protected static ?string $navigationGroup = 'Billing';
protected static ?int $navigationSort = 2;
protected static ?string $navigationIcon = 'heroicon-o-calendar';
```

### Automatische Berechnungen

**Overage Calculation**:
```php
$overage = max(0, $used - $included);
$overage_cost = $overage * $price_per_minute;
$total_cost = $base_fee + $overage_cost;
```

**Margin Calculation**:
```php
$margin = $revenue - $cost;
$margin_percentage = ($margin / $revenue) * 100;
```

### Navigation Badge
Zeigt Anzahl aktiver Perioden:
```php
BillingPeriod::where('status', 'active')->count()
```

### Permissions

- **View**: Alle User mit `view_billing_periods`
- **Create**: Nur Super Admins (automatische Erstellung bevorzugt)
- **Edit**: Admins
- **Delete**: Nur Super Admins
- **Process/Invoice**: Admins

## Workflows

### 1. Automatischer Workflow
```
Monatsbeginn → CreateBillingPeriods Job → Status: pending
↓
Erster Call → Status: active
↓
Monatsende → ProcessBillingPeriods Job → Status: processed
↓
Invoice Creation → Status: invoiced
```

### 2. Manueller Workflow
```
Admin öffnet Period → Click "Process" → Berechnung
↓
Click "Create Invoice" → Rechnung erstellt
↓
Automatisch: Status → invoiced
```

### 3. Fehlerbehandlung
- **Doppelte Perioden**: Verhindert durch unique constraint
- **Fehlende Pricing**: Fallback auf Defaults
- **Überlappende Perioden**: Validierung im Form

## Best Practices

### 1. Monatliche Routine
- Prüfe "Not Invoiced" Filter am Monatsanfang
- Verarbeite alle fertigen Perioden
- Erstelle Rechnungen in Batches

### 2. Overage Management
- Nutze "Has Overage" Filter für Übersicht
- Informiere Kunden bei hohen Overage-Werten
- Überprüfe Pricing bei häufigen Overages

### 3. Profitabilitäts-Monitoring
- Behalte Average Margin im Auge
- Identifiziere unprofitable Accounts
- Justiere Pricing basierend auf Usage

### 4. Datenqualität
- Revenue-Daten zeitnah eingeben
- Subscription-Verknüpfungen pflegen
- Regelmäßige Status-Updates

## Troubleshooting

### Problem: Period wird nicht automatisch erstellt

1. Prüfe Scheduler:
```bash
php artisan schedule:list | grep billing
```

2. Manuell ausführen:
```bash
php artisan billing:create-periods
```

### Problem: Usage wird nicht berechnet

1. Prüfe Call-Daten:
```sql
SELECT COUNT(*), SUM(duration_seconds/60) 
FROM calls 
WHERE company_id = ? 
AND start_time BETWEEN ? AND ?;
```

2. Manuell berechnen:
```php
$service->calculatePeriodUsage($period);
```

### Problem: Invoice Creation schlägt fehl

1. Prüfe Stripe Integration
2. Validiere Company-Daten (Billing Email, etc.)
3. Check Error Logs

## Erweiterungsmöglichkeiten

1. **Automatische Alerts**
   - Bei Overage-Schwellenwerten
   - Vor Period-Ende
   - Bei ungewöhnlicher Usage

2. **Erweiterte Reports**
   - Usage-Trends
   - Overage-Analyse
   - Profitabilitäts-Reports

3. **Batch-Operationen**
   - Multi-Company Invoice Creation
   - Bulk Status Updates
   - Mass Email Notifications

4. **Integration**
   - Direkte Stripe-Sync
   - Accounting Software Export
   - Customer Portal Access