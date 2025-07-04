# AskProAI Final Technical Specification
## Version 1.0 - June 26, 2025

## Executive Summary

Nach umfassender Multi-Agent-Analyse wurden **42 kritische Sicherheitslücken**, **massive Performance-Probleme** und ein **nicht funktionierender Webhook-Flow** identifiziert. Diese Spezifikation definiert die notwendigen Schritte, um das System production-ready zu machen.

**Kernproblem**: Testanrufe erscheinen nicht im Dashboard aufgrund eines defekten End-to-End-Flows.

**Zeitrahmen**: 14 Arbeitstage mit 2-3 Entwicklern
**Priorität**: KRITISCH - System ist aktuell NICHT production-ready

---

## Phase 1: CRITICAL HOTFIXES (Tag 1-2)
### Ziel: System stabilisieren und Sicherheitslücken schließen

### 1.1 SQL Injection Fixes (2 Stunden)
**Problem**: 42 unsichere SQL Queries mit direktem User Input

**Lösung**:
```php
// ALT - UNSICHER:
DB::select("SELECT * FROM users WHERE email = '$email'");
$query->whereRaw("phone LIKE '" . $phone . "%'");

// NEU - SICHER:
DB::select("SELECT * FROM users WHERE email = ?", [$email]);
$query->where('phone', 'LIKE', $phone . '%');
```

**Betroffene Dateien**:
- `app/Services/CustomerService.php` (Lines 234, 567, 890)
- `app/Services/CalcomV2Service.php` (Lines 145, 289)
- `app/Http/Controllers/Api/SearchController.php` (Line 78)
- [Vollständige Liste in SECURITY_AUDIT_REPORT_2025-06-26.json]

### 1.2 Webhook Registration Fix (30 Minuten)
**Problem**: Retell sendet keine Webhooks

**Lösung**:
1. In Retell Dashboard einloggen
2. Agent `agent_9a8202a740cd3120d96fcfda1e` bearbeiten
3. Webhook URL: `https://api.askproai.de/api/retell/webhook`
4. Events aktivieren: `call_started`, `call_ended`, `call_analyzed`

### 1.3 Emergency Test Suite Fix (3 Stunden)
**Problem**: 94% der Tests schlagen fehl wegen falscher PHPUnit Syntax

**Fix**:
```php
// ALT - FALSCH:
use PHPUnit\Framework\Attributes\Test;
class ExampleTest extends TestCase {
    use Test;  // FALSCH!
    
// NEU - RICHTIG:
use PHPUnit\Framework\Attributes\Test;
class ExampleTest extends TestCase {
    #[Test]
    public function it_does_something() { }
```

**Script für Batch-Fix**:
```bash
find tests -name "*.php" -exec sed -i 's/use Test;//' {} \;
find tests -name "*.php" -exec sed -i 's/public function test_/\#[Test]\npublic function /' {} \;
```

### 1.4 Connection Pool Fix (1 Stunde)
**Problem**: System crasht bei >100 gleichzeitigen Verbindungen

**Fix in `config/database.php`**:
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
    ],
    'pool' => [
        'min' => 5,
        'max' => 50,
    ],
],
```

---

## Phase 2: CORE FUNCTIONALITY (Tag 3-5)
### Ziel: Testanrufe im Dashboard sichtbar machen

### 2.1 Company Resolution Fix (2 Stunden)
**Problem**: Telefonnummer → Company Zuordnung schlägt fehl

**Neue einheitliche Implementation**:
```php
namespace App\Services;

class UnifiedCompanyResolver
{
    public function resolveFromPhoneNumber(string $phoneNumber): ?Company
    {
        // 1. Normalisiere Nummer
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        
        // 2. Cache Check
        $cached = Cache::get("phone_company:{$normalized}");
        if ($cached) return Company::find($cached);
        
        // 3. Direkte Suche
        $phoneRecord = PhoneNumber::where('number', $normalized)
            ->with(['branch.company'])
            ->first();
            
        if ($phoneRecord && $phoneRecord->branch) {
            Cache::put("phone_company:{$normalized}", 
                      $phoneRecord->branch->company_id, 
                      3600);
            return $phoneRecord->branch->company;
        }
        
        // 4. Fallback
        return Company::where('is_active', true)->first();
    }
}
```

### 2.2 Live Dashboard Updates (2 Stunden)
**Problem**: Dashboard zeigt nur alte Daten

**Implementation mit Pusher**:
```php
// In ProcessRetellCallEndedJob:
public function handle()
{
    // ... Call verarbeiten ...
    
    // Live Update senden
    broadcast(new CallUpdated($call))
        ->toOthers()
        ->on('company.' . $call->company_id);
}

// Im Dashboard Blade:
<script>
    Echo.private('company.{{ auth()->user()->company_id }}')
        .listen('CallUpdated', (e) => {
            Livewire.emit('refreshCalls');
        });
</script>
```

### 2.3 Webhook Processing Fix (1 Stunde)
**Problem**: Jobs werden nicht korrekt verarbeitet

**Fix in `ProcessRetellCallEndedJob`**:
```php
public function handle()
{
    // Company Context setzen
    $this->applyCompanyContext();
    
    try {
        DB::transaction(function () {
            // Call erstellen/updaten
            $call = Call::updateOrCreate(
                ['retell_call_id' => $this->data['call']['call_id']],
                $this->prepareCallData()
            );
            
            // Event dispatchen für Live Update
            event(new CallProcessed($call));
        });
    } finally {
        $this->clearCompanyContext();
    }
}
```

---

## Phase 3: OPTIMIZATION (Tag 6-10)
### Ziel: Performance und Monitoring

### 3.1 Database Optimization (1 Tag)
**Neue Indizes**:
```sql
-- Kritische Performance Indizes
CREATE INDEX idx_calls_company_created ON calls(company_id, created_at DESC);
CREATE INDEX idx_calls_retell_id ON calls(retell_call_id);
CREATE INDEX idx_phone_numbers_normalized ON phone_numbers(number);
CREATE INDEX idx_webhook_events_status ON webhook_events(status, created_at);
```

### 3.2 Redis Caching Strategy (1 Tag)
```php
// Cache warming für häufige Queries
Cache::remember('company_phone_numbers:' . $companyId, 3600, function() {
    return PhoneNumber::where('company_id', $companyId)
        ->with('branch')
        ->get();
});
```

### 3.3 Monitoring Setup (2 Tage)
**Prometheus Metrics**:
```php
// In AppServiceProvider
$this->app->singleton('prometheus', function() {
    $registry = new CollectorRegistry(new Redis());
    
    // Custom Metrics
    $registry->registerCounter('webhooks_received', 'Total webhooks');
    $registry->registerHistogram('api_response_time', 'API response times');
    $registry->registerGauge('active_calls', 'Currently active calls');
    
    return $registry;
});
```

---

## Phase 4: FUTURE-PROOFING (Tag 11-14)
### Ziel: Skalierbarkeit und Wartbarkeit

### 4.1 Service Consolidation (2 Tage)
**Von 7 auf 1 Retell Service**:
```php
namespace App\Services\External;

interface ExternalServiceInterface
{
    public function authenticate(array $credentials): void;
    public function makeRequest(string $endpoint, array $data): array;
    public function handleError(\Exception $e): void;
}

class RetellService implements ExternalServiceInterface
{
    use CircuitBreaker, RetryLogic, Cacheable;
    
    // Einheitliche Implementation
}
```

### 4.2 Test Suite Rebuild (2 Tage)
**Ziel**: 95% Coverage für kritische Flows
```php
class RetellWebhookTest extends TestCase
{
    #[Test]
    public function it_processes_call_ended_webhook()
    {
        // Arrange
        $payload = $this->makeWebhookPayload('call_ended');
        
        // Act
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $this->generateSignature($payload)
        ]);
        
        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => $payload['call']['call_id']
        ]);
    }
}
```

---

## Success Metrics & Monitoring

### KPIs nach Implementation:
- ✅ **Webhook Processing Time**: < 500ms (aktuell: 3-5s)
- ✅ **Dashboard Load Time**: < 200ms (aktuell: 2-5s)
- ✅ **Live Update Delay**: < 1s (aktuell: nie)
- ✅ **Failed Jobs**: < 1% (aktuell: 15-20%)
- ✅ **Test Coverage**: > 80% (aktuell: 6%)
- ✅ **Security Score**: A+ (aktuell: F)

### Monitoring Dashboard:
```yaml
Grafana Dashboards:
  - Webhook Processing (Success Rate, Processing Time)
  - API Performance (Response Times, Error Rates)
  - System Health (CPU, Memory, Queue Size)
  - Business Metrics (Calls/Hour, Bookings/Day)
```

---

## Rollback Strategy

Jede Phase hat einen definierten Rollback-Plan:

1. **Database Changes**: Migrations mit `down()` Methoden
2. **Code Changes**: Git Tags für jeden Deployment
3. **Configuration**: Backup vor jeder Änderung
4. **Emergency**: Feature Flags für kritische Changes

```bash
# Rollback Script
./scripts/rollback.sh --to-tag=pre-phase-1 --with-db-restore
```

---

## Deployment Checklist

### Pre-Deployment:
- [ ] Alle Tests grün (min. 80% Coverage)
- [ ] Security Scan durchgeführt
- [ ] Performance Tests bestanden
- [ ] Rollback Plan dokumentiert
- [ ] Monitoring Alerts konfiguriert

### Post-Deployment:
- [ ] Smoke Tests durchführen
- [ ] Monitoring für 2 Stunden
- [ ] User Acceptance Tests
- [ ] Performance Baseline erfassen
- [ ] Incident Response Team bereit

---

## Zusammenfassung

Diese Spezifikation transformiert das AskProAI System von einem **kritisch unsicheren Prototyp** zu einer **production-ready Plattform**. Die Implementierung folgt einem risikominimierten Ansatz mit klaren Phasen, messbaren Erfolgen und Rollback-Strategien.

**Nächster Schritt**: Beginnen Sie HEUTE mit Phase 1.1 (SQL Injection Fixes).

**Erwartetes Ergebnis**: In 14 Tagen haben Sie ein stabiles, sicheres und skalierbares System, das zuverlässig Anrufe in Termine umwandelt.

---

*Dokument erstellt: 26.06.2025*  
*Version: 1.0*  
*Status: FINAL*