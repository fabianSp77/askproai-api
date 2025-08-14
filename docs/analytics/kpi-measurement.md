# KPI-Messmethoden - AskProAI Analytics

## Übersicht

Dieses Dokument definiert Key Performance Indicators (KPIs) und deren Messmethoden für das AskProAI-System zur datengetriebenen Optimierung der KI-Telefonassistenz.

## 1. Call Performance KPIs

### 1.1 Call Success Rate
**Definition**: Anteil erfolgreich abgeschlossener Gespräche

**Berechnung**:
```sql
SELECT 
  (COUNT(*) FILTER (WHERE status = 'completed') * 100.0 / COUNT(*)) AS call_success_rate
FROM calls 
WHERE created_at >= NOW() - INTERVAL '30 days';
```

**Zielwerte**:
- Exzellent: ≥ 95%
- Gut: 85-94%
- Verbesserungsbedarf: < 85%

### 1.2 Average Call Duration
**Definition**: Durchschnittliche Gesprächsdauer in Sekunden

**Berechnung**:
```sql
SELECT 
  AVG(duration_seconds) AS avg_call_duration,
  PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY duration_seconds) AS median_duration
FROM calls 
WHERE status = 'completed' 
  AND created_at >= NOW() - INTERVAL '30 days';
```

**Benchmarks**:
- Terminbuchung: 60-120 Sekunden
- Informationsgespräch: 30-90 Sekunden
- Beratung: 180-300 Sekunden

### 1.3 Call Volume Trends
**Definition**: Anrufvolumen über Zeit

**Laravel Implementation**:
```php
// app/Services/Analytics/CallMetricsService.php
public function getCallVolumeTrends($days = 30) {
    return Call::selectRaw('
        DATE(created_at) as date,
        COUNT(*) as total_calls,
        COUNT(*) FILTER (WHERE status = "completed") as successful_calls,
        AVG(duration_seconds) as avg_duration
    ')
    ->where('created_at', '>=', now()->subDays($days))
    ->groupBy('date')
    ->orderBy('date')
    ->get();
}
```

## 2. Booking Conversion KPIs

### 2.1 Appointment Conversion Rate
**Definition**: Anteil der Gespräche, die zu einer Terminbuchung führen

**Berechnung**:
```sql
SELECT 
  (COUNT(a.id) * 100.0 / COUNT(c.id)) AS appointment_conversion_rate
FROM calls c
LEFT JOIN appointments a ON c.id = a.call_id
WHERE c.created_at >= NOW() - INTERVAL '30 days'
  AND c.status = 'completed';
```

**Zielwerte**:
- Exzellent: ≥ 80%
- Gut: 60-79%
- Verbesserungsbedarf: < 60%

### 2.2 Booking Completion Rate
**Definition**: Anteil der gebuchten Termine, die auch wahrgenommen wurden

**Laravel Implementation**:
```php
public function getBookingCompletionRate($days = 30) {
    $bookings = Appointment::where('created_at', '>=', now()->subDays($days));
    
    $completed = $bookings->where('status', 'completed')->count();
    $total = $bookings->count();
    
    return $total > 0 ? ($completed / $total) * 100 : 0;
}
```

### 2.3 No-Show Rate
**Definition**: Anteil nicht wahrgenommener Termine

**Berechnung**:
```sql
SELECT 
  (COUNT(*) FILTER (WHERE status = 'no_show') * 100.0 / COUNT(*)) AS no_show_rate
FROM appointments 
WHERE scheduled_at >= NOW() - INTERVAL '30 days'
  AND scheduled_at <= NOW();
```

## 3. Customer Experience KPIs

### 3.1 Customer Satisfaction Score (CSAT)
**Definition**: Durchschnittliche Kundenzufriedenheit (1-5 Skala)

**Laravel Implementation**:
```php
public function getCustomerSatisfaction($days = 30) {
    return Call::where('created_at', '>=', now()->subDays($days))
        ->whereNotNull('satisfaction_rating')
        ->selectRaw('
            AVG(satisfaction_rating) as avg_rating,
            COUNT(*) FILTER (WHERE satisfaction_rating >= 4) * 100.0 / COUNT(*) as satisfaction_rate
        ')
        ->first();
}
```

### 3.2 First Call Resolution Rate
**Definition**: Anteil der Anfragen, die im ersten Gespräch gelöst wurden

**Berechnung**:
```sql
SELECT 
  (COUNT(*) FILTER (WHERE resolution_status = 'resolved_first_call') * 100.0 / COUNT(*)) AS fcr_rate
FROM calls 
WHERE created_at >= NOW() - INTERVAL '30 days'
  AND call_type = 'customer_service';
```

### 3.3 Response Time
**Definition**: Zeit bis zur ersten Antwort der KI

**Laravel Implementation**:
```php
public function getResponseTimeMetrics() {
    return Call::selectRaw('
        AVG(first_response_time_ms) as avg_response_time,
        PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY first_response_time_ms) as p95_response_time,
        COUNT(*) FILTER (WHERE first_response_time_ms <= 2000) * 100.0 / COUNT(*) as sub_2s_rate
    ')
    ->where('created_at', '>=', now()->subDays(30))
    ->first();
}
```

## 4. Business KPIs

### 4.1 Revenue per Call
**Definition**: Durchschnittlicher Umsatz pro Anruf

**Berechnung**:
```sql
SELECT 
  SUM(a.service_price) / COUNT(DISTINCT c.id) AS revenue_per_call
FROM calls c
LEFT JOIN appointments a ON c.id = a.call_id
WHERE c.created_at >= NOW() - INTERVAL '30 days'
  AND a.status = 'completed';
```

### 4.2 Cost per Acquisition (CPA)
**Definition**: Kosten zur Gewinnung eines neuen Kunden

**Laravel Implementation**:
```php
public function getCostPerAcquisition($month) {
    $totalCosts = $this->getTotalOperatingCosts($month);
    $newCustomers = Customer::whereMonth('created_at', $month)
        ->whereYear('created_at', now()->year)
        ->count();
    
    return $newCustomers > 0 ? $totalCosts / $newCustomers : 0;
}
```

### 4.3 Customer Lifetime Value (CLV)
**Definition**: Durchschnittlicher Wert eines Kunden über die gesamte Kundenbeziehung

**Berechnung**:
```sql
SELECT 
  c.id,
  COUNT(a.id) as total_appointments,
  SUM(a.service_price) as total_revenue,
  AVG(a.service_price) as avg_order_value,
  DATEDIFF(MAX(a.scheduled_at), MIN(a.scheduled_at)) as customer_lifespan_days
FROM customers c
LEFT JOIN appointments a ON c.id = a.customer_id
WHERE a.status = 'completed'
GROUP BY c.id;
```

## 5. System Performance KPIs

### 5.1 System Availability
**Definition**: Verfügbarkeit des Systems in Prozent

**Monitoring**:
```php
// app/Console/Commands/SystemHealthCheck.php
public function handle() {
    $uptime = $this->checkSystemUptime();
    $availability = ($uptime / (24 * 60)) * 100; // Daily availability
    
    Metric::create([
        'name' => 'system_availability',
        'value' => $availability,
        'timestamp' => now()
    ]);
}
```

**Zielwerte**:
- SLA: 99.9% (< 43 Minuten Ausfall/Monat)
- Excellent: ≥ 99.95%

### 5.2 API Response Time
**Definition**: Durchschnittliche Response-Zeit der APIs

**Laravel Middleware**:
```php
public function handle($request, Closure $next) {
    $startTime = microtime(true);
    $response = $next($request);
    $duration = (microtime(true) - $startTime) * 1000;
    
    Log::info('API Response Time', [
        'endpoint' => $request->path(),
        'method' => $request->method(),
        'duration_ms' => $duration
    ]);
    
    return $response;
}
```

### 5.3 Error Rate
**Definition**: Anteil fehlerhafter Requests

**Berechnung**:
```sql
SELECT 
  (COUNT(*) FILTER (WHERE response_code >= 400) * 100.0 / COUNT(*)) AS error_rate
FROM api_logs 
WHERE created_at >= NOW() - INTERVAL '1 hour';
```

## 6. KPI Dashboard Implementation

### 6.1 Real-time Metrics Collection
```php
// app/Jobs/CollectMetricsJob.php
class CollectMetricsJob implements ShouldQueue {
    public function handle() {
        $metrics = [
            'call_success_rate' => $this->calculateCallSuccessRate(),
            'appointment_conversion' => $this->calculateAppointmentConversion(),
            'avg_response_time' => $this->calculateAvgResponseTime(),
            'system_availability' => $this->checkSystemAvailability(),
        ];
        
        foreach ($metrics as $name => $value) {
            KPI::updateOrCreate(
                ['name' => $name, 'date' => now()->format('Y-m-d')],
                ['value' => $value]
            );
        }
    }
}
```

### 6.2 Filament Dashboard Widget
```php
// app/Filament/Widgets/KPIOverviewWidget.php
class KPIOverviewWidget extends BaseWidget {
    protected function getStats(): array {
        return [
            Stat::make('Call Success Rate', $this->getCallSuccessRate() . '%')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-phone')
                ->color('success'),
                
            Stat::make('Booking Conversion', $this->getBookingConversion() . '%')
                ->description('Calls to appointments')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Customer Satisfaction', $this->getCSAT() . '/5')
                ->description('Average rating')
                ->descriptionIcon('heroicon-m-star')
                ->color($this->getCSAT() >= 4 ? 'success' : 'warning'),
        ];
    }
}
```

## 7. Automated Reporting

### 7.1 Daily KPI Report
```php
// app/Console/Commands/GenerateDailyKPIReport.php
public function handle() {
    $report = [
        'date' => now()->format('Y-m-d'),
        'call_metrics' => $this->getCallMetrics(),
        'booking_metrics' => $this->getBookingMetrics(),
        'system_metrics' => $this->getSystemMetrics(),
    ];
    
    // Send to stakeholders
    Mail::to('management@askproai.de')->send(new DailyKPIReport($report));
}
```

### 7.2 Weekly Business Review
```php
Schedule::command('kpi:weekly-report')
    ->weekly()
    ->mondays()
    ->at('08:00');
```

## 8. Alerting und Thresholds

### 8.1 Performance Alerts
```php
public function checkKPIThresholds() {
    $callSuccessRate = $this->getCallSuccessRate();
    if ($callSuccessRate < 85) {
        Alert::create([
            'type' => 'performance',
            'severity' => 'high',
            'message' => "Call success rate dropped to {$callSuccessRate}%",
            'kpi' => 'call_success_rate'
        ]);
    }
}
```

### 8.2 Business Critical Alerts
- Call Success Rate < 85%
- System Availability < 99%
- Response Time > 3 seconds
- Booking Conversion < 50%

## 9. Data Export und Integration

### 9.1 KPI Data Export
```php
// API endpoint für externe BI Tools
Route::get('/api/kpis/export', function(Request $request) {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    
    return KPI::whereBetween('date', [$startDate, $endDate])
        ->get()
        ->groupBy('name');
});
```

### 9.2 Third-party Integration
- Google Analytics Integration für Web-Metriken
- Stripe Dashboard für Revenue-Metriken
- Cal.com Analytics für Booking-Metriken

## 10. Kontinuierliche Verbesserung

### 10.1 A/B Testing Framework
```php
public function trackABTestMetrics($testName, $variant, $outcome) {
    ABTestMetric::create([
        'test_name' => $testName,
        'variant' => $variant,
        'outcome' => $outcome,
        'customer_id' => auth()->id(),
        'timestamp' => now()
    ]);
}
```

### 10.2 Predictive Analytics
- Machine Learning Models für Churn Prediction
- Seasonal Trend Analysis
- Customer Behavior Patterns

## Fazit

Diese KPI-Messmethoden ermöglichen:
- **Datengetriebene Entscheidungen** basierend auf objektiven Metriken
- **Proaktive Optimierung** durch Trend-Erkennung
- **Geschäftserfolg-Tracking** mit direktem ROI-Nachweis
- **Kontinuierliche Verbesserung** durch systematisches Monitoring