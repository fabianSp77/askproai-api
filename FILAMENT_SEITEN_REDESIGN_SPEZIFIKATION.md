# 🎯 Filament Seiten Redesign - Detaillierte Spezifikation

## Executive Summary

Überarbeitung der drei Kern-Seiten **Termine**, **Anrufe** und **Kunden** auf das Wow-Niveau des neuen Haupt-Dashboards. Fokus auf Performance, Business Intelligence und erstklassige UX.

**Scope**: 3 Seiten, 18 Widgets, 15 KPIs, Mobile-First Design  
**Timeline**: 5 Phasen (Plan → Spec → PR-1 → PR-2 → PR-3)  
**Success Metrics**: Sub-second Load, Lighthouse >90, 90%+ Test Coverage

---

## 🔍 Analyse-Ergebnisse (Basis für Redesign)

### Performance-Blocker identifiziert:
- **CallResource**: 1477 Zeilen, 25+ Spalten, N+1 Query Problem
- **JSON-Queries**: Unindizierte Sentiment/Tag-Abfragen
- **Redundante Eager Loading**: 5-10 DB-Queries pro Table-Row

### UX-Gaps identifiziert:
- **Fehlende KPIs**: Keine Umsatz-/Conversion-/Trend-Metriken
- **Inkonsistente Navigation**: Unterschiedliche Patterns zwischen Seiten
- **Mobile Usability**: Keine responsive Optimierung

### Wiederverwendbare Patterns gefunden:
- **FilterableWidget**: Universelles Filter-System
- **DashboardMetricsService**: Zentrale KPI-Berechnungen
- **LiveBoard-Pattern**: Real-time Updates mit Auto-Refresh

---

## 🎨 1. TERMINE-SEITE (AppointmentResource)

### 1.1 Ziele & KPIs

**Business Objectives:**
- Umsatz-Transparenz: Wertschöpfung pro Termin/Mitarbeiter/Filiale
- Auslastungs-Optimierung: Freie Slots identifizieren
- Conversion-Tracking: Phone → Termin → Completed

**Key Performance Indicators:**
```
1. Umsatz heute/Woche/Monat (€ + Δ%)
2. Auslastung (gebuchte/verfügbare Slots %)
3. Conversion Rate (Anfragen → Termine %)
4. No-Show Rate (% + Trend)
5. Durchschnittliche Termindauer (min + Δ)
6. Revenue per Appointment (€ + Benchmark)
```

### 1.2 Layout-Design (ASCII-Skizze)

```
┌─────────────────────────────────────────────────────────────┐
│ 🗓️  TERMINE                                    [Filter ▼]   │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │ 1.240€  │ │   87%   │ │   73%   │ │   8%    │ │ 47 min  │ │
│ │ Umsatz  │ │Auslast. │ │Convert. │ │No-Show  │ │ Ø Dauer │ │
│ │ +12% ▲  │ │ +5% ▲   │ │ -3% ▼   │ │ +2% ▲   │ │ -3 min  │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 📈 UMSATZ-TREND (30 Tage)                              │ │
│ │ ┌─────────────────────────────────────────────────────┐ │ │
│ │ │     1.5k€  ┌─┐                                      │ │ │
│ │ │     1.0k€  │ │  ┌─┐     ┌─┐                        │ │ │
│ │ │     0.5k€  │ │  │ │  ┌─┐│ │                        │ │ │
│ │ │     0.0k€  └─┘  └─┘  └─┘└─┘                        │ │ │
│ │ │           Mo  Di  Mi  Do  Fr  Sa  So                │ │ │
│ │ └─────────────────────────────────────────────────────┘ │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 📋 TERMINE (Sticky Header, Top 20)                     │ │
│ │ ┌─────────┬─────────┬─────────┬─────────┬─────────────┐ │ │
│ │ │ Datum   │ Kunde   │ Service │ Wert    │ Aktionen    │ │ │
│ │ ├─────────┼─────────┼─────────┼─────────┼─────────────┤ │ │
│ │ │ 10:00 ▲ │ M.Müller│ Beratung│ 120€    │ [Tel][Mail] │ │ │
│ │ │ 10:30   │ A.Schmidt│ Analyse │ 200€    │ [Tel][Mail] │ │ │
│ │ └─────────┴─────────┴─────────┴─────────┴─────────────┘ │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 1.3 Datenquellen & Formeln

**KPI-Berechnungen:**
```php
// 1. Umsatz heute/Woche/Monat
$revenue = Appointment::whereHas('service')
    ->where('status', 'completed')
    ->whereBetween('starts_at', [$start, $end])
    ->join('services', 'appointments.service_id', '=', 'services.id')
    ->sum('services.price');

// 2. Auslastung (gebuchte/verfügbare Slots)
$totalSlots = WorkingHours::calculateAvailableSlots($dateRange);
$bookedSlots = Appointment::whereBetween('starts_at', [$start, $end])->count();
$occupancy = ($bookedSlots / $totalSlots) * 100;

// 3. Conversion Rate (Calls → Appointments)
$callsWithIntent = Call::whereJsonContains('analysis->intent', 'booking')
    ->whereBetween('created_at', [$start, $end])->count();
$appointmentsFromCalls = Appointment::whereNotNull('call_id')
    ->whereBetween('created_at', [$start, $end])->count();
$conversionRate = ($appointmentsFromCalls / $callsWithIntent) * 100;
```

**Caching-Strategie:**
```php
// KPIs: 60s TTL (Live-Charakter)
// Trends: 300s TTL (weniger volatil)
// Historical: 3600s TTL (stabil)
```

### 1.4 Performance-Optimierungen

**Query-Optimierungen:**
```sql
-- Neue Indexes für KPI-Queries
ALTER TABLE appointments ADD INDEX idx_appt_status_date (status, starts_at);
ALTER TABLE appointments ADD INDEX idx_appt_service_value (service_id, status, starts_at);
ALTER TABLE appointments ADD INDEX idx_appt_call_conversion (call_id, created_at);
```

**Eager Loading Profile:**
```php
// Nur benötigte Felder laden
$appointments = Appointment::select([
    'id', 'starts_at', 'status', 'service_id', 'customer_id', 'call_id'
])->with([
    'customer:id,name,phone',
    'service:id,name,price,duration',
    'staff:id,name'
])->latest('starts_at')->limit(20)->get();
```

### 1.5 Widget-Implementierung

**AppointmentKpiWidget:**
```php
class AppointmentKpiWidget extends FilterableWidget
{
    protected static string $view = 'filament.widgets.appointment-kpi';
    protected int $refreshInterval = 60; // 60s Auto-refresh
    
    public function getKpis(): array
    {
        return Cache::remember('appointment_kpis_' . $this->getFilters(), 60, function() {
            return [
                'revenue' => $this->calculateRevenue(),
                'occupancy' => $this->calculateOccupancy(),
                'conversion' => $this->calculateConversion(),
                'no_show_rate' => $this->calculateNoShowRate(),
                'avg_duration' => $this->calculateAvgDuration(),
            ];
        });
    }
}
```

---

## 🎨 2. ANRUFE-SEITE (CallResource)

### 2.1 Ziele & KPIs

**Business Objectives:**
- Call-Quality-Monitoring: Sentiment, Dauer, Erfolg
- Conversion-Optimierung: Welche Calls führen zu Terminen?
- Cost-Tracking: Kosten pro Call und ROI

**Key Performance Indicators:**
```
1. Calls heute (Anzahl + Δ%)
2. Ø Call-Dauer (min + Trend)
3. Erfolgsquote (Termin gebucht %)
4. Sentiment-Score (Positiv/Negativ %)
5. Kosten pro Call (€ + Budget)
6. ROI (Termin-Wert / Call-Kosten)
```

### 2.2 Layout-Design (ASCII-Skizze)

```
┌─────────────────────────────────────────────────────────────┐
│ 📞 ANRUFE                                      [Filter ▼]   │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │   47    │ │ 3:42min │ │   68%   │ │ 75% 📈  │ │ 1.20€   │ │
│ │ Calls   │ │ Ø Dauer │ │ Erfolg  │ │Positiv  │ │ø Kosten │ │
│ │ +8 ▲    │ │ +22s ▲  │ │ +12% ▲  │ │ +5% ▲   │ │ -0.10€  │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────┐ ┌─────────────────────────────────┐ │
│ │ 📊 CALL-VOLUMEN     │ │ 📈 DAUER-VERTEILUNG             │ │
│ │ (7 Tage)            │ │                                 │ │
│ │ ┌─────────────────┐ │ │ ┌─────────────────────────────┐ │ │
│ │ │ 60  ┌─┐         │ │ │ │ 30 ├─────────┐ < 1min        │ │ │
│ │ │ 40  │ │ ┌─┐     │ │ │ │ 20 ├─────┐     1-5min        │ │ │
│ │ │ 20  │ │ │ │ ┌─┐ │ │ │ │ 10 ├─┐       5-15min         │ │ │
│ │ │  0  └─┘ └─┘ └─┘ │ │ │ │  0 └─┘       >15min ⚠️       │ │ │
│ │ └─────────────────┘ │ │ └─────────────────────────────┘ │ │
│ └─────────────────────┘ └─────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 📋 ANRUFE (Live-Filter, Top 20)                        │ │
│ │ ┌─────────┬─────────┬─────────┬─────────┬─────────────┐ │ │
│ │ │ Zeit    │ Anrufer │ Dauer   │ Emotion │ Ergebnis    │ │ │
│ │ ├─────────┼─────────┼─────────┼─────────┼─────────────┤ │ │
│ │ │ 14:23 ▲ │ +49..89 │ 4:12min │ 😊 Pos  │ ✅ Termin   │ │ │
│ │ │ 14:15   │ +49..34 │ 0:45min │ 😐 Neu  │ ❌ Aufgel.  │ │ │
│ │ └─────────┴─────────┴─────────┴─────────┴─────────────┘ │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Spezielle Features

**Anomalie-Erkennung:**
```php
// Identifiziere ungewöhnliche Patterns
$shortCalls = Call::where('duration_sec', '<', 10)->count(); // Aufleger
$longCalls = Call::where('duration_sec', '>', 1800)->count(); // >30min
$negativeSpike = Call::whereJsonContains('analysis->sentiment', 'negative')
    ->whereBetween('created_at', [now()->subHour(), now()])->count();
```

**Real-time Updates:**
```php
// Auto-refresh alle 30s für Live-Monitoring
protected int $refreshInterval = 30;

// Push-Notifications für kritische Events
if ($negativeSpike > 3) {
    Notification::make()
        ->title('Sentiment-Alert')
        ->body("$negativeSpike negative Calls in der letzten Stunde")
        ->warning()
        ->send();
}
```

---

## 🎨 3. KUNDEN-SEITE (CustomerResource)

### 3.1 Ziele & KPIs

**Business Objectives:**
- Customer-Lifetime-Value: Wertschöpfung pro Kunde
- Segmentierung: VIP/Standard/Neue Kunden identifizieren
- Retention: Wiederkehrende vs. Einmalkunden

**Key Performance Indicators:**
```
1. Kunden gesamt (Anzahl + Wachstum)
2. Neue Kunden heute/Woche (Anzahl + Δ%)
3. Ø Customer Lifetime Value (€)
4. Wiederkehrende Kunden (% + Trend)
5. Top-Kunden Umsatz (€ + Anteil)
6. Churn Rate (% + Trend)
```

### 3.2 Layout-Design (ASCII-Skizze)

```
┌─────────────────────────────────────────────────────────────┐
│ 👥 KUNDEN                                      [Filter ▼]   │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │ 2.847   │ │   +12   │ │  340€   │ │   67%   │ │  5.8%   │ │
│ │ Kunden  │ │ Neue    │ │ Ø CLV   │ │ Wieder- │ │ Churn   │ │
│ │ +142 ▲  │ │ +3 ▲    │ │ +25€ ▲  │ │ kehrend │ │ -1.2% ▼ │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────┐ ┌─────────────────────────────────┐ │
│ │ 🔄 LEAD-FUNNEL      │ │ 🥧 KUNDEN-QUELLEN               │ │
│ │                     │ │                                 │ │
│ │  Anrufe    ┌─────┐  │ │ ┌─────────────────────────────┐ │ │
│ │    84  ────┤     │  │ │ │ 📞 Phone  45% ████████████  │ │ │
│ │            │ 68  │  │ │ │ 🌐 Web    30% ████████      │ │ │
│ │  Termine   └─────┤  │ │ │ 👥 Empf.  15% ████          │ │ │
│ │    68  ────┤     │  │ │ │ 🔗 Sozial 10% ███           │ │ │
│ │            │ 52  │  │ │ └─────────────────────────────┘ │ │
│ │  Kunden    └─────┘  │ │                                 │ │
│ │    52               │ │                                 │ │
│ └─────────────────────┘ └─────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 📋 KUNDEN (Segment-Filter, Live-Suche)                 │ │
│ │ [🥇 VIP] [⭐ Stamm] [🆕 Neu] [💰 Wert] [🔍 Suche...]     │ │
│ │ ┌─────────┬─────────┬─────────┬─────────┬─────────────┐ │ │
│ │ │ Kunde   │ Status  │ Wert    │ Termine │ Aktionen    │ │ │
│ │ ├─────────┼─────────┼─────────┼─────────┼─────────────┤ │ │
│ │ │ 🥇 Müller│ VIP     │ 2.340€  │ 12      │ [Tel][Mail] │ │ │
│ │ │ ⭐ Schmidt│ Stamm   │ 890€    │ 5       │ [Tel][Mail] │ │ │
│ │ └─────────┴─────────┴─────────┴─────────┴─────────────┘ │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 3.3 Segmentierung-Logik

**Kunden-Segmente:**
```php
// VIP-Kunden (Top 10% CLV)
$vipThreshold = Customer::avg('total_revenue') * 2;
$vipCustomers = Customer::where('total_revenue', '>', $vipThreshold);

// Stammkunden (≥3 Termine)
$loyalCustomers = Customer::has('appointments', '>=', 3);

// Neue Kunden (≤30 Tage)
$newCustomers = Customer::whereBetween('created_at', [now()->subDays(30), now()]);

// Churn-Risk (>90 Tage ohne Termin)
$churnRisk = Customer::whereDoesntHave('appointments', function($q) {
    $q->where('starts_at', '>', now()->subDays(90));
});
```

---

## 🚀 4. TECHNISCHE IMPLEMENTIERUNG

### 4.1 Performance-Architektur

**Caching-Layer:**
```php
// Multi-Level Caching
class DashboardMetricsService 
{
    public function getKpis(string $page, array $filters): array
    {
        $cacheKey = "kpis_{$page}_" . md5(serialize($filters));
        
        return Cache::tags(['kpis', $page])->remember($cacheKey, 60, function() use ($page, $filters) {
            return match($page) {
                'appointments' => $this->calculateAppointmentKpis($filters),
                'calls' => $this->calculateCallKpis($filters),
                'customers' => $this->calculateCustomerKpis($filters),
            };
        });
    }
}
```

**Database-Optimierung:**
```sql
-- Kritische Indexes für KPI-Performance
ALTER TABLE appointments ADD INDEX idx_revenue_calc (status, starts_at, service_id);
ALTER TABLE calls ADD INDEX idx_conversion_track (created_at, appointment_id);
ALTER TABLE customers ADD INDEX idx_clv_segment (total_revenue, created_at);
```

### 4.2 Widget-Basis-Architektur

**UniversalKpiWidget:**
```php
abstract class UniversalKpiWidget extends FilterableWidget
{
    protected static string $view = 'filament.widgets.universal-kpi';
    protected int $refreshInterval = 60;
    
    abstract protected function getKpiDefinitions(): array;
    abstract protected function calculateKpis(array $filters): array;
    
    public function getViewData(): array
    {
        $kpis = $this->calculateKpis($this->getFilters());
        
        return [
            'kpis' => $this->formatKpis($kpis),
            'trends' => $this->calculateTrends($kpis),
            'alerts' => $this->checkThresholds($kpis),
        ];
    }
}
```

### 4.3 Responsive Design System

**CSS Grid-Layout:**
```css
.dashboard-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

@media (max-width: 768px) {
    .kpi-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

---

## 🧪 5. TESTING-STRATEGIE

### 5.1 Unit Tests (90%+ Coverage)

**KPI-Berechnungen:**
```php
class AppointmentKpiTest extends TestCase
{
    public function test_revenue_calculation_with_completed_appointments()
    {
        // Arrange
        $service = Service::factory()->create(['price' => 100]);
        Appointment::factory(3)->create([
            'service_id' => $service->id,
            'status' => 'completed',
            'starts_at' => now()->subHour()
        ]);
        
        // Act
        $revenue = app(DashboardMetricsService::class)
            ->calculateAppointmentRevenue(today(), today());
        
        // Assert
        $this->assertEquals(300, $revenue);
    }
}
```

### 5.2 Browser Tests (Kern-Flows)

**Filter-Funktionalität:**
```php
class AppointmentPageTest extends TestCase
{
    public function test_branch_filter_updates_all_widgets()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/appointments')
                ->select('branch_filter', 'branch_1')
                ->waitForText('Filiale Berlin')
                ->assertSee('Berlin')
                ->assertDontSee('München');
        });
    }
}
```

---

## 📊 6. SUCCESS METRICS

### 6.1 Performance-Ziele

- **Load Time**: < 1 Sekunde (First Contentful Paint)
- **Lighthouse Score**: > 90 (Performance, Accessibility, Best Practices)
- **Database Queries**: < 10 pro Page Load
- **Memory Usage**: < 50MB pro Request

### 6.2 User Experience Metrics

- **Click-to-Insight**: ≤ 2 Klicks für jeden KPI-Drill-Down
- **Mobile Usability**: 100% Feature-Parität auf Mobile
- **Error Rate**: < 0.1% (Robust Error Handling)
- **User Feedback**: ≥ 4.5/5 Sterne (interne Bewertung)

### 6.3 Business Impact KPIs

- **Time-to-Insight**: 50% Reduktion (von 3min auf 1.5min)
- **Action-Conversion**: 25% Steigerung (mehr Aktionen durch bessere UX)
- **User Adoption**: 90%+ aller Nutzer verwenden neue Features
- **Training-Aufwand**: 80% Reduktion (Intuitive Bedienung)

---

## 🎯 7. IMPLEMENTATION ROADMAP

### Phase 1: Layout-Skeleton + Dummy-KPIs (2-3 Tage)
- Responsive Grid-Layout
- KPI-Cards mit Dummy-Daten
- Navigation & Filter-Grundstruktur
- Mobile-First Styling

### Phase 2: Echte Daten-Logik + Live-Filter (3-4 Tage)
- DashboardMetricsService implementieren
- Database-Queries optimieren
- Real-time Updates einbauen
- Cross-Widget-Filter-Synchronisation

### Phase 3: Clean-Up + Tests + Performance (2-3 Tage)
- Unit-Tests für alle KPI-Berechnungen
- Browser-Tests für kritische User-Flows
- Performance-Profiling & Optimierung
- Accessibility-Audit & Fixes

**Total Effort**: 7-10 Arbeitstage  
**Risk Buffer**: +2-3 Tage für unvorhergesehene Komplexität

---

## 🔧 8. EDGE CASES & ERROR HANDLING

### 8.1 Datenqualität

**Fehlende/Ungültige Daten:**
```php
// Graceful Degradation bei fehlenden Daten
public function calculateRevenue(): float
{
    try {
        $revenue = $this->getRevenueQuery()->value('revenue') ?? 0;
        return $revenue > 0 ? $revenue : 0;
    } catch (Exception $e) {
        Log::warning('Revenue calculation failed', ['error' => $e->getMessage()]);
        return 0; // Fallback auf 0 statt Error
    }
}
```

**Tooltip-Erklärungen:**
- "Warum 0%?" → "Noch keine Daten für den gewählten Zeitraum"
- "Warum N/A?" → "Berechnung erfordert mindestens 10 Datenpunkte"

### 8.2 Performance-Degradation

**Circuit Breaker für langsame Queries:**
```php
public function getKpisWithFallback(): array
{
    $timeout = 5; // 5 Sekunden Maximum
    
    try {
        return DB::timeout($timeout)->transaction(function() {
            return $this->calculateKpis();
        });
    } catch (QueryTimeoutException $e) {
        // Fallback auf cached Daten
        return Cache::get('kpis_fallback', []);
    }
}
```

---

## 💡 9. INNOVATION & FUTURE-PROOFING

### 9.1 AI-Enhanced Features

**Predictive Analytics:**
```php
// Vorhersage von No-Shows basierend auf Pattern
public function predictNoShowRisk(Appointment $appointment): float
{
    $features = [
        'hour_of_day' => $appointment->starts_at->hour,
        'day_of_week' => $appointment->starts_at->dayOfWeek,
        'customer_history' => $appointment->customer->no_show_rate,
        'booking_lead_time' => $appointment->created_at->diffInHours($appointment->starts_at),
    ];
    
    return $this->mlModel->predict($features);
}
```

### 9.2 Erweiterte Visualisierungen

**Interactive Charts:**
```html
<!-- Chart.js mit Drill-Down-Funktion -->
<canvas id="revenueChart" 
        data-drill-down="true"
        data-api-endpoint="/api/revenue-detail"
        data-filters="{{ json_encode($filters) }}">
</canvas>
```

### 9.3 Personalisierung

**User-Specific Dashboards:**
```php
// Benutzer-spezifische Widget-Konfiguration
public function getUserWidgetConfig(User $user): array
{
    return $user->preferences['dashboard_widgets'] ?? $this->getDefaultConfig();
}
```

---

## ✅ KRITISCHE HINTERFRAGUNG

### Erreiche ich maximalen Wert bei minimaler Komplexität?

**JA**, weil:
- ✅ Wiederverwendung bestehender Patterns (FilterableWidget, DashboardMetricsService)
- ✅ Fokus auf die 6 wichtigsten KPIs pro Seite (nicht 20+)
- ✅ Performance von Anfang an mitgedacht (Caching, Indexing)
- ✅ Mobile-First Design reduziert Komplexität

**Risiken & Mitigation:**
- ⚠️ **Risiko**: CallResource-Refactoring könnte komplex werden
  - **Mitigation**: Schrittweise Migration, Backward-Compatibility
- ⚠️ **Risiko**: Neue Indexes könnten Deployment verlangsamen
  - **Mitigation**: Online-Schema-Changes, Maintenance-Window

### Ist die Spezifikation implementierbar?

**JA**, alle Komponenten sind:
- ✅ Technisch machbar mit vorhandenen Tools
- ✅ Performance-optimiert durch bewährte Patterns
- ✅ Testbar durch klare Interfaces
- ✅ Wartbar durch modulare Architektur

**Ready for Implementation** ✅

---

*Diese Spezifikation dient als Masterplan für die Implementierung. Jede Phase wird iterativ validiert und optimiert.*